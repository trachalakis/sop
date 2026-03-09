<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Supply;
use Domain\Repositories\SuppliesRepository;
use Domain\Repositories\SupplyGroupsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateSupply
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
		if ($request->getMethod() == 'POST') {
			$requestData = $request->getParsedBody();

			$supplyGroup = $this->supplyGroupsRepository->findOneBy(['id' => $requestData['supplyGroup']]);
			$supply = new Supply;
			$supply->setName($requestData['name']);
			$supply->setSupplyGroup($supplyGroup);
			$this->suppliesRepository->persist($supply);

			return $response->withHeader('Location', '/admin/supplies')->withStatus(302);
		}

		$supplyGroups = $this->supplyGroupsRepository->findBy([], ['name' => 'asc']);
		return $this->twig->render(
			$response,
			'admin/create_supply.twig',
			[
				'supplyGroups' => $supplyGroups
			]
		);
	}
}