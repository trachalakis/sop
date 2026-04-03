<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTime;
use Domain\Entities\WorkShift;
use Domain\Repositories\DailyRoleSlotsRepository;
use Domain\Repositories\UsersRepository;
use Domain\Repositories\WorkShiftsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AssignWorkShift
{
    public function __construct(
        private WorkShiftsRepository $workShiftsRepository,
        private UsersRepository $usersRepository,
        private DailyRoleSlotsRepository $dailyRoleSlotsRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $body = json_decode((string) $request->getBody(), true);
        $user = $this->usersRepository->find((int) $body['userId']);
        $slot = $this->dailyRoleSlotsRepository->find((int) $body['slotId']);

        $start = new DateTime($body['date'] . ' ' . $body['slot'] . ':00');
        $end   = (clone $start)->modify('+8 hours');

        $shift = new WorkShift($user, $slot, $start, $end);
        $this->workShiftsRepository->persist($shift);

        $response->getBody()->write(json_encode([
            'ok'    => true,
            'shift' => [
                'id'     => $shift->getId(),
                'userId' => $shift->getUser()->getId(),
                'slotId' => $shift->getDailyRoleSlot()->getId(),
                'slot'   => $shift->getStart()->format('H:i'),
                'start'  => $shift->getStart()->format('H:i'),
                'end'    => $shift->getEnd()->format('H:i'),
                'hours'  => $shift->getHours(),
            ],
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
