<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\ShoppingListsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class ShoppingLists
{
	private ShoppingListsRepositoryInterface $shoppingListsRepository;

    private Twig $twig;

    public function __construct(
        ShoppingListsRepositoryInterface $shoppingListsRepository,
        Twig $twig
    ) {
        $this->shoppingListsRepository = $shoppingListsRepository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request, Response $response)
    {
        $shoppingLists = $this->shoppingListsRepository->findBy([], ['date' => 'desc']);

        return $this->twig->render($response, 'admin/shopping_lists.twig', ['shoppingLists' => $shoppingLists]);
    }
}