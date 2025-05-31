<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SupplyGroupsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Supplies
{
    private SupplyGroupsRepositoryInterface $supplyGroupsRepository;

    private Twig $twig;

    public function __construct(
        SupplyGroupsRepositoryInterface $supplyGroupsRepository,
        Twig $twig
    ) {
        $this->twig = $twig;
        $this->supplyGroupsRepository = $supplyGroupsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $supplyGroups = $this->supplyGroupsRepository->findBy([], ['position' => 'asc']);

        return $this->twig->render($response, 'admin/supplies.twig', ['supplyGroups' => $supplyGroups]);
    }
}