<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Application\Services\InvoiceParserService;
use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Slim\Views\Twig;

final class ScanInvoice
{
    public function __construct(
        private InvoiceParserService $parser,
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if ($request->getMethod() === 'POST') {
            $files = $request->getUploadedFiles();

            /** @var UploadedFileInterface|null $file */
            $file = $files['invoice'] ?? null;

            if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
                return $this->twig->render($response, 'admin/scan_invoice.twig', [
                    'error' => 'Δεν ανέβηκε αρχείο ή παρουσιάστηκε σφάλμα.',
                ]);
            }

            $mediaType = $file->getClientMediaType() ?: 'image/jpeg';
            $imageData = (string) $file->getStream();

            try {
                $parsed = $this->parser->parse($imageData, $mediaType);
            } catch (RuntimeException $e) {
                return $this->twig->render($response, 'admin/scan_invoice.twig', [
                    'error' => 'Σφάλμα ανάλυσης τιμολογίου: ' . $e->getMessage(),
                ]);
            }

            // Attempt case-insensitive supplier match
            $allSuppliers = $this->suppliersRepository->findAll();
            $matchedSupplierId = null;
            foreach ($allSuppliers as $supplier) {
                if (mb_strtolower($supplier->getName()) === mb_strtolower($parsed['supplier_name'] ?? '')) {
                    $matchedSupplierId = $supplier->getId();
                    break;
                }
            }

            $_SESSION['invoice_scan'] = [
                'supplier_name'    => $parsed['supplier_name'] ?? '',
                'supplier_details' => $parsed['supplier_details'] ?? [],
                'supplier_id'      => $matchedSupplierId,
                'invoice_number'   => $parsed['invoice_number'] ?? null,
                'date'             => $parsed['date'] ?? null,
                'entries'          => $parsed['entries'] ?? [],
            ];

            return $response->withHeader('Location', '/admin/invoices/review')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/scan_invoice.twig', []);
    }
}
