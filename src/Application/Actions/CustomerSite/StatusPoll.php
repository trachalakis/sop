<?php

declare(strict_types=1);

namespace Application\Actions\CustomerSite;

use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

final class StatusPoll
{
    public function __construct(
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response, array $args)
    {
        $takeOutRequest = $this->takeOutRequestsRepository->findOneByToken($args['token']);

        if ($takeOutRequest === null) {
            throw new HttpNotFoundException($request);
        }

        $response->getBody()->write(json_encode([
            'status' => $takeOutRequest->getStatus()->value,
            'etaMinutes' => $takeOutRequest->getEtaMinutes(),
            'ticketNumber' => $takeOutRequest->getOrder()?->getTicketNumber(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
