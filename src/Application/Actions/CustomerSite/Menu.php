<?php

declare(strict_types=1);

namespace Application\Actions\CustomerSite;

use Domain\Enums\MenuType;
use Domain\Repositories\MenusRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Menu
{
    public function __construct(
        private Twig $twig,
        private MenusRepository $menusRepository
    ) {}

    public function __invoke(Request $request, Response $response)
    {
        $menu = $this->menusRepository->findOneBy([
            'menuType' => MenuType::TakeOut,
            'isActive' => true,
        ]);

        $sections = [];
        if ($menu !== null) {
            foreach ($menu->getMenuSections() as $menuSection) {
                if (!$menuSection->getIsActive()) {
                    continue;
                }

                $items = [];
                foreach ($menuSection->getActiveMenuItems() as $menuItem) {
                    // Weight-priced items need a scale; the customer total must be exact
                    if ($menuItem->getPriceUnit() === 'kg') {
                        continue;
                    }

                    $extras = [];
                    foreach ($menuItem->getAllExtras() as $extra) {
                        $extras[] = [
                            'name' => $extra->getName(),
                            'price' => $extra->getPrice(),
                        ];
                    }

                    $items[] = [
                        'id' => $menuItem->getId(),
                        'price' => $menuItem->getPrice(),
                        'soldOut' => $menuItem->getTrackAvailableQuantity()
                            && ($menuItem->getAvailableQuantity() ?? 0) <= 0,
                        'names' => [
                            'el' => $menuItem->getTranslation('el')?->getName() ?? '',
                            'en' => $menuItem->getTranslation('en')?->getName() ?? '',
                        ],
                        'extras' => $extras,
                    ];
                }

                if (count($items) === 0) {
                    continue;
                }

                $sections[] = [
                    'id' => $menuSection->getId(),
                    'names' => [
                        'el' => $menuSection->getTranslation('el')?->getName() ?? '',
                        'en' => $menuSection->getTranslation('en')?->getName() ?? '',
                    ],
                    'items' => $items,
                ];
            }
        }

        return $this->twig->render($response, 'customer_site/menu.twig', [
            'sectionsJson' => json_encode($sections),
        ]);
    }
}
