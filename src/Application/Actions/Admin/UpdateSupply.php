<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Supply;
use Domain\Repositories\SuppliersRepositoryInterface;
use Domain\Repositories\SuppliesRepositoryInterface;
use Domain\Repositories\SupplyGroupsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateSupply
{
    private SuppliersRepositoryInterface $suppliersRepository;

    private SuppliesRepositoryInterface $suppliesRepository;

    private SupplyGroupsRepositoryInterface $supplyGroupsRepository;

    private Twig $twig;

    public function __construct(
    	SuppliersRepositoryInterface $suppliersRepository,
    	SuppliesRepositoryInterface $suppliesRepository,
    	SupplyGroupsRepositoryInterface $supplyGroupsRepository,
    	Twig $twig
    ) {
        $this->suppliersRepository = $suppliersRepository;
        $this->suppliesRepository = $suppliesRepository;
        $this->supplyGroupsRepository = $supplyGroupsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
	{
		$requestData = $request->getParsedBody();
		$supply = $this->suppliesRepository->findOneBy(['id' => $request->getQueryparams()['id']]);

		if ($request->getMethod() == 'POST') {
			$supplyGroup = $this->supplyGroupsRepository->findOneBy(['id' => $requestData['supplyGroup']]);
			$supplier = $this->suppliersRepository->findOneBy(['id' => $requestData['supplier']]);

			$supply->setDescription($requestData['description']);
			$supply->setIsActive(boolval($requestData['isActive']));
			$supply->setName($requestData['name']);
			$supply->setSupplier($supplier);
			$supply->setSupplyGroup($supplyGroup);
			$supply->setUnit($requestData['unit']);
			$supply->setVatPercentage(floatval($requestData['vatPercentage']));

			$customFields = [];
            if (isset($requestData['customFields'])) {
                foreach ($requestData['customFields'] as $customField) {
                	$customFields[$customField['field']] = $customField['value'];
                }
            }
            $supply->setCustomFields($customFields);

			$this->suppliesRepository->persist($supply);

			return $response->withHeader('Location', '/admin/supplies')->withStatus(302);
		}

		$suppliers = $this->suppliersRepository->findBy([], ['name' => 'asc']);
		$supplyGroups = $this->supplyGroupsRepository->findBy([], ['position' => 'asc']);
		return $this->twig->render(
			$response,
			'admin/update_supply.twig',
			[
				'suppliers' => $suppliers,
				'supply' => $supply,
				'supplyGroups' => $supplyGroups
			]
		);
	}
}