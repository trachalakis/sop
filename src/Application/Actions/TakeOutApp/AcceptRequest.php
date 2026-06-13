<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use Application\Services\TakeOutOrderFactory;
use DateTimeImmutable;
use Domain\Enums\TakeOutRequestStatus;
use Domain\Repositories\TakeOutRequestsRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AcceptRequest
{
    public function __construct(
        private TakeOutRequestsRepository $takeOutRequestsRepository,
        private UsersRepository $usersRepository,
        private TakeOutOrderFactory $takeOutOrderFactory
    ) {}

    public function __invoke(Request $request, Response $response, array $args)
    {
        $takeOutRequest = $this->takeOutRequestsRepository->find((int) $args['id']);

        if ($takeOutRequest === null) {
            return $this->fail($response, 'Request not found.', 404);
        }
        if ($takeOutRequest->getStatus() !== TakeOutRequestStatus::Pending) {
            return $this->fail($response, 'Request is no longer pending.', 409);
        }

        $requestData = json_decode((string) $request->getBody(), true);
        $etaMinutes = (int) ($requestData['etaMinutes'] ?? 0);
        if ($etaMinutes < 5 || $etaMinutes > 180) {
            return $this->fail($response, 'Invalid ETA.', 422);
        }

        foreach ($takeOutRequest->getEntries() as $entry) {
            $menuItem = $entry->getMenuItem();
            if ($menuItem->getTrackAvailableQuantity()
                && ($menuItem->getAvailableQuantity() ?? 0) < $entry->getQuantity()
            ) {
                return $this->fail(
                    $response,
                    'Not enough quantity for: ' . ($menuItem->getTranslation('el')?->getName() ?? $menuItem->getId()),
                    409
                );
            }
        }

        $entrySpecs = [];
        foreach ($takeOutRequest->getEntries() as $entry) {
            $extras = [];
            foreach ($entry->getExtras() as $extra) {
                $extras[] = [
                    'name' => $extra->getName(),
                    'price' => $extra->getPrice(),
                ];
            }

            $entrySpecs[] = [
                'menuItem' => $entry->getMenuItem(),
                'menuItemPrice' => $entry->getMenuItemPrice(),
                'quantity' => $entry->getQuantity(),
                'timing' => $entry->getMenuItem()->getIsDrink() ? 6 : 1,
                'notes' => '',
                'weight' => null,
                'extras' => $extras,
            ];
        }

        $waiter = $this->usersRepository->find($_SESSION['user']->getId());
        $order = $this->takeOutOrderFactory->create($entrySpecs, $takeOutRequest->getNotes(), $waiter, false);

        $takeOutRequest->setStatus(TakeOutRequestStatus::Accepted);
        $takeOutRequest->setEtaMinutes($etaMinutes);
        $takeOutRequest->setRespondedAt(new DateTimeImmutable());
        $takeOutRequest->setOrder($order);
        $this->takeOutRequestsRepository->persist($takeOutRequest);

        $response->getBody()->write(json_encode([
            'ticketNumber' => $order->getTicketNumber(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function fail(Response $response, string $message, int $status): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
