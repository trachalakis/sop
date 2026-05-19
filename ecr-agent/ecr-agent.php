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
    // MCP spec section 6.3.1: 8-bit sum (BYTE arithmetic, wraps) mod 100
    $sum = 0;
    foreach (str_split($data) as $char) {
        $sum = ($sum + ord($char)) & 0xFF;
    }
    return sprintf('%02d', $sum % 100);
}

function read_reply($socket, int $timeout): string
{
    // First byte: wait up to $timeout seconds.
    $read = [$socket]; $write = $except = null;
    if (stream_select($read, $write, $except, $timeout) < 1) {
        throw new RuntimeException("No reply within {$timeout}s");
    }
    $reply = fread($socket, 4096);
    if ($reply === false || $reply === '') {
        throw new RuntimeException("Socket closed before reply received");
    }

    // Subsequent bytes: drain until socket idle for 500ms (end of message).
    while (true) {
        $read = [$socket]; $write = $except = null;
        if (stream_select($read, $write, $except, 0, 500000) < 1) {
            break;
        }
        $chunk = fread($socket, 4096);
        if ($chunk === false || $chunk === '') {
            break;
        }
        $reply .= $chunk;
    }

    return $reply;
}

function send_item_sale($socket, array $entry, int $timeout): void
{
    $name = mb_substr($entry['name'], 0, 20);
    $data = sprintf(
        '3/S/%s//%s/%s/%d/',
        $name,
        $entry['quantity'],
        $entry['unitPrice'],
        $entry['fiscalDepartment']
    );
    $packet = $data . calc_checksum($data);

    fwrite($socket, $packet);
    $reply = read_reply($socket, $timeout);

    $fields = explode('/', $reply);
    $code = $fields[0] ?? '';
    if ($code !== '00') {
        throw new RuntimeException("ECR returned error {$code} (reply: {$reply})");
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

try {
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
                send_item_sale($socket, $entry, $config['timeout']);
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
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
