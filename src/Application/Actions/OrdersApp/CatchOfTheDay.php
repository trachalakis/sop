<?php

declare(strict_types=1);

namespace Application\Actions\OrdersApp;

use Domain\Repositories\MenuItemsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CatchOfTheDay
{
	private MenuItemsRepositoryInterface $menuItemsRepository;

    private Twig $twig;

    public function __construct(
        MenuItemsRepositoryInterface $menuItemsRepository,
        Twig $twig
    ) {
        $this->menuItemsRepository = $menuItemsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
    	//$id = $request->getQueryParams()['id'];
    	$menuItems = $this->menuItemsRepository->findBy(['isPricePerKg' => true]);

    	//if ($order->getStatus() != 'OPEN') {
    	//	return $response->withHeader('Location', '/orders-app/')->withStatus(302);
    	//}

    	return $this->twig->render($response, 'orders_app/catch_of_the_day.twig', ['menuItems' => $menuItems]);
    }
}