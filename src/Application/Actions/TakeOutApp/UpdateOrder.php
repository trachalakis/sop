<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use DateTimeImmutable;
use Domain\Entities\OrderEntry;
use Domain\Entities\OrderEntryCancellation;
use Domain\Entities\OrderEntryGroup;
use Domain\Entities\OrderEntryExtra;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\OrderEntriesRepository;
use Domain\Repositories\OrderEntryCancellationsRepository;
use Domain\Repositories\OrderEntryGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateOrder
{
    public function __construct(
        private MenuItemsRepository $menuItemsRepository,
        private OrdersRepository $ordersRepository,
        private OrderEntriesRepository $orderEntriesRepository,
        private OrderEntryCancellationsRepository $orderEntryCancellationsRepository,
        private OrderEntryGroupsRepository $orderEntryGroupsRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $id = $request->getQueryParams()['id'];

        if ($request->getMethod() == 'GET') {
            $order = $this->ordersRepository->find($id);

            if ($order->getStatus() != 'OPEN') {
                return $response->withHeader('Location', '/take-out/')->withStatus(302);
            }
        }

        if ($request->getMethod() == 'POST') {
            $requestData = json_decode(file_get_contents('php://input'), true);

            $order = $this->ordersRepository->find($requestData['order']['id']);

            foreach ($requestData['order']['orderEntryGroups'] as $orderEntryGroup) {
                foreach ($orderEntryGroup['orderEntries'] as $entry) {
                    $orderEntry = $this->orderEntriesRepository->find($entry['id']);
                    $orderEntry->setQuantity(intval($entry['quantity']));
                    $orderEntry->setDiscount(floatval($entry['discount']));
                    $orderEntry->setDiscountReason($entry['discountReason']);

                    $this->orderEntriesRepository->persist($orderEntry);

                    foreach ($entry['orderEntryCancellations'] as $entryCancellation) {
                        if (!isset($entryCancellation['id'])) {
                            $cancellation = new OrderEntryCancellation();
                            $cancellation->setCreatedAt(new DateTimeImmutable);
                            $cancellation->setCancellationReason($entryCancellation['cancellationReason']);
                            $cancellation->setOrderEntry($orderEntry);

                            $this->orderEntryCancellationsRepository->persist($cancellation);

                            //if an order entry is cancelled reset its discount
                            $orderEntry->setDiscount(0);
                            $orderEntry->setDiscountReason('');
                        }
                    }
                }
            }

            $this->ordersRepository->persist($order);

            $response->getBody()->write('ok');
            return $response;
        }

        return $this->twig->render($response, 'take_out_app/update_order.twig', ['orderId' => $id]);
    }
}
