# Shopping Lists App — Design Spec

**Date:** 2026-04-05

## Goal

Extract the shopping lists feature from the admin section into a standalone app following the same pattern as `orders-app` and `reservations-app`. No new functionality is added. A new `shopping_lists_manager` role (added in parallel) will be able to access the app without needing any admin access.

## Routing

Remove the two existing routes from the `/admin` group:

```
GET  /admin/shopping-lists/update
POST /admin/shopping-lists/save
```

Add a new route group at `/shopping-lists-app` with `Authentication` + `Authorization` middleware (no `Menus` middleware):

```
GET  /shopping-lists-app/update  → Application\Actions\ShoppingListsApp\UpdateShoppingList
POST /shopping-lists-app/save    → Application\Actions\ShoppingListsApp\SaveShoppingList
```

## Action Classes

Move both action classes to a new directory:

- `src/Application/Actions/Admin/UpdateShoppingList.php` → `src/Application/Actions/ShoppingListsApp/UpdateShoppingList.php`
- `src/Application/Actions/Admin/SaveShoppingList.php` → `src/Application/Actions/ShoppingListsApp/SaveShoppingList.php`

Update namespace from `Application\Actions\Admin` to `Application\Actions\ShoppingListsApp` in both files. No logic changes.

## Templates

Create directory `src/templates/shopping_lists_app/`. Move the template content from `src/templates/admin/update_shopping_list.twig` into `src/templates/shopping_lists_app/update_shopping_list.twig`.

Change the extends directive:
- From: `{% extends 'admin/skeleton.twig' %}`
- To: `{% extends 'app_skeleton.twig' %}`

Remove the `{% set activeNavLink = 'shopping-lists' %}` line (not applicable outside admin). All Vue.js logic, supply group rendering, printer selection, and receipt canvas code remains unchanged.

Update `UpdateShoppingList.php` to render the new template path `shopping_lists_app/update_shopping_list.twig`.

## Permissions Migration

Update `migrations/add_shopping_lists_manager_permissions.php` (not yet run) to use the new paths:

- `shopping-lists-app/update` (was `admin/shopping-lists/update`)
- `shopping-lists-app/save` (was `admin/shopping-lists/save`)

## Admin Nav Cleanup

Remove the shopping lists link from `src/templates/admin/skeleton.twig` — the feature no longer lives in admin.

## Files Changed

| File | Change |
|------|--------|
| `app/routes.php` | Remove from admin group; add new `/shopping-lists-app` group |
| `src/Application/Actions/Admin/UpdateShoppingList.php` | Delete (moved) |
| `src/Application/Actions/Admin/SaveShoppingList.php` | Delete (moved) |
| `src/Application/Actions/ShoppingListsApp/UpdateShoppingList.php` | New (moved + namespace update + template path update) |
| `src/Application/Actions/ShoppingListsApp/SaveShoppingList.php` | New (moved + namespace update) |
| `src/templates/admin/update_shopping_list.twig` | Delete (moved) |
| `src/templates/shopping_lists_app/update_shopping_list.twig` | New (moved + extends change) |
| `src/templates/admin/skeleton.twig` | Remove shopping lists nav link |
| `migrations/add_shopping_lists_manager_permissions.php` | Update paths to new routes |

## Out of Scope

- No new pages or functionality
- No changes to `ShoppingListsRepository`, `SaveShoppingList` business logic, or the Vue.js frontend code
