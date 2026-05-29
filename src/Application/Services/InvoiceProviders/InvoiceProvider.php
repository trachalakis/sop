<?php

declare(strict_types=1);

namespace Application\Services\InvoiceProviders;

/**
 * A QR-code URL on a Greek invoice usually points at the software vendor that
 * issues the invoice (Impact, Epsilon Net, SoftOne, …). Each vendor renders
 * its verification page differently. A provider knows how to recognise its
 * own pages and either parse the invoice directly (when the vendor exposes
 * structured data) or extract the embedded AADE/myDATA verification URL so
 * the fetcher can fall back to AADE.
 *
 * Implementations should be cheap to instantiate and stateless.
 */
interface InvoiceProvider
{
    /**
     * True if this provider handles QR URLs of the given form.
     * The fetcher walks providers in registration order and uses the first
     * supporting provider whose extraction or direct parse succeeds.
     */
    public function supports(string $url): bool;

    /**
     * Fast path: fetch and parse the invoice directly, without needing the
     * AADE round-trip. Implementations return null to fall through to
     * extractAadeUrl(); a non-null array means "I produced the final parsed
     * invoice; the fetcher should use it as-is".
     *
     * The returned array must match the shape produced by
     * AadeInvoiceFetcherService::parse() plus a 'mark' and (when known) a
     * 'mydata_url' key.
     */
    public function parseDirectly(string $url): ?array;

    /**
     * Fallback path: given the HTML the fetcher already downloaded for the
     * provider's verification page, return the embedded AADE TimologioQR/
     * QRInfo URL so the fetcher can hit AADE itself. Return null to fall
     * through to the next provider in the registry.
     */
    public function extractAadeUrl(string $html): ?string;

    /**
     * Short human-readable name used in error messages.
     */
    public function name(): string;
}
