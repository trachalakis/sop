<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Settings\Settings;

final class PrinterRequestLog
{
    private const WINDOW_SECONDS = 300;
    private const MAX_ENTRIES    = 500;

    private string $cacheKey;

    public function __construct(Settings $settings)
    {
        $this->cacheKey = 'printer_request_log:' . $settings->get('siteName');
    }

    public function record(array $entry): void
    {
        $entries = apcu_fetch($this->cacheKey);
        if (!is_array($entries)) {
            $entries = [];
        }

        $cutoff  = microtime(true) - self::WINDOW_SECONDS;
        $entries = array_values(array_filter($entries, fn ($e) => $e['ts'] >= $cutoff));
        $entries[] = $entry;

        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, -self::MAX_ENTRIES);
        }

        apcu_store($this->cacheKey, $entries);
    }

    public function getRecent(): array
    {
        $entries = apcu_fetch($this->cacheKey);
        if (!is_array($entries)) {
            return [];
        }
        $cutoff = microtime(true) - self::WINDOW_SECONDS;
        return array_values(array_filter($entries, fn ($e) => $e['ts'] >= $cutoff));
    }
}
