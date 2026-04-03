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

            if (count($requestData['newOrderEntryGroup']['orderEntries']) > 0) {
                $orderEntryGroup = new OrderEntryGroup;
                $orderEntryGroup->setCreatedAt(new DateTimeImmutable);
                $orderEntryGroup->setNotes($requestData['newOrderEntryGroup']['notes']);
                $orderEntryGroup->setOrder($order);

                $this->orderEntryGroupsRepository->persist($orderEntryGroup);

                foreach ($requestData['newOrderEntryGroup']['orderEntries'] as $entry) {
                    $menuItem = $this->menuItemsRepository->find($entry['menuItem']['id']);

                    if ($menuItem->getTrackAvailableQuantity()) {
                        $menuItem->setAvailableQuantity($menuItem->getAvailableQuantity() - intval($entry['quantity']));
                        $this->menuItemsRepository->persist($menuItem);

                        if (function_exists('apcu_clear_cache')) {
                            apcu_clear_cache();
                        }
                    }

                    $orderEntry = new OrderEntry;
                    $orderEntry->setMenuItem($menuItem);
                    $orderEntry->setMenuItemPrice($menuItem->getPrice());
                    $orderEntry->setQuantity(intval($entry['quantity']));
                    $orderEntry->setIsPaid(false);
                    if ($menuItem->getPriceUnit() == 'kg') {
                        $orderEntry->setWeight(intval($entry['weight']));
                    } else {
                        $orderEntry->setWeight(null);
                    }
                    $orderEntry->setDiscount(intval($entry['discount']));
                    $orderEntry->setOrder($order);
                    $orderEntry->setFamily(intval($entry['family']));
                    $orderEntry->setTiming(intval($entry['timing']));
                    $orderEntry->setNotes($entry['notes']);

                    $orderEntryExtras = [];
                    foreach ($entry['orderEntryExtras'] as $extra) {
                        $orderEntryExtra = new OrderEntryExtra;
                        $orderEntryExtra->setName($extra['name']);
                        $orderEntryExtra->setPrice(floatval($extra['price']));
                        $orderEntryExtra->setOrderEntry($orderEntry);
                        $orderEntryExtras[] = $orderEntryExtra;
                    }
                    $orderEntry->setOrderEntryExtras($orderEntryExtras);
                    $orderEntry->setOrderEntryGroup($orderEntryGroup);

                    $this->orderEntriesRepository->persist($orderEntry);
                }
            }

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
