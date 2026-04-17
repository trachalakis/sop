<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTime;
use Domain\Entities\Invoice;
use Domain\Entities\InvoiceEntry;
use Domain\Entities\Supplier;
use Domain\Repositories\InvoicesRepository;
use Domain\Repositories\SupplyAliasesRepository;
use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ConfirmInvoice
{
    public function __construct(
        private InvoicesRepository $invoicesRepository,
        private SupplyAliasesRepository $supplyAliasesRepository,
        private SuppliersRepository $suppliersRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $scan = $_SESSION['invoice_scan'] ?? null;
        if ($scan === null) {
            return $response->withHeader('Location', '/admin/invoices/scan')->withStatus(302);
        }

        $data = $request->getParsedBody();

        // Resolve supplier
        $supplier = null;
        if (!empty($data['new_supplier_name'])) {
            // Create new supplier inline
            $supplier = new Supplier();
            $supplier->setName(trim($data['new_supplier_name']));
            $supplier->setTelephone(!empty($data['new_supplier_telephone']) ? trim($data['new_supplier_telephone']) : null);
            $supplier->setDetails(!empty($scan['supplier_details']) ? $scan['supplier_details'] : null);
            $this->suppliersRepository->persist($supplier);
        } else {
            $supplier = $this->suppliersRepository->find((int) $data['supplier_id']);
        }

        if ($supplier === null) {
            return $response->withHeader('Location', '/admin/invoices/review')->withStatus(302);
        }

        // Backfill supplier details if empty
        if ($supplier->getDetails() === null && !empty($scan['supplier_details'])) {
            $supplier->setDetails($scan['supplier_details']);
            $this->suppliersRepository->persist($supplier);
        }

        // Build Invoice
        $invoice = new Invoice();
        $invoice->setSupplier($supplier);
        $invoice->setDate(new DateTime($data['date']));
        $invoice->setInvoiceNumber(!empty($data['invoice_number']) ? $data['invoice_number'] : null);
        // scannedAt is auto-set to now() in Invoice::__construct()

        // Build entries
        foreach ($data['entries'] as $entryData) {
            $entry = new InvoiceEntry();
            $entry->setDescription($entryData['description']);
            $entry->setQuantity((float) $entryData['quantity']);
            $entry->setUnitPrice((float) $entryData['unit_price']);
            $extras = isset($entryData['extras']) ? json_decode($entryData['extras'], true) : null;
            $entry->setExtras($extras ?: null);

            // Auto-link supply alias if one exists for this supplier + description
            $alias = $this->supplyAliasesRepository->findBySupplierAndDescription(
                $supplier->getId(),
                $entryData['description']
            );
            $entry->setSupplyAlias($alias);

            $invoice->addEntry($entry);
        }

        $this->invoicesRepository->persist($invoice);

        unset($_SESSION['invoice_scan']);

        return $response->withHeader('Location', '/admin/invoices/view?id=' . $invoice->getId())->withStatus(302);
    }
}
