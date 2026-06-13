<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use Application\Services\TakeOutOrderFactory;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateOrder
{
    public function __construct(
        private Twig $twig,
        private MenuItemsRepository $menuItemsRepository,
        private UsersRepository $usersRepository,
        private TakeOutOrderFactory $takeOutOrderFactory
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() === 'POST') {
            $requestData = json_decode(file_get_contents('php://input'), true);

            $waiter = $this->usersRepository->find($_SESSION['user']->getId());

            $entrySpecs = [];
            foreach ($requestData['orderEntries'] as $entry) {
                $menuItem = $this->menuItemsRepository->find($entry['menuItem']['id']);

                $extras = [];
                foreach ($entry['orderEntryExtras'] as $extra) {
                    $extras[] = [
                        'name' => $extra['name'],
                        'price' => floatval($extra['price']),
                    ];
                }

                $entrySpecs[] = [
                    'menuItem' => $menuItem,
                    'menuItemPrice' => $menuItem->getPrice(),
                    'quantity' => intval($entry['quantity']),
                    'timing' => intval($entry['timing'] ?? 1),
                    'notes' => $entry['notes'] ?? '',
                    'weight' => isset($entry['weight']) ? intval($entry['weight']) : null,
                    'extras' => $extras,
                ];
            }

            $order = $this->takeOutOrderFactory->create(
                $entrySpecs,
                $requestData['notes'],
                $waiter,
                !empty($requestData['markAsPaid'])
            );

            $response->getBody()->write(json_encode([
                'ticketNumber' => $order->getTicketNumber(),
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        return $this->twig->render($response, 'take_out_app/create_order.twig');
    }
}
