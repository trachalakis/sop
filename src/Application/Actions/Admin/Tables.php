<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\TablesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Tables
{
    public function __construct(
        private Twig $twig,
        private TablesRepository $tablesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $tables = $this->tablesRepository->findBy([], ['isActive' => 'desc', 'position' => 'asc']);

        return $this->twig->render(
            $response, 
            'admin/tables.twig', 
            ['tables' => $tables]
        );
    }
}