<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Application\Services\AadeInvoiceFetcherService;
use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Views\Twig;

final class ScanInvoiceQr
{
    public function __construct(
        private AadeInvoiceFetcherService $fetcher,
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if ($request->getMethod() === 'POST') {
            $body = $request->getParsedBody();
            $url  = trim((string) ($body['url'] ?? ''));

            if ($url === '') {
                return $this->twig->render($response, 'admin/scan_invoice_qr.twig', [
                    'error' => 'Παρακαλώ δώστε ένα URL.',
                ]);
            }

            try {
                $parsed = $this->fetcher->fetch($url);
            } catch (RuntimeException $e) {
                return $this->twig->render($response, 'admin/scan_invoice_qr.twig', [
                    'error' => 'Σφάλμα: ' . $e->getMessage(),
                    'url'   => $url,
                ]);
            }

            // Prefer AFM match; fall back to case-insensitive name match.
            $afm = $parsed['supplier_details']['afm'] ?? null;
            $matchedSupplierId = null;
            if ($afm !== null) {
                $byAfm = $this->suppliersRepository->findOneBy(['afm' => $afm]);
                if ($byAfm !== null) {
                    $matchedSupplierId = $byAfm->getId();
                }
            }
            if ($matchedSupplierId === null) {
                foreach ($this->suppliersRepository->findAll() as $supplier) {
                    if (mb_strtolower($supplier->getName()) === mb_strtolower($parsed['supplier_name'] ?? '')) {
                        $matchedSupplierId = $supplier->getId();
                        break;
                    }
                }
            }

            $_SESSION['invoice_scan'] = [
                'supplier_name'    => $parsed['supplier_name'] ?? '',
                'supplier_details' => $parsed['supplier_details'] ?? [],
                'supplier_id'      => $matchedSupplierId,
                'invoice_number'   => $parsed['invoice_number'] ?? null,
                'series'           => $parsed['series'] ?? null,
                'document_type'    => $parsed['document_type'] ?? null,
                'date'             => $parsed['date'] ?? null,
                'net_total'        => $parsed['net_total'] ?? null,
                'vat_total'        => $parsed['vat_total'] ?? null,
                'gross_total'      => $parsed['gross_total'] ?? null,
                'entries'          => $parsed['entries'] ?? [],
                'mark'             => $parsed['mark'] ?? null,
                'mydata_url'       => $parsed['mydata_url'] ?? null,
            ];

            return $response->withHeader('Location', '/admin/invoices/review')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/scan_invoice_qr.twig', []);
    }
}
