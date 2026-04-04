# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Environment

This project runs via **DDEV** (Docker-based). All PHP/Composer commands must be prefixed with `ddev`.

```bash
ddev start                    # Start the environment
ddev stop                     # Stop the environment
ddev composer install         # Install PHP dependencies
ddev composer require <pkg>   # Add a package
ddev exec php <script>        # Run a PHP script
```

The app is served at the DDEV-assigned URL (e.g., `https://sop.ddev.site`). The document root is `public/`.

There is no test suite and no build step.

## Environment Configuration

Copy `app/.env.example` to `app/.env`. DDEV sets the DB credentials automatically (`db/db/db`). The key variable is `APP_MODE=development|production` — Twig template caching is only enabled in production.

## Running Migrations

Migrations are standalone PHP scripts in `migrations/`. Run them individually:

```bash
ddev exec php migrations/<migration_file>.php
```

There is no migration runner — new migrations are added and executed manually.

## Architecture

**Request lifecycle**: `public/index.php` → Slim Router → Middleware → Action `__invoke()` → Repository/Service → Doctrine ORM → PostgreSQL.

**Key directories**:
- `app/` — Bootstrap files: `settings.php` (config), `dependencies.php` (DI bindings), `routes.php` (all routes)
- `src/Application/Actions/` — One class per route, each with a single `__invoke(Request, Response): Response` method
- `src/Domain/Entities/` — Doctrine ORM entities using PHP 8 attribute mapping
- `src/Domain/Repositories/` — Doctrine `EntityRepository` subclasses with custom query methods
- `src/Middleware/` — PSR-15 middleware: `Authentication`, `Authorization`, `Globals`, `Menus`
- `src/templates/` — Twig templates, organized by app section

**The five sub-applications** and their route prefixes:
- `/admin/*` — Back-office management (menus, orders, users, supplies, reports)
- `/orders-app/*` — Waiter interface for table orders
- `/take-out/*` — Take-away order management
- `/reservations-app/*` — Reservations and table assignment
- `/users-app/*` — Employee self-service (timekeeping, card scanning)

## Adding a New Route

1. Create an Action class in the appropriate `src/Application/Actions/<App>/` directory
2. Add the route in `app/routes.php` inside the relevant `$app->group()` block
3. If the action needs a repository or service, inject it via constructor; bind it in `app/dependencies.php` if not already autowired
4. Create the Twig template in `src/templates/<app_section>/`

## Authorization

The `Authorization` middleware checks `UserPermissionsRepository` for path-based permissions stored in the database. The `webmaster` role bypasses all checks. New routes that need restricted access require a corresponding DB entry in the permissions table.

## DI Container

PHP-DI 7. All repositories are bound in `app/dependencies.php` as:
```php
SomeRepository::class => fn($c) => $c->get(EntityManager::class)->getRepository(SomeEntity::class),
```
The current logged-in user is available via the `'SessionUser'` container key (`$_SESSION['user']`).

## Doctrine ORM

- Entities live in `src/Domain/Entities/` and use PHP 8 attributes (`#[ORM\Entity]`, `#[ORM\Column]`, etc.)
- Repositories call `$this->getEntityManager()->persist($entity)` and `->flush()` to save
- Native lazy objects are enabled; dev mode is always on (no proxy generation needed)
- A custom `DATE()` SQL function is registered for PostgreSQL date queries

## GraphQL

A single GraphQL endpoint lives at `POST /admin/graph-ql`. Schema types are in `src/Application/GraphQl/Types/` and resolvers in `src/Application/GraphQl/Resolvers/`. Query results are APCu-cached with a 12-hour TTL.

## Templates & i18n

Twig templates use a custom `|_` filter that wraps PHP's `gettext()` for translations. All user-visible strings should use this filter.
