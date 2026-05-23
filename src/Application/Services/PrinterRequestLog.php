<?php

declare(strict_types=1);

namespace Application\Services;

final class PrinterRequestLog
{
    private const CACHE_KEY      = 'printer_request_log';
    private const WINDOW_SECONDS = 300;
    private const MAX_ENTRIES    = 500;

    public function record(array $entry): void
    {
        $entries = apcu_fetch(self::CACHE_KEY);
        if (!is_array($entries)) {
            $entries = [];
        }

        $cutoff  = microtime(true) - self::WINDOW_SECONDS;
        $entries = array_values(array_filter($entries, fn ($e) => $e['ts'] >= $cutoff));
        $entries[] = $entry;

        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, -self::MAX_ENTRIES);
        }

        apcu_store(self::CACHE_KEY, $entries);
    }

    public function getRecent(): array
    {
        $entries = apcu_fetch(self::CACHE_KEY);
        if (!is_array($entries)) {
            return [];
        }
        $cutoff = microtime(true) - self::WINDOW_SECONDS;
        return array_values(array_filter($entries, fn ($e) => $e['ts'] >= $cutoff));
    }
}
