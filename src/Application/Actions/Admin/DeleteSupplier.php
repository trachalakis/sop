<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteSupplier
{
    public function __construct(
        private SuppliersRepository $suppliersRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $supplier = $this->suppliersRepository->find($request->getQueryParams()['id']);
        $this->suppliersRepository->delete($supplier);
        return $response->withHeader('Location', '/admin/suppliers')->withStatus(302);
    }
}
