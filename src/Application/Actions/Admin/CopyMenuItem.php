<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Extra;
use Domain\Entities\Ingredient;
use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemTranslation;
use Domain\Entities\Recipe;
use Domain\Enums\PriceUnit;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\MenusRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\RecipesRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CopyMenuItem
{
    public function __construct(
        private MenuItemsRepository $menuItemsRepository,
        private MenusRepository $menusRepository,
        private MenuSectionsRepository $menuSectionsRepository,
        private RecipesRepository $recipesRepository,
        private Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $sourceItem = $this->menuItemsRepository->find($request->getQueryParams()['id']);

        if ($request->getMethod() === 'POST') {
            $requestData = $request->getParsedBody();
            $targetSection = $this->menuSectionsRepository->find($requestData['menuSection']);

            // Compute next position in target section
            $existingItems = $targetSection->getMenuItems()->toArray();
            $maxPosition = empty($existingItems)
                ? 0
                : max(array_map(fn($i) => $i->getPosition(), $existingItems));

            // Clone the MenuItem
            $newItem = new MenuItem();
            $newItem->setPrice($sourceItem->getPrice());
            $newItem->setPriceUnit(PriceUnit::from($sourceItem->getPriceUnit()));
            $newItem->setIsActive(true);
            $newItem->setIsArchived(false);
            $newItem->setIsDrink($sourceItem->getIsDrink());
            $newItem->setTrackAvailableQuantity($sourceItem->getTrackAvailableQuantity());
            $newItem->setAvailableQuantity($sourceItem->getAvailableQuantity());
            $newItem->setCustomFields($sourceItem->getCustomFields());
            $newItem->setPosition($maxPosition + 1);
            $newItem->setMenuSection($targetSection);
            $newItem->setPrinters(new \Doctrine\Common\Collections\ArrayCollection($sourceItem->getPrinters()->toArray()));

            // Copy translations
            $newTranslations = [];
            foreach ($sourceItem->getTranslations() as $t) {
                $newT = new MenuItemTranslation();
                $newT->setLanguage($t->getLanguage());
                $newT->setMenuItem($newItem);
                $newT->setName($t->getName());
                $newTranslations[] = $newT;
            }
            $newItem->setTranslations($newTranslations);

            // Copy item-level extras
            foreach ($sourceItem->getExtras() as $extra) {
                $newExtra = new Extra($extra->getName(), $extra->getPrice(), $newItem, null);
                $newItem->addExtra($newExtra);
            }

            $this->menuItemsRepository->persist($newItem);

            // Copy recipe
            $sourceRecipe = $this->recipesRepository->findOneBy(['menuItem' => $sourceItem]);
            if ($sourceRecipe !== null) {
                $newRecipe = new Recipe();
                $newRecipe->setMenuItem($newItem);
                $newRecipe->setName($sourceRecipe->getName());
                $newRecipe->setDuration($sourceRecipe->getDuration());
                $newRecipe->setYield($sourceRecipe->getYield());
                $newRecipe->setYieldUnit($sourceRecipe->getYieldUnit());
                $newRecipe->setComments($sourceRecipe->getComments());

                $newIngredients = [];
                foreach ($sourceRecipe->getIngredients() as $ingredient) {
                    $newIngredient = new Ingredient();
                    $newIngredient->setRecipe($newRecipe);
                    $newIngredient->setSupply($ingredient->getSupply());
                    $newIngredient->setPreparation($ingredient->getPreparation());
                    $newIngredient->setQuantity($ingredient->getQuantity());
                    $newIngredient->setUnit($ingredient->getUnit());
                    $newIngredients[] = $newIngredient;
                }
                $newRecipe->setIngredients($newIngredients);

                $this->recipesRepository->persist($newRecipe);
            }

            if (function_exists('apcu_clear_cache')) {
                apcu_clear_cache();
            }

            return $response
                ->withHeader('Location', '/admin/menu-items/update?id=' . $newItem->getId())
                ->withStatus(302);
        }

        // Build menus + sections data for the cascading selects
        $menus = $this->menusRepository->findBy([], ['name' => 'asc']);
        $menusData = [];
        foreach ($menus as $menu) {
            $sections = $this->menuSectionsRepository->findBy(['menu' => $menu], ['position' => 'asc']);
            if (empty($sections)) {
                continue;
            }
            $menusData[] = [
                'id'   => $menu->getId(),
                'name' => $menu->getName(),
                'sections' => array_map(fn($s) => [
                    'id'   => $s->getId(),
                    'name' => $s->getTranslation('el')?->getName() ?? '(χωρίς όνομα)',
                ], $sections),
            ];
        }

        return $this->twig->render($response, 'admin/copy_menu_item.twig', [
            'menuItem'  => $sourceItem,
            'menusJson' => json_encode($menusData),
        ]);
    }
}
