<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Supplier;
use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateSupplier
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $supplier = new Supplier();
            $supplier->setName($data['name']);
            $supplier->setTelephone($data['telephone'] !== '' ? $data['telephone'] : null);
            $this->suppliersRepository->persist($supplier);
            return $response->withHeader('Location', '/admin/suppliers')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/create_supplier.twig', []);
    }
}
