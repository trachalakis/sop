<?php

declare(strict_types=1);

namespace Application\Services\InvoiceProviders;

/**
 * Fallback provider that scans the entire page for any AADE TimologioQR URL.
 * Matches every URL (supports() always returns true) so it must be registered
 * LAST in the registry — vendor-specific providers should get a shot first.
 */
final class GenericInvoiceProvider implements InvoiceProvider
{
    public function supports(string $url): bool
    {
        return true;
    }

    public function parseDirectly(string $url): ?array
    {
        return null;
    }

    public function extractAadeUrl(string $html): ?string
    {
        return self::scan($html);
    }

    public function name(): string
    {
        return 'generic';
    }

    /**
     * Exposed statically so vendor-specific providers can reuse the regex as
     * a final attempt when their structured extraction fails.
     */
    public static function scan(string $html): ?string
    {
        if (preg_match('~https?://[^\s"\'<>]*mydatapi\.aade\.gr/myDATA/TimologioQR/QRInfo\?q=[^\s"\'<>]+~i', $html, $m)) {
            return html_entity_decode($m[0], ENT_QUOTES | ENT_HTML5);
        }
        return null;
    }
}
