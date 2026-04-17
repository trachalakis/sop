<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\InvoicesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Invoices
{
    public function __construct(
        private InvoicesRepository $invoicesRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $invoices = $this->invoicesRepository->findBy([], ['scannedAt' => 'DESC']);

        return $this->twig->render($response, 'admin/invoices.twig', [
            'invoices' => $invoices,
        ]);
    }
}
