# Copy Menu Item Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Copy to…" action on a menu item that duplicates the item (including translations, extras, printers, custom fields, and its recipe with all ingredients) into a user-selected section of any menu.

**Architecture:** A single new `CopyMenuItem` action handles GET (render target-section picker form) and POST (perform the deep copy and redirect to the new item). All data needed to populate the cascading menu → section selects is passed as JSON from the action. No new repositories or migrations are needed.

**Tech Stack:** PHP 8, Slim 4, Doctrine ORM 3, Twig 3, Bootstrap 5, vanilla JS (cascading select)

---

### Task 1: Add route and dropdown entry point

**Files:**
- Modify: `app/routes.php`
- Modify: `src/templates/admin/update_menu_item.twig`

- [ ] **Step 1: Add `use` import and route in `app/routes.php`**

Add the import alongside the other menu-item imports at the top of the file:
```php
use Application\Actions\Admin\CopyMenuItem;
```

Add the route inside the `/admin` group, alongside the other `/menu-items/*` routes (around line 165):
```php
$group->map(['GET', 'POST'], '/menu-items/copy', CopyMenuItem::class);
```

- [ ] **Step 2: Add "Αντιγραφή σε…" link to the gear dropdown in `update_menu_item.twig`**

Find the existing gear dropdown `<ul class="dropdown-menu">` block (around line 43) and add the copy link as the first `<li>`:
```twig
<ul class="dropdown-menu">
    <li>
        <a class="dropdown-item" href="/admin/menu-items/copy?id={{menuItem.getId}}">
            <i class="bi bi-copy"></i> Αντιγραφή σε…
        </a>
    </li>
    <li>
        <a class="dropdown-item" href="/admin/menu-items/toggle-archive?id={{menuItem.getId}}">
            {{menuItem.getIsArchived ? 'Επαναφορά' : 'Αρχειοθέτηση'}}
        </a>
        <a class="dropdown-item confirm" href="/admin/menu-items/delete?id={{menuItem.getId}}">
            <i class="bi bi-trash"></i> Διαγραφή
        </a>
    </li>
</ul>
```

- [ ] **Step 3: Commit**

```bash
git add app/routes.php src/templates/admin/update_menu_item.twig
git commit -m "feat: add copy menu item route and dropdown entry"
```

---

### Task 2: Create CopyMenuItem action

**Files:**
- Create: `src/Application/Actions/Admin/CopyMenuItem.php`

- [ ] **Step 1: Create the action file**

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\Extra;
use Domain\Entities\Ingredient;
use Domain\Entities\MenuItem;
use Domain\Entities\MenuItemTranslation;
use Domain\Entities\Recipe;
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
            $newItem->setPriceUnit(\Domain\Enums\PriceUnit::from($sourceItem->getPriceUnit()));
            $newItem->setIsActive(true);
            $newItem->setIsArchived(false);
            $newItem->setIsDrink($sourceItem->getIsDrink());
            $newItem->setTrackAvailableQuantity($sourceItem->getTrackAvailableQuantity());
            $newItem->setAvailableQuantity($sourceItem->getAvailableQuantity());
            $newItem->setCustomFields($sourceItem->getCustomFields());
            $newItem->setPosition($maxPosition + 1);
            $newItem->setMenuSection($targetSection);
            $newItem->setPrinters($sourceItem->getPrinters()->toArray());

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
            $newRecipe = new Recipe();
            $newRecipe->setMenuItem($newItem);
            $newRecipe->setName($sourceRecipe?->getName());
            $newRecipe->setDuration($sourceRecipe?->getDuration() ?? 0);
            $newRecipe->setYield($sourceRecipe?->getYield() ?? 1);
            $newRecipe->setYieldUnit($sourceRecipe?->getYieldUnit() ?? 'item');
            $newRecipe->setComments($sourceRecipe?->getComments());

            // Copy ingredients
            if ($sourceRecipe !== null) {
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
            }

            $this->recipesRepository->persist($newRecipe);

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
```

- [ ] **Step 2: Commit**

```bash
git add src/Application/Actions/Admin/CopyMenuItem.php
git commit -m "feat: add CopyMenuItem action with full deep copy including recipe"
```

---

### Task 3: Create copy_menu_item.twig template

**Files:**
- Create: `src/templates/admin/copy_menu_item.twig`

- [ ] **Step 1: Create the template**

```twig
{% extends 'admin/skeleton.twig' %}

