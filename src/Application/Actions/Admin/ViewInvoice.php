<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\InvoicesRepository;
use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ViewInvoice
{
    public function __construct(
        private InvoicesRepository $invoicesRepository,
        private SuppliesRepository $suppliesRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $invoice = $this->invoicesRepository->find((int) ($params['id'] ?? 0));

        if ($invoice === null) {
            return $response->withStatus(404);
        }

        $supplies = $this->suppliesRepository->findBy([], ['name' => 'ASC']);

        return $this->twig->render($response, 'admin/view_invoice.twig', [
            'invoice'  => $invoice,
            'supplies' => $supplies,
        ]);
    }
}
