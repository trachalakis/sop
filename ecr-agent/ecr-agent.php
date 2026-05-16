#!/usr/bin/env php
<?php

declare(strict_types=1);

$config = require __DIR__ . '/ecr-agent-config.php';

// Prevent concurrent cron runs
$lockFile = sys_get_temp_dir() . '/ecr-agent.lock';
$lock = fopen($lockFile, 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo date('Y-m-d H:i:s') . " Already running, exiting.\n";
    exit(0);
}

function log_msg(string $msg): void
{
    echo date('Y-m-d H:i:s') . ' ' . $msg . "\n";
}

function http_get(string $url, string $apiKey): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['X-Api-Key: ' . $apiKey],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if ($body === false) {
        return null;
    }
    return json_decode($body, true);
}

function http_post(string $url, string $apiKey, array $data): void
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'X-Api-Key: ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function calc_checksum(string $data): string
{
    $sum = 0;
    foreach (str_split($data) as $char) {
        $sum += ord($char);
    }
    return sprintf('%02d', $sum % 100);
}

function read_byte($socket, int $timeout): string
{
    $read = [$socket];
    $write = $except = null;
    if (stream_select($read, $write, $except, $timeout) < 1) {
        throw new RuntimeException("Socket read timeout");
    }
    $byte = fread($socket, 1);
    if ($byte === false || $byte === '') {
        throw new RuntimeException("Socket closed unexpectedly");
    }
    return $byte;
}

function read_packet($socket, int $timeout): string
{
    // Wait for STX (0x02), discard anything before it
    $deadline = time() + $timeout;
    do {
        if (time() > $deadline) {
            throw new RuntimeException("Timeout waiting for STX");
        }
        $byte = read_byte($socket, $timeout);
    } while ($byte !== chr(0x02));

    // Read until ETX (0x03), accumulate only printable bytes (>=32)
    $data    = '';
    $deadline = time() + $timeout;
    while (true) {
        if (time() > $deadline) {
            throw new RuntimeException("Timeout waiting for ETX");
        }
        $byte = read_byte($socket, $timeout);
        if ($byte === chr(0x03)) {
            break;
        }
        if (ord($byte) >= 32) {
            $data .= $byte;
        }
    }
    return $data;
}

function send_item_sale($socket, array $entry, int $maxRetries, int $timeout): void
{
    // Build data string: 3/S/<name>//<qty>/<unitPrice>/<dept>/
    $name = mb_substr($entry['name'], 0, 20);
    $data = sprintf(
        '3/S/%s//%s/%s/%d/',
        $name,
        $entry['quantity'],
        $entry['unitPrice'],
        $entry['fiscalDepartment']
    );
    $data .= calc_checksum($data); // 2-digit checksum appended

    // Step 1: ENQ handshake — send ENQ (0x05), wait for ACK (0x06)
    $acked = false;
    for ($i = 0; $i < $maxRetries; $i++) {
        fwrite($socket, chr(0x05));
        $resp = read_byte($socket, $timeout);
        if ($resp === chr(0x06)) {
            $acked = true;
            break;
        }
    }
    if (!$acked) {
        throw new RuntimeException("ENQ not acknowledged after {$maxRetries} attempts");
    }

    // Step 2: Send packet — STX + data + ETX, wait for ACK
    $packet = chr(0x02) . $data . chr(0x03);
    $acked  = false;
    for ($i = 0; $i < $maxRetries; $i++) {
        fwrite($socket, $packet);
        $resp = read_byte($socket, $timeout);
        if ($resp === chr(0x06)) {
            $acked = true;
            break;
        }
    }
    if (!$acked) {
        throw new RuntimeException("Packet not acknowledged after {$maxRetries} attempts");
    }

    // Step 3: Receive reply packet, send ACK
    $reply = read_packet($socket, $timeout);
    fwrite($socket, chr(0x06)); // ACK the reply

    // Verify reply code matches request code '3'
    $parts = explode('/', $reply);
    if (($parts[0] ?? '') !== '3') {
        throw new RuntimeException(
            "Unexpected reply code: " . ($parts[0] ?? 'none') . " (full reply: {$reply})"
        );
    }
}

function ack_job(int $jobId, string $status, ?string $error, array $config): void
{
    $url  = rtrim($config['cloud_api_url'], '/') . '/api/ecr/jobs/' . $jobId . '/ack';
    $data = ['status' => $status];
    if ($error !== null) {
        $data['error'] = $error;
    }
    http_post($url, $config['api_key'], $data);
}

// ── Main ──────────────────────────────────────────────────────────────────────

$url  = rtrim($config['cloud_api_url'], '/') . '/api/ecr/jobs';
$jobs = http_get($url, $config['api_key']);

if (!is_array($jobs)) {
    log_msg("ERROR: Failed to fetch jobs from cloud API.");
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(1);
}

if (empty($jobs)) {
    log_msg("No pending jobs.");
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(0);
}

log_msg("Processing " . count($jobs) . " job(s).");

foreach ($jobs as $job) {
    $jobId  = (int) $job['id'];
    $socket = null;

    try {
        // Validate fiscal departments before touching the ECR
        foreach ($job['entries'] as $entry) {
            if ($entry['fiscalDepartment'] === null) {
                throw new RuntimeException(
                    "Entry '{$entry['name']}' has no fiscal department configured."
                );
            }
        }

        // Open TCP socket to ECR
        $socket = @fsockopen(
            $config['ecr_host'],
            $config['ecr_port'],
            $errno,
            $errstr,
            $config['timeout']
        );
        if (!$socket) {
            throw new RuntimeException(
                "Cannot connect to ECR at {$config['ecr_host']}:{$config['ecr_port']}: {$errstr} ({$errno})"
            );
        }
        stream_set_timeout($socket, $config['timeout']);

        // Send one item sale command per entry
        foreach ($job['entries'] as $entry) {
            send_item_sale($socket, $entry, $config['max_retries'], $config['timeout']);
        }

        fclose($socket);

        ack_job($jobId, 'sent', null, $config);
        log_msg("Job {$jobId} (order {$job['orderId']}): sent successfully.");

    } catch (RuntimeException $e) {
        if ($socket) {
            fclose($socket);
        }
        ack_job($jobId, 'failed', $e->getMessage(), $config);
        log_msg("Job {$jobId} (order {$job['orderId']}): FAILED — " . $e->getMessage());
    }
}

flock($lock, LOCK_UN);
fclose($lock);