{% set menu = menuItem.getMenuSection.getMenu %}
{% set activeNavLink = menu.getName %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none"><i class="bi bi-house"></i></a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/menus" class="text-decoration-none">Μενού</a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/menu?id={{menu.getId}}" class="text-decoration-none">{{menu.getName}}</a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/menu-items/update?id={{menuItem.getId}}" class="text-decoration-none">
            {{menuItem.getTranslation('el').getName}}
        </a>
    </li>
</ol>

<div class="d-flex justify-content-between align-items-center pb-1 mb-3 mt-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-copy me-2"></i>
        {{'Αντιγραφή'|_}}: {{menuItem.getTranslation('el').getName}}
    </h1>
</div>

<form method="post" action="">
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">{{'Μενού'|_}}</label>
        <div class="col-sm-10">
            <select class="form-select" id="target-menu" name="targetMenu">
                <option value="">{{'— επιλέξτε μενού —'|_}}</option>
            </select>
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">{{'Τμήμα'|_}}</label>
        <div class="col-sm-10">
            <select class="form-select" id="target-section" name="menuSection" required>
                <option value="">{{'— επιλέξτε τμήμα —'|_}}</option>
            </select>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-sm-10 offset-sm-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-copy me-1"></i>{{'Αντιγραφή'|_}}
            </button>
            <a href="/admin/menu-items/update?id={{menuItem.getId}}" class="btn btn-outline-secondary ms-2">
                {{'Ακύρωση'|_}}
            </a>
        </div>
    </div>
</form>
{% endblock %}

{% block javascript %}
{{ parent() }}
<script>
    const menus = {{ menusJson|raw }};
    const menuSelect    = document.getElementById('target-menu');
    const sectionSelect = document.getElementById('target-section');

    menus.forEach(function (menu) {
        const opt = document.createElement('option');
        opt.value = menu.id;
        opt.textContent = menu.name;
        menuSelect.appendChild(opt);
    });

    menuSelect.addEventListener('change', function () {
        sectionSelect.innerHTML = '<option value="">— επιλέξτε τμήμα —</option>';
        const menu = menus.find(m => m.id == this.value);
        if (!menu) return;
        menu.sections.forEach(function (section) {
            const opt = document.createElement('option');
            opt.value = section.id;
            opt.textContent = section.name;
            sectionSelect.appendChild(opt);
        });
    });
</script>
{% endblock %}
```

- [ ] **Step 2: Commit**

```bash
git add src/templates/admin/copy_menu_item.twig
git commit -m "feat: add copy_menu_item template with cascading menu/section selects"
```

---

### Task 4: Smoke test

- [ ] **Step 1: Open an existing menu item and verify the dropdown**

Navigate to any menu item update page (`/admin/menu-items/update?id=X`). Open the gear dropdown — "Αντιγραφή σε…" should appear as the first option.

- [ ] **Step 2: Test the copy form**

Click "Αντιγραφή σε…". The form page should load showing the item name in the breadcrumb and heading. Select a menu — the section dropdown should populate. Select a section and submit.

- [ ] **Step 3: Verify the copy**

You should land on the new item's update page. Verify:
- Name(s) match the source
- Price, priceUnit, isDrink, printers match
- Extras tab shows the same item-level extras
- Recipe tab shows the same ingredients

- [ ] **Step 4: Verify the source item is unchanged**

Navigate back to the source item's update page and confirm nothing changed.
