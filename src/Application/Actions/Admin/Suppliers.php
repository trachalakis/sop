<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SuppliersRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Suppliers
{
    private SuppliersRepositoryInterface $suppliersRepository;

    private Twig $twig;

    public function __construct(
        SuppliersRepositoryInterface $suppliersRepository,
        Twig $twig
    ) {
        $this->twig = $twig;
        $this->suppliersRepository = $suppliersRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $suppliers = $this->suppliersRepository->findBy([], ['name' => 'asc']);

        return $this->twig->render($response, 'admin/suppliers.twig', ['suppliers' => $suppliers]);
    }
}