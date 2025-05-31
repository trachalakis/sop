<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\InvoicesRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Invoices
{
	private Twig $twig;

    private InvoicesRepositoryInterface $invoicesRepository;

    public function __construct(
        Twig $twig,
        InvoicesRepositoryInterface $invoicesRepository
    ) {
        $this->twig = $twig;
        $this->invoicesRepository = $invoicesRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $invoices = $this->invoicesRepository->findBy([], ['createdAt' => 'desc']);

        return $this->twig->render($response, 'admin/invoices.twig', ['invoices' => $invoices]);
    }
}