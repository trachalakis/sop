<?php

declare(strict_types=1);

namespace Application\Actions\TakeOutApp;

use DateTimeImmutable;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\OrderEntriesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Payment
{
    public function __construct(
        private OrdersRepository $ordersRepository,
        private OrderEntriesRepository $orderEntriesRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $requestData = json_decode(file_get_contents('php://input'), true);

        $order = $this->ordersRepository->find($requestData['orderId']);

        foreach ($order->getOrderEntries() as $orderEntry) {
            $orderEntry->setIsPaid(true);
            $this->orderEntriesRepository->persist($orderEntry);
        }

        $order->setStatus('PAID');
        $order->setPaidAt(new DateTimeImmutable());
        $this->ordersRepository->persist($order);

        $response->getBody()->write('ok');
        return $response;
    }
}
