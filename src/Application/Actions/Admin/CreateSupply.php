<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Supply;
use Domain\Enums\PriceUnit;
use Domain\Repositories\SuppliesRepository;
use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateSupply
{
    public function __construct(
    	private SuppliesRepository $suppliesRepository,
    	private SupplyGroupsRepository $supplyGroupsRepository,
    	private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
			$requestData = $request->getParsedBody();

			$supplyGroup = $this->supplyGroupsRepository->find($requestData['supplyGroup']);
			$supply = new Supply;
			$supply->setName($requestData['name']);
			$supply->setSupplyGroup($supplyGroup);
            $supply->setPrice(floatval($requestData['price']));
            $supply->setPriceUnit(PriceUnit::from($requestData['priceUnit']));
            $supply->setVatRate(isset($requestData['vatRate']) && $requestData['vatRate'] !== '' ? floatval($requestData['vatRate']) : null);
			$this->suppliesRepository->persist($supply);

			return $response->withHeader('Location', '/admin/supplies')->withStatus(302);
		}

		$supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
		return $this->twig->render(
			$response,
			'admin/create_supply.twig',
			[
				'supplyGroups' => $supplyGroups,
                'priceUnits' => PriceUnit::cases()
			]
		);
	}
}