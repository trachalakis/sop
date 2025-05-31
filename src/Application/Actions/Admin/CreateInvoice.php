<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Invoice;
use Domain\Entities\Supplier;
use Domain\Entities\Supply;
use Domain\Entities\InvoiceEntry;
use Domain\Repositories\SuppliersRepositoryInterface;
use Domain\Repositories\SuppliesRepositoryInterface;
use Domain\Repositories\VatClassesRepositoryInterface;
use Domain\Repositories\InvoicesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateInvoice
{
	private $twig;

    private $suppliersRepository;

    private $suppliesRepository;

    private $invoicesRepository;

    public function __construct(
        Twig $twig,
        SuppliersRepositoryInterface $suppliersRepository,
        SuppliesRepositoryInterface $suppliesRepository,
        InvoicesRepositoryInterface $invoicesRepository
    ) {
        $this->twig = $twig;
        $this->suppliersRepository = $suppliersRepository;
        $this->suppliesRepository = $suppliesRepository;
        $this->invoicesRepository = $invoicesRepository;
    }

    public function __invoke(Request $request, Response $response)
	{
        if ($request->getMethod() == 'POST') {
            $invoiceData = json_decode(file_get_contents('php://input'), true);
            $invoice = new Invoice;
            $invoice->setCreatedAt(new \Datetime);
            $invoice->setUpdatedAt(new \Datetime);
            $invoice->setType($invoiceData['type']);
            $invoice->setDate(new \Datetime($invoiceData['date']));
            $invoice->setInvoiceNumber($invoiceData['invoiceNumber']);
            $invoice->setComments($invoiceData['comments']);
            $invoice->setTotal(floatval($invoiceData['total']));
            $invoice->setVat(floatval($invoiceData['vat']));

            $supplier = $this->suppliersRepository->findOneBy(['name' => trim($invoiceData['supplier'])]);
            if ($supplier == null) {
                $supplier = new Supplier;
                $supplier->setName(trim($invoiceData['supplier']));
            }
            $invoice->setSupplier($supplier);

            $invoiceEntries = [];
            foreach ($invoiceData['invoiceEntries'] as $entry) {
                if (isset($entry['supply']) && strlen(trim($entry['supply'])) > 0) {

	                $supplyName = trim($entry['supply']);
                    $supply = $this->suppliesRepository->findOneBy(['name' => $supplyName]);
                    if ($supply == null) {
                        $supply = new Supply;
                        $supply->setName($supplyName);
                        $supply->setUnit($entry['unit']);
                        $supply->setVatPercentage($entry['vatPercentage']);
                    }

	                $invoiceEntry = new InvoiceEntry;
	                $invoiceEntry->setSupply($supply);
	                $invoiceEntry->setQuantity(floatval($entry['quantity']));
	                $invoiceEntry->setPrice(floatval($entry['price']));
	                $invoiceEntry->setUnit($entry['unit']);
	                $invoiceEntry->setVat(round(floatval($entry['price']) * ($entry['vatPercentage'] / 100), 2));
	                $invoiceEntry->setVatPercentage($entry['vatPercentage']);
	                $invoiceEntry->setInvoice($invoice);

	                $invoiceEntries[] = $invoiceEntry;
	            }
            }
            $invoice->setInvoiceEntries($invoiceEntries);

            $this->invoicesRepository->persist($invoice);

            $response->getBody()->write('ok');
            return $response;
        }

        return $this->twig->render($response, 'admin/create_invoice.twig');
	}
}