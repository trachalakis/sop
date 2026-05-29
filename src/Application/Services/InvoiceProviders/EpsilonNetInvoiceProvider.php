<?php

declare(strict_types=1);

namespace Application\Services\InvoiceProviders;

use DOMDocument;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Epsilon Net (https://epsilonnet.gr). The DocViewer page itself is a Blazor
 * WebAssembly SPA we can't read, but the same backend exposes the canonical
 * AADE myDATA InvoicesDoc XML at /filedocument/GetInvoiceDocDetailed/{guid}
 * with the same UUID. Parsing that XML directly is more reliable than the
 * AADE round-trip used by other providers — we skip extractAadeUrl().
 *
 * Hostnames observed in 2026: epsilondigital{N}.epsilonnet.gr.
 */
final class EpsilonNetInvoiceProvider implements InvoiceProvider
{
    private const MYDATA_NS = 'http://www.aade.gr/myDATA/invoice/v1.0';

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        return $host === 'epsilonnet.gr' || str_ends_with($host, '.epsilonnet.gr');
    }

    public function parseDirectly(string $url): ?array
    {
        $xmlUrl = $this->deriveXmlUrl($url);
        if ($xmlUrl === null) {
            throw new RuntimeException(
                'Epsilon Net URL is not in the expected /DocViewer/{guid} form'
            );
        }

        $xml = $this->httpGet($xmlUrl);
        $parsed = $this->parseMyDataXml($xml);
        $parsed['mydata_url'] = $url; // keep the original DocViewer URL for re-viewing
        return $parsed;
    }

    public function extractAadeUrl(string $html): ?string
    {
        return null;
    }

    public function name(): string
    {
        return 'epsilonnet';
    }

    private function deriveXmlUrl(string $docViewerUrl): ?string
    {
        $parts = parse_url($docViewerUrl);
        if (!isset($parts['scheme'], $parts['host'], $parts['path'])) {
            return null;
        }
        if (!preg_match('~^/DocViewer/([0-9a-f-]+)/?$~i', $parts['path'], $m)) {
            return null;
        }
        return $parts['scheme'] . '://' . $parts['host'] . '/filedocument/GetInvoiceDocDetailed/' . $m[1];
    }

    private function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; sop-invoice-fetcher/1.0)',
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $status >= 400) {
            throw new RuntimeException("Failed to fetch Epsilon Net XML (HTTP {$status})");
        }
        return (string) $body;
    }

    private function parseMyDataXml(string $xml): array
    {
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) {
            throw new RuntimeException('Could not parse the InvoiceDoc XML returned by Epsilon Net');
        }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('m', self::MYDATA_NS);

        $invoice = $xp->query('//m:invoice')->item(0);
        if ($invoice === null) {
            throw new RuntimeException('No <invoice> element in the Epsilon Net XML');
        }

        $text = function (string $path, ?DOMNode $ctx = null) use ($xp, $invoice): string {
            $node = $xp->query($path, $ctx ?? $invoice)->item(0);
            return $node ? trim($node->textContent) : '';
        };
        $num  = function (string $path, ?DOMNode $ctx = null) use ($text): float {
            $s = $text($path, $ctx);
            return $s === '' ? 0.0 : (float) $s;
        };

        // Issuer (supplier)
        $supplierName = $text('m:issuer/m:name');
        $supplierAfm  = $text('m:issuer/m:vatNumber');
        $address      = $this->joinAddress(
            $text('m:issuer/m:address/m:street'),
            $text('m:issuer/m:address/m:number'),
            $text('m:issuer/m:address/m:postalCode'),
            $text('m:issuer/m:address/m:city'),
        );

        // Header
        $series          = $text('m:invoiceHeader/m:series');
        $aa              = $text('m:invoiceHeader/m:aa');
        $issueDate       = $text('m:invoiceHeader/m:issueDate'); // already YYYY-MM-DD
        $invoiceTypeCode = $text('m:invoiceHeader/m:invoiceType');
        $documentType    = $this->mapInvoiceType($invoiceTypeCode);

        $mark = $text('m:mark');

        // Totals
        $netTotal   = $num('m:invoiceSummary/m:totalNetValue');
        $vatTotal   = $num('m:invoiceSummary/m:totalVatAmount');
        $grossTotal = $num('m:invoiceSummary/m:totalGrossValue');

        // Line items
        $entries = [];
        $details = $xp->query('m:invoiceDetails', $invoice);
        foreach ($details as $detail) {
            $description = $text('m:itemDescr', $detail);
            $quantity    = $num('m:quantity', $detail);
            if ($description === '' || $quantity <= 0) {
                continue;
            }

            $lineNumber = (int) $text('m:lineNumber', $detail);
            $itemCode   = $text('m:itemCode', $detail);
            $unit       = $this->mapMeasurementUnit($text('m:measurementUnit', $detail));
            $netValue   = $num('m:netValue', $detail);
            $vatAmount  = $num('m:vatAmount', $detail);

            $unitPrice = round($netValue / $quantity, 4);
            $vatRate   = $netValue > 0 ? (int) round(($vatAmount / $netValue) * 100) : 0;

            $entries[] = [
                'description'   => $description,
                'quantity'      => $quantity,
                'unit_price'    => $unitPrice,
                'supplier_code' => $itemCode !== '' ? $itemCode : null,
                'unit'          => $unit,
                'vat_amount'    => $vatAmount,
                'vat_rate'      => $vatRate,
                'line_number'   => $lineNumber > 0 ? $lineNumber : null,
                'extras'        => [
                    'line_total' => $netValue,
                    'discounts'  => [],
                ],
            ];
        }

        return [
            'supplier_name'    => $supplierName,
            'supplier_details' => [
                'afm'      => $supplierAfm !== '' ? $supplierAfm : null,
                'doy'      => null,
                'address'  => $address !== '' ? $address : null,
                'email'    => null,
                'website'  => null,
                'activity' => null,
            ],
            'invoice_number' => $aa !== '' ? $aa : null,
            'series'         => $series !== '' ? $series : null,
            'document_type'  => $documentType,
            'date'           => $issueDate !== '' ? $issueDate : null,
            'net_total'      => $netTotal > 0 ? $netTotal : null,
            'vat_total'      => $vatTotal > 0 ? $vatTotal : null,
            'gross_total'    => $grossTotal > 0 ? $grossTotal : null,
            'entries'        => $entries,
            'mark'           => $mark !== '' ? $mark : null,
        ];
    }

    private function joinAddress(string $street, string $number, string $postal, string $city): string
    {
        $line1 = trim($street . ' ' . $number);
        $line2 = trim($postal . ' ' . $city);
        return trim($line1 . ($line1 !== '' && $line2 !== '' ? ', ' : '') . $line2);
    }

    /**
     * Per AADE myDATA invoiceType enum. We only map the codes our suppliers
     * are likely to use; unknown codes are returned verbatim so they're not
     * lost.
     */
    private function mapInvoiceType(string $code): ?string
    {
        $map = [
            '1.1'  => 'Τιμολόγιο Πώλησης',
            '1.2'  => 'Τιμολόγιο Παροχής Υπηρεσιών',
            '1.3'  => 'Τιμολόγιο Πώλησης / Ενδοκοινοτικές',
            '1.4'  => 'Τιμολόγιο Πώλησης / Εξαγωγές',
            '2.1'  => 'Τιμολόγιο Παροχής Υπηρεσιών για Πιστωτικό',
            '5.1'  => 'Πιστωτικό Τιμολόγιο',
            '5.2'  => 'Πιστωτικό Τιμολόγιο Λιανικής',
            '11.1' => 'Α.Λ.Π.',
            '11.2' => 'Α.Π.Υ.',
            '11.3' => 'Απλοποιημένο Τιμολόγιο',
            '11.4' => 'Πιστωτικό Στοιχείο Λιανικής',
            '11.5' => 'Απόδειξη Λιανικής Πώλησης',
        ];
        if ($code === '') {
            return null;
        }
        return $map[$code] ?? $code;
    }

    private function mapMeasurementUnit(string $code): ?string
    {
        $map = [
            '1' => 'τμχ',
            '2' => 'kg',
            '3' => 'lt',
            '4' => 'm',
            '5' => 'm²',
            '6' => 'm³',
            '7' => 'm',
        ];
        if ($code === '') {
            return null;
        }
        return $map[$code] ?? $code;
    }
}
