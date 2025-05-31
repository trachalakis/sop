<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\PriceListsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class PriceLists
{
	private PriceListsRepositoryInterface $priceListsRepository;

	private Twig $twig;

    public function __construct(
        PriceListsRepositoryInterface $priceListsRepository,
        Twig $twig
    ) {
        $this->twig = $twig;
        $this->priceListsRepository = $priceListsRepository;
    }

    public function __invoke(Request $request, Response $response)
    {
        $priceLists = $this->priceListsRepository->findBy([], ['name' => 'asc']);

        return $this->twig->render($response, 'admin/price_lists.twig', ['priceLists' => $priceLists]);
    }
}