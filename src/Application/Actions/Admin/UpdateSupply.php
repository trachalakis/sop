<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Enums\PriceUnit;
use Domain\Repositories\SuppliesRepository;
use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateSupply
{
    private SuppliesRepository $suppliesRepository;

    private SupplyGroupsRepository $supplyGroupsRepository;

    private Twig $twig;

    public function __construct(
    	SuppliesRepository $suppliesRepository,
    	SupplyGroupsRepository $supplyGroupsRepository,
    	Twig $twig
    ) {
        $this->suppliesRepository = $suppliesRepository;
        $this->supplyGroupsRepository = $supplyGroupsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
	{
		$requestData = $request->getParsedBody();
		$supply = $this->suppliesRepository->find($request->getQueryparams()['id']);

		if ($request->getMethod() == 'POST') {
			$supplyGroup = $this->supplyGroupsRepository->find($requestData['supplyGroup']);
			$supply->setName($requestData['name']);
			$supply->setSupplyGroup($supplyGroup);
            $supply->setPrice(floatval($requestData['price']));
            $supply->setPriceUnit(PriceUnit::from($requestData['priceUnit']));
            $supply->setVatRate(isset($requestData['vatRate']) && $requestData['vatRate'] !== '' ? floatval($requestData['vatRate']) : null);

			$supply->setCustomFields([]);
            if (isset($requestData['customFields'])) {
                foreach ($requestData['customFields'] as $customField) {
                    $supply->setCustomField($customField['field'], $customField['value']);
                }
            }
            
			$this->suppliesRepository->persist($supply);

			return $response->withHeader('Location', '/admin/supplies')->withStatus(302);
		}

		$supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
		return $this->twig->render(
			$response,
			'admin/update_supply.twig',
			[
				'supply' => $supply,
				'supplyGroups' => $supplyGroups,
                'priceUnits' => PriceUnit::cases()
			]
		);
	}
}