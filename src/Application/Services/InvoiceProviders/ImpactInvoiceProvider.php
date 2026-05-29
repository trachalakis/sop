<?php

declare(strict_types=1);

namespace Application\Services\InvoiceProviders;

/**
 * Impact (https://impact.gr). The verification page is a Blazor app whose
 * "Εγγραφή στο MyData" button is a fluent-anchor with id="erpQrBtn" whose
 * href points at the AADE TimologioQR URL.
 *
 * Page format observed on einvoice.impact.gr in May 2026.
 */
final class ImpactInvoiceProvider implements InvoiceProvider
{
    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        return $host === 'einvoice.impact.gr' || str_ends_with($host, '.impact.gr');
    }

    public function parseDirectly(string $url): ?array
    {
        return null;
    }

    public function extractAadeUrl(string $html): ?string
    {
        // Targeted: the AADE-verify button has a stable id.
        if (preg_match('~id=["\']erpQrBtn["\'][^>]*\bhref=["\']([^"\']+)["\']~i', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
        }

        // Fallback: scan for any AADE URL if Impact reshuffles their markup.
        return GenericInvoiceProvider::scan($html);
    }

    public function name(): string
    {
        return 'impact';
    }
}
