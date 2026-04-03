<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTime;
use DateInterval;
use Domain\Repositories\DailyRoleSlotsRepository;
use Domain\Repositories\RolesRepository;
use Domain\Repositories\UsersRepository;
use Domain\Repositories\WorkShiftsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class WorkSchedule
{
    public function __construct(
        private Twig $twig,
        private WorkShiftsRepository $workShiftsRepository,
        private UsersRepository $usersRepository,
        private RolesRepository $rolesRepository,
        private DailyRoleSlotsRepository $dailyRoleSlotsRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $date = empty($queryParams['date'])
            ? new DateTime()
            : new DateTime($queryParams['date']);

        $users  = $this->usersRepository->findBy(['isActive' => true], ['fullName' => 'asc']);
        $roles  = $this->rolesRepository->findBy([], ['label' => 'asc']);
        $slots  = $this->dailyRoleSlotsRepository->findByDate($date);
        $shifts = $this->workShiftsRepository->findByDate($date);

        $slotsData = array_values(array_map(fn($s) => [
            'id'              => $s->getId(),
            'roleId'          => $s->getRole()->getId(),
            'roleLabel'       => $s->getRole()->getLabel(),
            'minimumManHours' => $s->getRole()->getMinimumManHours(),
        ], $slots));

        $shiftsData = array_values(array_map(fn($s) => [
            'id'     => $s->getId(),
            'userId' => $s->getUser()->getId(),
            'slotId' => $s->getDailyRoleSlot()->getId(),
            'slot'   => $s->getStart()->format('H:i'),
            'start'  => $s->getStart()->format('H:i'),
            'end'    => $s->getEnd()->format('H:i'),
            'hours'  => $s->getHours(),
        ], $shifts));

        $usersData = array_values(array_map(fn($u) => [
            'id'       => $u->getId(),
            'fullName' => $u->getFullName(),
        ], $users));

        $rolesData = array_values(array_map(fn($r) => [
            'id'    => $r->getId(),
            'label' => $r->getLabel(),
        ], $roles));

        return $this->twig->render($response, 'admin/work_schedule.twig', [
            'slotsJson'  => json_encode($slotsData),
            'shiftsJson' => json_encode($shiftsData),
            'usersJson'  => json_encode($usersData),
            'rolesJson'  => json_encode($rolesData),
            'date'       => $date,
            'prev'       => (clone $date)->sub(new DateInterval('P1D')),
            'next'       => (clone $date)->add(new DateInterval('P1D')),
        ]);
    }
}
