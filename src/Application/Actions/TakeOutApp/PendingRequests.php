<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PendingRequests
{
    public function __construct(
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $requests = [];
        foreach ($this->takeOutRequestsRepository->findPending() as $takeOutRequest) {
            $entries = [];
            foreach ($takeOutRequest->getEntries() as $entry) {
                $extras = [];
                foreach ($entry->getExtras() as $extra) {
                    $extras[] = $extra->getName();
                }
                $entries[] = [
                    'name' => $entry->getMenuItem()->getTranslation('el')?->getName() ?? '',
                    'quantity' => $entry->getQuantity(),
                    'extras' => $extras,
                    'price' => $entry->getPrice(),
                ];
            }

            $requests[] = [
                'id' => $takeOutRequest->getId(),
                'createdAt' => $takeOutRequest->getCreatedAt()->format('Y-m-d H:i:s'),
                'customerName' => $takeOutRequest->getCustomerName(),
                'customerPhone' => $takeOutRequest->getCustomerPhone(),
                'notes' => $takeOutRequest->getNotes(),
                'entries' => $entries,
                'total' => $takeOutRequest->getTotal(),
            ];
        }

        $response->getBody()->write(json_encode($requests));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
