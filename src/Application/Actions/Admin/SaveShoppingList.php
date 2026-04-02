<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTimeImmutable;
use Domain\Entities\ShoppingList;
use Domain\Entities\ShoppingListEntry;
use Domain\Repositories\ShoppingListsRepository;
use Domain\Repositories\SuppliesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SaveShoppingList
{
    public function __construct(
        private ShoppingListsRepository $shoppingListsRepository,
        private SuppliesRepository $suppliesRepository
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $entries = $body['entries'] ?? [];
        $notes = isset($body['notes']) ? trim((string) $body['notes']) : null;

        $targetDate = $this->getTargetDate();
        $dateStr = $targetDate->format('Y-m-d');

        $shoppingList = $this->shoppingListsRepository->findByDate($targetDate);
        $isNew = $shoppingList === null;

        if ($isNew) {
            $shoppingList = new ShoppingList();
            $shoppingList->setDate(new DateTimeImmutable($dateStr));
            $shoppingList->setCreatedAt(new DateTimeImmutable());
        }

        $shoppingList->setUpdatedAt(new DateTimeImmutable());
        $shoppingList->setNotes($notes !== '' ? $notes : null);
        $shoppingList->clearEntries();

        foreach ($entries as $entryData) {
            $supply = $this->suppliesRepository->find((int) $entryData['supplyId']);
            if ($supply === null) {
                continue;
            }

            $entry = new ShoppingListEntry();
            $entry->setShoppingList($shoppingList);
            $entry->setSupply($supply);
            $entry->setQuantity((float) $entryData['quantity']);
            $entry->setUnitCost(isset($entryData['unitCost']) ? (float) $entryData['unitCost'] : null);
            $shoppingList->addEntry($entry);
        }

        $this->shoppingListsRepository->persist($shoppingList);

        $response->getBody()->write(json_encode([
            'success' => true,
            'date' => $dateStr,
            'isNew' => $isNew,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getTargetDate(): DateTimeImmutable
    {
        $now = new DateTimeImmutable();
        if ((int) $now->format('H') < 5) {
            return $now;
        }
        return $now->modify('+1 day');
    }
}
