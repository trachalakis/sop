<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTime;
use Domain\Entities\DailyRoleSlot;
use Domain\Repositories\DailyRoleSlotsRepository;
use Domain\Repositories\RolesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AddDailyRoleSlot
{
    public function __construct(
        private DailyRoleSlotsRepository $dailyRoleSlotsRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $body = json_decode((string) $request->getBody(), true);
        $role = $this->rolesRepository->find((int) $body['roleId']);
        $date = new DateTime($body['date']);

        $slot = new DailyRoleSlot($date, $role);
        $this->dailyRoleSlotsRepository->persist($slot);

        $response->getBody()->write(json_encode([
            'ok'   => true,
            'slot' => [
                'id'              => $slot->getId(),
                'roleId'          => $role->getId(),
                'roleLabel'       => $role->getLabel(),
                'minimumManHours' => $role->getMinimumManHours(),
            ],
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
