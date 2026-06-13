<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use DateTimeImmutable;
use Domain\Enums\TakeOutRequestStatus;
use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class RejectRequest
{
    public function __construct(
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response, array $args)
    {
        $takeOutRequest = $this->takeOutRequestsRepository->find((int) $args['id']);

        if ($takeOutRequest === null) {
            $response->getBody()->write(json_encode(['error' => 'Request not found.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        if ($takeOutRequest->getStatus() !== TakeOutRequestStatus::Pending) {
            $response->getBody()->write(json_encode(['error' => 'Request is no longer pending.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $takeOutRequest->setStatus(TakeOutRequestStatus::Rejected);
        $takeOutRequest->setRespondedAt(new DateTimeImmutable());
        $this->takeOutRequestsRepository->persist($takeOutRequest);

        $response->getBody()->write('ok');
        return $response;
    }
}
