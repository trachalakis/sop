<?php

declare(strict_types=1);

namespace Application\Actions\Api;

use Domain\Repositories\EcrJobsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class EcrJobs
{
    public function __construct(private EcrJobsRepository $ecrJobsRepository) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $configuredKey = $_ENV['ECR_AGENT_API_KEY'] ?? '';
        if ($configuredKey === '' || $request->getHeaderLine('X-Api-Key') !== $configuredKey) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $jobs = $this->ecrJobsRepository->findPending();

        $result = [];
        foreach ($jobs as $job) {
            $entries = [];

            foreach ($job->getOrder()->getOrderEntries() as $orderEntry) {
                $menuItem = $orderEntry->getMenuItem();
                $name = mb_substr(
                    $menuItem->getTranslation('el')?->getName()
                    ?? $menuItem->getTranslation('en')?->getName()
                    ?? '',
                    0,
                    20
                );

                $isKg = $menuItem->getPriceUnit() === 'kg';
                $qty = $isKg
                    ? number_format(($orderEntry->getWeight() ?? 1000) / 1000.0, 3, '.', '')
                    : number_format($orderEntry->getQuantity(), 3, '.', '');

                $entries[] = [
                    'name'             => $name,
                    'quantity'         => $qty,
                    'unitPrice'        => number_format($orderEntry->getMenuItemPrice(), 2, '.', ''),
                    'fiscalDepartment' => $menuItem->getFiscalDepartment(),
                ];

                foreach ($orderEntry->getOrderEntryExtras() as $extra) {
                    $entries[] = [
                        'name'             => mb_substr($extra->getName(), 0, 20),
                        'quantity'         => '1.000',
                        'unitPrice'        => number_format($extra->getPrice(), 2, '.', ''),
                        'fiscalDepartment' => $menuItem->getFiscalDepartment(),
                    ];
                }
            }

            $result[] = [
                'id'      => $job->getId(),
                'orderId' => $job->getOrder()->getId(),
                'entries' => $entries,
            ];
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
