<?php

declare(strict_types=1);

namespace Application\Actions\CustomerSite;

use DateTimeImmutable;
use Domain\Entities\TakeOutRequest;
use Domain\Entities\TakeOutRequestEntry;
use Domain\Entities\TakeOutRequestEntryExtra;
use Domain\Enums\MenuType;
use Domain\Enums\TakeOutRequestStatus;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\MenusRepository;
use Domain\Repositories\TakeOutRequestsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

final class SubmitRequest
{
    public function __construct(
        private MenusRepository $menusRepository,
        private MenuItemsRepository $menuItemsRepository,
        private TakeOutRequestsRepository $takeOutRequestsRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!is_array($data) || !empty($data['website'])) {
            return $this->fail($response, 'Invalid request.', 400);
        }

        $name = trim($data['customerName'] ?? '');
        $phone = trim($data['customerPhone'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $entries = $data['entries'] ?? [];

        if ($name === '' || mb_strlen($name) > 100) {
            return $this->fail($response, 'Please enter your name.', 422);
        }
        if (!preg_match('/^\+?[0-9 ]{8,20}$/', $phone)) {
            return $this->fail($response, 'Please enter a valid phone number.', 422);
        }
        if (!is_array($entries) || count($entries) === 0 || count($entries) > 30) {
            return $this->fail($response, 'Your cart is empty.', 422);
        }

        if ($this->takeOutRequestsRepository->countPendingByPhone($phone) >= 3) {
            return $this->fail($response, 'Too many pending orders for this phone number.', 429);
        }

        $menu = $this->menusRepository->findOneBy([
            'menuType' => MenuType::TakeOut,
            'isActive' => true,
        ]);
        if ($menu === null) {
            return $this->fail($response, 'Ordering is currently unavailable.', 503);
        }

        $takeOutRequest = new TakeOutRequest();
        $takeOutRequest->setCreatedAt(new DateTimeImmutable());
        $takeOutRequest->setToken(Uuid::uuid4()->toString());
        $takeOutRequest->setCustomerName($name);
        $takeOutRequest->setCustomerPhone($phone);
        $takeOutRequest->setNotes(mb_substr($notes, 0, 500));
        $takeOutRequest->setStatus(TakeOutRequestStatus::Pending);
        $takeOutRequest->setEtaMinutes(null);
        $takeOutRequest->setRespondedAt(null);
        $takeOutRequest->setOrder(null);

        $requestEntries = [];
        foreach ($entries as $entry) {
            $quantity = (int) ($entry['quantity'] ?? 0);
            if ($quantity < 1 || $quantity > 20) {
                return $this->fail($response, 'Invalid quantity.', 422);
            }

            $menuItem = $this->menuItemsRepository->find((int) ($entry['menuItemId'] ?? 0));
            if ($menuItem === null
                || !$menuItem->getIsActive()
                || $menuItem->getPriceUnit() === 'kg'
                || $menuItem->getMenuSection()->getMenu()->getId() !== $menu->getId()
            ) {
                return $this->fail($response, 'Some items in your cart are no longer available.', 409);
            }
            if ($menuItem->getTrackAvailableQuantity() && ($menuItem->getAvailableQuantity() ?? 0) < $quantity) {
                return $this->fail($response, 'Some items in your cart are sold out.', 409);
            }

            $requestEntry = new TakeOutRequestEntry();
            $requestEntry->setRequest($takeOutRequest);
            $requestEntry->setMenuItem($menuItem);
            $requestEntry->setMenuItemPrice($menuItem->getPrice());
            $requestEntry->setQuantity($quantity);

            $availableExtras = [];
            foreach ($menuItem->getAllExtras() as $extra) {
                $availableExtras[$extra->getName()] = $extra->getPrice();
            }

            $requestExtras = [];
            foreach ($entry['extras'] ?? [] as $extraName) {
                if (!is_string($extraName) || !array_key_exists($extraName, $availableExtras)) {
                    return $this->fail($response, 'Some extras are no longer available.', 409);
                }
                $requestExtra = new TakeOutRequestEntryExtra();
                $requestExtra->setEntry($requestEntry);
                $requestExtra->setName($extraName);
                $requestExtra->setPrice($availableExtras[$extraName]);
                $requestExtras[] = $requestExtra;
            }
            $requestEntry->setExtras($requestExtras);

            $requestEntries[] = $requestEntry;
        }

        $takeOutRequest->setEntries($requestEntries);
        $this->takeOutRequestsRepository->persist($takeOutRequest);

        $response->getBody()->write(json_encode([
            'statusUrl' => '/order/status/' . $takeOutRequest->getToken(),
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function fail(Response $response, string $message, int $status): Response
    {
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
