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

        // Duplicate-MARK guard: if this invoice has already been imported,
        // redirect to the existing record instead of creating a second one.
        $mark = $scan['mark'] ?? null;
        if ($mark !== null && $mark !== '') {
            $existing = $this->invoicesRepository->findOneBy(['mark' => $mark]);
            if ($existing !== null) {
                unset($_SESSION['invoice_scan']);
                return $response
                    ->withHeader('Location', '/admin/invoices/view?id=' . $existing->getId() . '&dup=1')
                    ->withStatus(302);
            }
        }

        $data = $request->getParsedBody();

        // Resolve supplier
        $supplier = null;
        if (!empty($data['new_supplier_name'])) {
            $supplier = new Supplier();
            $supplier->setName(trim($data['new_supplier_name']));
            $supplier->setTelephone(!empty($data['new_supplier_telephone']) ? trim($data['new_supplier_telephone']) : null);
            $supplier->setDetails(!empty($scan['supplier_details']) ? $scan['supplier_details'] : null);
            $supplier->setAfm($scan['supplier_details']['afm'] ?? null);
            $this->suppliersRepository->persist($supplier);
        } else {
            $supplier = $this->suppliersRepository->find((int) $data['supplier_id']);
        }

        if ($supplier === null) {
            return $response->withHeader('Location', '/admin/invoices/review')->withStatus(302);
        }

        // Backfill supplier details / AFM if empty on the existing record
        if ($supplier->getDetails() === null && !empty($scan['supplier_details'])) {
            $supplier->setDetails($scan['supplier_details']);
        }
        if ($supplier->getAfm() === null && !empty($scan['supplier_details']['afm'])) {
            $supplier->setAfm($scan['supplier_details']['afm']);
        }
        $this->suppliersRepository->persist($supplier);

        // Build Invoice
        $invoice = new Invoice();
        $invoice->setSupplier($supplier);
        $invoice->setDate(new DateTime(!empty($data['date']) ? $data['date'] : 'today'));
        $invoice->setInvoiceNumber(!empty($data['invoice_number']) ? $data['invoice_number'] : null);
        $invoice->setSeries($scan['series'] ?? null);
        $invoice->setDocumentType($scan['document_type'] ?? null);
        $invoice->setMark($mark !== '' ? $mark : null);
        $invoice->setNetTotal(isset($scan['net_total']) ? (float) $scan['net_total'] : null);
        $invoice->setVatTotal(isset($scan['vat_total']) ? (float) $scan['vat_total'] : null);
        $invoice->setGrossTotal(isset($scan['gross_total']) ? (float) $scan['gross_total'] : null);
        // scannedAt is auto-set to now() in Invoice::__construct()

        // Index scan entries by description so we can recover line metadata
        // (vat_amount, supplier_code, etc.) that the review form doesn't expose.
        $scanByDescription = [];
        foreach (($scan['entries'] ?? []) as $e) {
            $scanByDescription[$e['description'] ?? ''] = $e;
        }

        foreach (($data['entries'] ?? []) as $i => $entryData) {
            $entry = new InvoiceEntry();
            $entry->setDescription($entryData['description']);
            $entry->setQuantity((float) $entryData['quantity']);
            $grossPrice = (float) $entryData['unit_price'];
            $entry->setUnitPrice($grossPrice);

            $extras = isset($entryData['extras']) ? json_decode($entryData['extras'], true) : null;
            $extras = is_array($extras) ? $extras : null;
            $entry->setExtras($extras);

            // Compute effective unit price after all stacked discounts
            $discounts = $extras['discounts'] ?? [];
            if (!empty($discounts)) {
                $effectivePrice = $grossPrice;
                foreach ($discounts as $d) {
                    $effectivePrice *= (1 - (float) $d / 100);
                }
                $entry->setEffectiveUnitPrice($effectivePrice);
            }

            // Pull AADE-sourced line metadata (or AI-sourced fallbacks from extras).
            $orig = $scanByDescription[$entryData['description']] ?? [];
            $entry->setSupplierCode($orig['supplier_code'] ?? ($extras['supplier_code'] ?? null));
            $entry->setUnit($orig['unit'] ?? ($extras['unit'] ?? null));
            $entry->setVatAmount(isset($orig['vat_amount']) ? (float) $orig['vat_amount'] : null);
            $entry->setVatRate(isset($orig['vat_rate']) ? (int) $orig['vat_rate'] : (isset($extras['vat_rate']) ? (int) $extras['vat_rate'] : null));
            $entry->setLineNumber($orig['line_number'] ?? ($i + 1));

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
