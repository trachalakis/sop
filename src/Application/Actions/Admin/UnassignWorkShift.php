<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\WorkShiftsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UnassignWorkShift
{
    public function __construct(private WorkShiftsRepository $workShiftsRepository)
    {
    }

    public function __invoke(Request $request, Response $response)
    {
        $body  = json_decode((string) $request->getBody(), true);
        $shift = $this->workShiftsRepository->find((int) $body['shiftId']);
        $this->workShiftsRepository->delete($shift);

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
