<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Suppliers
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $suppliers = $this->suppliersRepository->findBy([], ['name' => 'ASC']);
        return $this->twig->render($response, 'admin/suppliers.twig', [
            'suppliers' => $suppliers,
        ]);
    }
}
