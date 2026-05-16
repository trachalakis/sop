<?php

declare(strict_types=1);

namespace Application\Actions\Api;

use DateTimeImmutable;
use Domain\Repositories\EcrJobsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AckEcrJob
{
    public function __construct(private EcrJobsRepository $ecrJobsRepository) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        if ($request->getHeaderLine('X-Api-Key') !== ($_ENV['ECR_AGENT_API_KEY'] ?? '')) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $job = $this->ecrJobsRepository->find((int) $args['id']);
        if ($job === null) {
            $response->getBody()->write(json_encode(['error' => 'Not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $body = $request->getParsedBody();

        $newAttempts = $job->getAttempts() + 1;
        $job->setAttempts($newAttempts);
        $job->setLastAttemptedAt(new DateTimeImmutable());

        if ($newAttempts >= 5) {
            $job->setStatus('failed');
            $job->setError($body['error'] ?? $job->getError());
        } elseif (($body['status'] ?? '') === 'sent') {
            $job->setStatus('sent');
        } else {
            $job->setStatus('pending');
            $job->setError($body['error'] ?? null);
        }

        $this->ecrJobsRepository->persist($job);

        $response->getBody()->write('ok');
        return $response->withHeader('Content-Type', 'application/json');
    }
}
