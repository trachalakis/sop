<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Supply;
use Domain\Repositories\SuppliersRepository;
use Domain\Repositories\SuppliesRepository;
use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateSupply
{
    private SuppliersRepository $suppliersRepository;

    private SuppliesRepository $suppliesRepository;

    private SupplyGroupsRepository $supplyGroupsRepository;

    private Twig $twig;

    public function __construct(
    	SuppliersRepository $suppliersRepository,
    	SuppliesRepository $suppliesRepository,
    	SupplyGroupsRepository $supplyGroupsRepository,
    	Twig $twig
    ) {
        $this->suppliersRepository = $suppliersRepository;
        $this->suppliesRepository = $suppliesRepository;
        $this->supplyGroupsRepository = $supplyGroupsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
	{
		if ($request->getMethod() == 'POST') {
			$requestData = $request->getParsedBody();

			$supplier = $this->suppliersRepository->findOneBy(['id' => $requestData['supplier']]);
			$supplyGroup = $this->supplyGroupsRepository->findOneBy(['id' => $requestData['supplyGroup']]);
			$supply = new Supply;
			$supply->setIsActive(true);
			//$supply->setDescription($requestData['description']);
			$supply->setName($requestData['name']);
			$supply->setUnit($requestData['unit']);
			//$supply->setVatPercentage(floatval($requestData['vatPercentage']));
			$supply->setSupplier($supplier);
			$supply->setSupplyGroup($supplyGroup);
			$this->suppliesRepository->persist($supply);

			return $response->withHeader('Location', '/admin/supplies/update?id=' . $supply->getId())->withStatus(302);
		}

		$suppliers = $this->suppliersRepository->findBy([], ['name' => 'asc']);
		$supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
		return $this->twig->render(
			$response,
			'admin/create_supply.twig',
			[
				'suppliers' => $suppliers,
				'supplyGroups' => $supplyGroups
			]
		);
	}
}