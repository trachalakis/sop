<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use DateTimeImmutable;
use Domain\Entities\OrderEntryGroup;
use Domain\Repositories\OrderEntriesRepository;
use Domain\Repositories\OrderEntryGroupsRepository;
use Domain\Repositories\OrdersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class TransferOrderEntry
{
    private OrderEntriesRepository $orderEntriesRepository;

    private OrderEntryGroupsRepository $orderEntryGroupsRepository;

    private OrdersRepository $ordersRepository;

    public function __construct(
        OrderEntriesRepository $orderEntriesRepository,
        OrderEntryGroupsRepository $orderEntryGroupsRepository,
        OrdersRepository $ordersRepository
    ) {
        $this->orderEntriesRepository = $orderEntriesRepository;
        $this->orderEntryGroupsRepository = $orderEntryGroupsRepository;
        $this->ordersRepository = $ordersRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $requestData = json_decode(file_get_contents("php://input"), true);

        $orderEntry = $this->orderEntriesRepository->find($requestData['orderEntryId']);
        $sourceOrder = $orderEntry->getOrder();
        $targetOrder = $this->ordersRepository->find($requestData['targetOrderId']);

        $sourceTableName = $sourceOrder->getTable() ? $sourceOrder->getTable()->getName() : 'take-out';

        $orderEntryGroup = new OrderEntryGroup;
        $orderEntryGroup->setCreatedAt(new DateTimeImmutable);
        $orderEntryGroup->setNotes('Μεταφορά από τραπέζι ' . $sourceTableName);
        $orderEntryGroup->setOrder($targetOrder);

        $this->orderEntryGroupsRepository->persist($orderEntryGroup);

        $orderEntry->setOrder($targetOrder);
        $orderEntry->setOrderEntryGroup($orderEntryGroup);

        $this->orderEntriesRepository->persist($orderEntry);

        $response->getBody()->write('ok');
        return $response;
    }
}
