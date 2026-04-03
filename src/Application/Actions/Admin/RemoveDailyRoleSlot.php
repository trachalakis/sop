<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\DailyRoleSlotsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class RemoveDailyRoleSlot
{
    public function __construct(private DailyRoleSlotsRepository $dailyRoleSlotsRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
        $body = json_decode((string) $request->getBody(), true);
        $slot = $this->dailyRoleSlotsRepository->find((int) $body['slotId']);
        $this->dailyRoleSlotsRepository->delete($slot);

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
