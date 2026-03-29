<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SortSupplyGroups
{
    public function __construct(
        private SupplyGroupsRepository $supplyGroupsRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $ids = json_decode(file_get_contents('php://input'), true);
        $position = 1;

        foreach ($ids as $id) {
            $supplyGroup = $this->supplyGroupsRepository->find($id);
            $supplyGroup->setPosition($position++);
            $this->supplyGroupsRepository->persist($supplyGroup);
        }

        $response->getBody()->write('ok');
        return $response;
    }
}
