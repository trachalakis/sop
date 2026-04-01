<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Supplies
{
    public function __construct(
        private SupplyGroupsRepository $supplyGroupsRepository,
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $supplyGroups = $this->supplyGroupsRepository->findBy([], ['position' => 'asc', 'name' => 'asc']);

        return $this->twig->render(
            $response, 
            'admin/supplies.twig', 
            ['supplyGroups' => $supplyGroups]
        );
    }
}