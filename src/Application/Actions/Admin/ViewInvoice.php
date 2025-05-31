<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Invoice;
use Domain\Repositories\InvoicesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ViewInvoice
{
	private $twig;

    private $invoicesRepository;

    public function __construct(Twig $twig, InvoicesRepositoryInterface $invoicesRepository)
    {
        $this->twig = $twig;
        $this->invoicesRepository = $invoicesRepository;
    }

	public function __invoke(Request $request, Response $response)
	{
		$invoice = $this->invoicesRepository->findOneBy(['id' => $request->getQueryParams()['id']]);

		return $this->twig->render($response, 'admin/view_invoice.twig', ['invoice' => $invoice]);
	}
}