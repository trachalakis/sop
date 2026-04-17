<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ReviewInvoice
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $scan = $_SESSION['invoice_scan'] ?? null;

        if ($scan === null) {
            return $response->withHeader('Location', '/admin/invoices/scan')->withStatus(302);
        }

        $suppliers = $this->suppliersRepository->findBy([], ['name' => 'ASC']);

        return $this->twig->render($response, 'admin/review_invoice.twig', [
            'scan'      => $scan,
            'suppliers' => $suppliers,
        ]);
    }
}
