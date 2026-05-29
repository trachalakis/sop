<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Services\InvoiceProviders\EpsilonNetInvoiceProvider;
use Application\Services\InvoiceProviders\GenericInvoiceProvider;
use Application\Services\InvoiceProviders\ImpactInvoiceProvider;
use Application\Services\InvoiceProviders\InvoiceProvider;
use DOMDocument;
use DOMXPath;
use RuntimeException;

/**
 * Fetches an invoice from a QR-code URL. The URL may point directly at
 * mydatapi.aade.gr or at a provider page (e.g. einvoice.impact.gr) which
 * embeds a link to the AADE verification page; we transparently follow it.
 *
 * Returns the same shape as InvoiceParserService::parse(), plus a "mark" key
 * containing the AADE unique invoice id (Μοναδικός Αριθμός Καταχώρισης) so
 * callers can surface it during review.
 */
final class AadeInvoiceFetcherService
{
    private const AADE_HOST = 'mydatapi.aade.gr';

    /** @var InvoiceProvider[] Walked in order; the generic fallback MUST be last. */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            new ImpactInvoiceProvider(),
            new EpsilonNetInvoiceProvider(),
            new GenericInvoiceProvider(),
        ];
    }

    public function fetch(string $url): array
    {
        $url = trim($url);
        if (!preg_match('~^https?://~i', $url)) {
            throw new RuntimeException('Invalid URL');
        }

        // Fast path: a provider that has its own structured data source can
        // produce the parsed invoice without us even fetching the URL.
        foreach ($this->providers as $provider) {
            if (!$provider->supports($url)) {
                continue;
            }
            $direct = $provider->parseDirectly($url);
            if ($direct !== null) {
                return $direct;
            }
            // First supporting provider gets a shot at parseDirectly only;
            // its extractAadeUrl chance comes during the HTML walk below
            // (so it's not skipped when parseDirectly returns null).
            break;
        }

        $html    = $this->httpGet($url);
        $aadeUrl = $this->isAadeUrl($url) ? $url : null;

        // The QR may point at AADE directly, at a provider that redirects to
        // AADE (curl follows the redirect), or at a provider that renders a
        // landing page with a link to AADE. Detect the AADE page by content
        // rather than URL so all three cases collapse to one branch.
        if (!$this->looksLikeAadePage($html)) {
            $providerTried = null;
            foreach ($this->providers as $provider) {
                if (!$provider->supports($url)) {
                    continue;
                }
                $providerTried = $provider->name();
                $extracted = $provider->extractAadeUrl($html);
                if ($extracted !== null) {
                    $aadeUrl = $extracted;
                    break;
                }
            }
            if ($aadeUrl === null) {
                throw new RuntimeException(
                    'Could not locate AADE link in this provider page'
                    . ($providerTried !== null ? " ({$providerTried})" : '')
                    . '. If the invoice has a second QR labelled "myDATA", scan that one instead.'
                );
            }
            $html = $this->httpGet($aadeUrl);
            if (!$this->looksLikeAadePage($html)) {
                throw new RuntimeException('AADE page did not return the expected invoice structure');
            }
        }

        $parsed = $this->parse($html);
        $parsed['mydata_url'] = $aadeUrl;
        return $parsed;
    }

    private function isAadeUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        return str_ends_with($host, self::AADE_HOST);
    }

    private function looksLikeAadePage(string $html): bool
    {
        return str_contains($html, "id='tableDiakinisis'")
            || str_contains($html, 'id="tableDiakinisis"')
            || (str_contains($html, 'id="bname"') && str_contains($html, 'id="vatnumber"'));
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
            throw new RuntimeException("Failed to fetch {$url} (HTTP {$status})");
        }
        return (string) $body;
    }

    private function parse(string $html): array
    {
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp  = new DOMXPath($dom);

        $inputVal = function (string $id) use ($xp): string {
            $node = $xp->query("//input[@id='{$id}']")->item(0);
            return $node ? trim((string) $node->getAttribute('value')) : '';
        };

        $textOf = function (string $id) use ($xp): string {
            $node = $xp->query("//*[@id='{$id}']")->item(0);
            return $node ? trim($node->textContent) : '';
        };

        $bname     = $inputVal('bname');
        $vatnumber = $inputVal('vatnumber');
        $iaddress  = $inputVal('iaddress');
        $bactivity = $inputVal('bactivity');
        $tdate     = $inputVal('tdate');
        $saa       = $inputVal('saa');
        $snumber   = $inputVal('snumber');
        $dtype     = $inputVal('dtype');
        $tamount   = $this->parseNumber($inputVal('tamount'));
        $namount   = $this->parseNumber($inputVal('namount'));
        $vatTotal  = $this->parseNumber($inputVal('vat'));

        if ($bname === '' && $vatnumber === '' && $saa === '') {
            throw new RuntimeException('AADE page did not contain expected invoice fields');
        }

        $date = $this->normaliseDate($tdate);
        $mark = $this->extractMark($textOf('tMark'), $html);

        $entries  = [];
        $rows     = $xp->query("//table[@id='tableDiakinisis']//tr[position()>1]");
        $lineIdx  = 0;
        foreach ($rows as $tr) {
            $tds = $xp->query('./td', $tr);
            if ($tds->length < 7) {
                continue;
            }

            $aa          = trim($tds->item(0)->textContent);
            $code        = trim($tds->item(1)->textContent);
            $description = trim($tds->item(2)->textContent);
            $unit        = trim($tds->item(3)->textContent);
            $quantity    = $this->parseNumber($tds->item(4)->textContent);
            $netValue    = $this->parseNumber($tds->item(5)->textContent);
            $vatAmount   = $this->parseNumber($tds->item(6)->textContent);

            if ($description === '' || $quantity <= 0) {
                continue;
            }

            $unitPrice  = round($netValue / $quantity, 4);
            $vatRate    = $netValue > 0 ? (int) round(($vatAmount / $netValue) * 100) : 0;
            $lineNumber = ctype_digit($aa) ? (int) $aa : ++$lineIdx;

            $entries[] = [
                'description'   => $description,
                'quantity'      => $quantity,
                'unit_price'    => $unitPrice,
                'supplier_code' => $code !== '' ? $code : null,
                'unit'          => $unit !== '' ? $unit : null,
                'vat_amount'    => $vatAmount,
                'vat_rate'      => $vatRate,
                'line_number'   => $lineNumber,
                'extras'        => [
                    'line_total' => $netValue,
                    'discounts'  => [],
                ],
            ];
        }

        return [
            'supplier_name'    => $bname,
            'supplier_details' => [
                'afm'      => $vatnumber !== '' ? $vatnumber : null,
                'doy'      => null,
                'address'  => $iaddress !== '' ? $iaddress : null,
                'email'    => null,
                'website'  => null,
                'activity' => $bactivity !== '' ? $bactivity : null,
            ],
            'invoice_number' => $saa !== '' ? $saa : null,
            'series'         => $snumber !== '' ? $snumber : null,
            'document_type'  => $dtype !== '' ? $dtype : null,
            'date'           => $date,
            'net_total'      => $namount > 0 ? $namount : null,
            'vat_total'      => $vatTotal > 0 ? $vatTotal : null,
            'gross_total'    => $tamount > 0 ? $tamount : null,
            'entries'        => $entries,
            'mark'           => $mark,
        ];
    }

    private function normaliseDate(string $dmy): ?string
    {
        if (!preg_match('~^(\d{1,2})/(\d{1,2})/(\d{4})$~', $dmy, $m)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }

    private function parseNumber(string $text): float
    {
        $t = trim($text);
        if ($t === '') {
            return 0.0;
        }
        if (str_contains($t, ',')) {
            $t = str_replace('.', '', $t);
            $t = str_replace(',', '.', $t);
        }
        return (float) $t;
    }

    /**
     * The MARK appears in the tMark cell as raw text after a commented-out
     * input. If the cell text is empty, fall back to scanning the raw HTML.
     */
    private function extractMark(string $cellText, string $html): ?string
    {
        if (preg_match('~(\d{10,})~', $cellText, $m)) {
            return $m[1];
        }
        if (preg_match('~id=["\']tMark["\'].*?(\d{10,})~s', $html, $m)) {
            return $m[1];
        }
        return null;
    }
}
