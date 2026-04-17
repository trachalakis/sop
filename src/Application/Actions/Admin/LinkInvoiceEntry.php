<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\SupplyAlias;
use Domain\Repositories\InvoiceEntriesRepository;
use Domain\Repositories\SupplyAliasesRepository;
use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LinkInvoiceEntry
{
    public function __construct(
        private InvoiceEntriesRepository $invoiceEntriesRepository,
        private SupplyAliasesRepository $supplyAliasesRepository,
        private SuppliesRepository $suppliesRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $entry = $this->invoiceEntriesRepository->find((int) $data['invoice_entry_id']);
        if ($entry === null) {
            return $response->withStatus(404);
        }

        $supply = $this->suppliesRepository->find((int) $data['supply_id']);
        if ($supply === null) {
            return $response->withStatus(404);
        }

        $supplier = $entry->getInvoice()->getSupplier();
        $description = $entry->getDescription();
        $invoiceId = $entry->getInvoice()->getId();

        // Find or create the SupplyAlias
        $alias = $this->supplyAliasesRepository->findBySupplierAndDescription($supplier->getId(), $description);
        if ($alias === null) {
            $alias = new SupplyAlias();
            $alias->setSupply($supply);
            $alias->setSupplier($supplier);
            $alias->setDescription($description);
            $this->supplyAliasesRepository->persist($alias);
        }

        // Backfill all InvoiceEntry rows with the same supplier + description
        $this->invoiceEntriesRepository->linkAllBySupplierAndDescription($supplier->getId(), $description, $alias);

        return $response->withHeader('Location', '/admin/invoices/view?id=' . $invoiceId)->withStatus(302);
    }
}
