<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use DateTimeImmutable;
use Domain\Entities\SupplyPriceHistory;
use Domain\Enums\PriceUnit;
use Domain\Repositories\SuppliesRepository;
use Domain\Repositories\SupplyGroupsRepository;
use Domain\Repositories\SupplyPriceHistoryRepository;
use Domain\Repositories\SuppliersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateSupply
{
    public function __construct(
    	private SuppliesRepository $suppliesRepository,
    	private SupplyGroupsRepository $supplyGroupsRepository,
    	private SupplyPriceHistoryRepository $supplyPriceHistoryRepository,
    	private SuppliersRepository $suppliersRepository,
    	private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
	{
		$requestData = $request->getParsedBody();
		$supply = $this->suppliesRepository->find($request->getQueryparams()['id']);

		if ($request->getMethod() == 'POST') {
			$supplyGroup = $this->supplyGroupsRepository->find($requestData['supplyGroup']);
			$supply->setName($requestData['name']);
			$supply->setSupplyGroup($supplyGroup);

            $newPrice = floatval($requestData['price']);
            if ($supply->getPrice() !== $newPrice) {
                $history = new SupplyPriceHistory;
                $history->setSupply($supply);
                $history->setPrice($newPrice);
                $history->setValidFrom(new DateTimeImmutable);
                $this->supplyPriceHistoryRepository->record($history);
            }

            $supply->setPrice($newPrice);
            $supply->setPriceUnit(PriceUnit::from($requestData['priceUnit']));
            $supply->setVatRate(isset($requestData['vatRate']) && $requestData['vatRate'] !== '' ? floatval($requestData['vatRate']) : null);

			$supply->setCustomFields([]);
            if (isset($requestData['customFields'])) {
                foreach ($requestData['customFields'] as $customField) {
                    $supply->setCustomField($customField['field'], $customField['value']);
                }
            }

            $supplierId = $requestData['supplier'] ?? '';
            $supply->setSupplier($supplierId !== '' ? $this->suppliersRepository->find((int)$supplierId) : null);

			$this->suppliesRepository->persist($supply);

			return $response->withHeader('Location', '/admin/supplies')->withStatus(302);
		}

		$supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
		$suppliers = $this->suppliersRepository->findBy([], ['name' => 'ASC']);

        $priceHistory = $supply->getPriceHistory()->toArray();
        usort($priceHistory, fn($a, $b) => $a->getValidFrom() <=> $b->getValidFrom());

		return $this->twig->render(
			$response,
			'admin/update_supply.twig',
			[
				'supply' => $supply,
				'supplyGroups' => $supplyGroups,
                'priceUnits' => PriceUnit::cases(),
                'priceHistory' => $priceHistory,
                'suppliers' => $suppliers,
			]
		);
	}
}