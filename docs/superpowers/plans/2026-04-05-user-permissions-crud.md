# User Permissions CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a full admin UI to create, read, update, and delete `UserPermission` records.

**Architecture:** Four Action classes + three Twig templates following the existing Roles/Users CRUD pattern. No new repository methods or DI bindings are needed — `UserPermissionsRepository` already has `persist()` and `delete()`, and both repositories are already bound in `app/dependencies.php`.

**Tech Stack:** PHP 8, Slim 4, Doctrine ORM, Twig 3, Bootstrap 5, Bootbox (confirm dialogs)

---

### Task 1: Permissions list action + template + route

**Files:**
- Create: `src/Application/Actions/Admin/Permissions.php`
- Create: `src/templates/admin/permissions.twig`
- Modify: `app/routes.php`

- [ ] **Step 1: Create the Permissions action**

Create `src/Application/Actions/Admin/Permissions.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RolesRepository;
use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Permissions
{
    public function __construct(
        private Twig $twig,
        private UserPermissionsRepository $userPermissionsRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $permissions = $this->userPermissionsRepository->findAll();

        $rolesMap = [];
        foreach ($this->rolesRepository->findBy([], ['label' => 'asc']) as $role) {
            $rolesMap[$role->getName()] = $role->getLabel();
        }

        return $this->twig->render($response, 'admin/permissions.twig', [
            'permissions' => $permissions,
            'rolesMap' => $rolesMap,
        ]);
    }
}
```

- [ ] **Step 2: Create the permissions list template**

Create `src/templates/admin/permissions.twig`:

```twig
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'permissions' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none">
            <i class="bi bi-house"></i>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/permissions" class="text-decoration-none">Άδειες</a>
    </li>
</ol>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1>Άδειες</h1>
    <div class="btn-toolbar">
        <a href="/admin/permissions/create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i>
        </a>
    </div>
</div>
<table class="table table-responsive table-striped">
    <thead>
        <tr>
            <th>Μονοπάτι (regex)</th>
            <th>Επιτρεπόμενοι ρόλοι</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        {% for permission in permissions %}
            <tr>
                <td>
                    <a class="text-decoration-none" href="/admin/permissions/update?id={{ permission.getId }}">
                        <code>{{ permission.getPath }}</code>
                    </a>
                </td>
                <td>
                    {% for roleName in permission.getAllowedRoles %}
                        {{ rolesMap[roleName] ?? roleName }}{% if not loop.last %}, {% endif %}
                    {% endfor %}
                </td>
                <td class="text-end">
                    <a href="/admin/permissions/delete?id={{ permission.getId }}" class="btn btn-sm btn-outline-danger confirm">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
        {% endfor %}
    </tbody>
</table>
{% endblock %}
```

- [ ] **Step 3: Add the list route to `app/routes.php`**

Add the use statement at the top of the file with the other Admin use statements:

```php
use Application\Actions\Admin\Permissions;
```

Add the route inside the `/admin` group, after the roles block:

```php
$group->get('/permissions', Permissions::class);
```

- [ ] **Step 4: Verify**

Start the DDEV environment if not already running:
```bash
ddev start
```

Visit `https://sop.ddev.site/admin/permissions` — you should see the "Άδειες" page with the table (empty if no permissions exist in the DB yet, or populated if they do).

- [ ] **Step 5: Commit**

```bash
git add src/Application/Actions/Admin/Permissions.php src/templates/admin/permissions.twig app/routes.php
git commit -m "feat: add permissions list page"
```

---

### Task 2: CreatePermission action + template + route

**Files:**
- Create: `src/Application/Actions/Admin/CreatePermission.php`
- Create: `src/templates/admin/create_permission.twig`
- Modify: `app/routes.php`

- [ ] **Step 1: Create the CreatePermission action**

Create `src/Application/Actions/Admin/CreatePermission.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\UserPermission;
use Domain\Repositories\RolesRepository;
use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreatePermission
{
    public function __construct(
        private Twig $twig,
        private UserPermissionsRepository $userPermissionsRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() === 'POST') {
            $requestData = $request->getParsedBody();

            $permission = new UserPermission();
            $permission->setPath($requestData['path']);
            $permission->setAllowedRoles($requestData['allowedRoles'] ?? []);
            $this->userPermissionsRepository->persist($permission);

            return $response->withHeader('Location', '/admin/permissions')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/create_permission.twig', [
            'roles' => $this->rolesRepository->findBy([], ['label' => 'asc']),
        ]);
    }
}
```

- [ ] **Step 2: Update `UserPermission` entity to support no-arg construction**

The entity has no constructor, so `new UserPermission()` already works. However the `id` property has no default and `allowedRoles`/`path` have no defaults either — Doctrine will handle ID generation on persist. No changes needed to the entity.

- [ ] **Step 3: Create the create permission template**

Create `src/templates/admin/create_permission.twig`:

```twig
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'permissions' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none">
            <i class="bi bi-house"></i>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/permissions" class="text-decoration-none">Άδειες</a>
    </li>
</ol>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1>Νέα Άδεια</h1>
</div>
<form method="post" autocomplete="off">
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">Μονοπάτι (regex)</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="path" required/>
            <div class="form-text">Κανονική έκφραση που αντιστοιχεί στο URL (π.χ. <code>^/orders-app</code>)</div>
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">Επιτρεπόμενοι ρόλοι</label>
        <div class="col-sm-10">
            {% for role in roles %}
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="allowedRoles[]"
                        value="{{ role.getName }}"
                        id="role_{{ role.getName }}"
                    />
                    <label class="form-check-label" for="role_{{ role.getName }}">
                        {{ role.getLabel }}
                    </label>
                </div>
            {% endfor %}
        </div>
    </div>
    <hr/>
    <div class="row mb-3">
        <div class="col-sm-10 offset-sm-2">
            <button type="submit" class="btn btn-primary">Αποθήκευση</button>
            <a href="/admin/permissions" class="btn btn-secondary ms-2">Ακύρωση</a>
        </div>
    </div>
</form>
{% endblock %}
```

- [ ] **Step 4: Add the create route to `app/routes.php`**

Add the use statement:

```php
use Application\Actions\Admin\CreatePermission;
```

Add the route inside the `/admin` group, after `$group->get('/permissions', Permissions::class);`:

```php
$group->map(['GET', 'POST'], '/permissions/create', CreatePermission::class);
```

- [ ] **Step 5: Verify**

Visit `https://sop.ddev.site/admin/permissions/create` — you should see the form with the path input and a checklist of all roles. Submit the form and confirm it redirects to `/admin/permissions` and the new permission appears in the list.

- [ ] **Step 6: Commit**

```bash
git add src/Application/Actions/Admin/CreatePermission.php src/templates/admin/create_permission.twig app/routes.php
git commit -m "feat: add create permission page"
```

---

### Task 3: UpdatePermission action + template + route

**Files:**
- Create: `src/Application/Actions/Admin/UpdatePermission.php`
- Create: `src/templates/admin/update_permission.twig`
- Modify: `app/routes.php`

- [ ] **Step 1: Create the UpdatePermission action**

Create `src/Application/Actions/Admin/UpdatePermission.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RolesRepository;
use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdatePermission
{
    public function __construct(
        private Twig $twig,
        private UserPermissionsRepository $userPermissionsRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $permission = $this->userPermissionsRepository->find($request->getQueryParams()['id']);

        if ($request->getMethod() === 'POST') {
            $requestData = $request->getParsedBody();

            $permission->setPath($requestData['path']);
            $permission->setAllowedRoles($requestData['allowedRoles'] ?? []);
            $this->userPermissionsRepository->persist($permission);

            return $response->withHeader('Location', '/admin/permissions')->withStatus(302);
        }

        return $this->twig->render($response, 'admin/update_permission.twig', [
            'permission' => $permission,
            'roles' => $this->rolesRepository->findBy([], ['label' => 'asc']),
        ]);
    }
}
```

- [ ] **Step 2: Create the update permission template**

Create `src/templates/admin/update_permission.twig`:

```twig
{% extends 'admin/skeleton.twig' %}

{% set activeNavLink = 'permissions' %}

{% block content %}
<ol class="breadcrumb pt-3">
    <li class="breadcrumb-item">
        <a href="/admin/" class="text-decoration-none">
            <i class="bi bi-house"></i>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a href="/admin/permissions" class="text-decoration-none">Άδειες</a>
    </li>
</ol>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-1 mb-3 border-bottom">
    <h1>Επεξεργασία Άδειας</h1>
</div>
<form method="post" autocomplete="off">
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">Μονοπάτι (regex)</label>
        <div class="col-sm-10">
            <input type="text" class="form-control" name="path" value="{{ permission.getPath }}" required/>
            <div class="form-text">Κανονική έκφραση που αντιστοιχεί στο URL (π.χ. <code>^/orders-app</code>)</div>
        </div>
    </div>
    <div class="row mb-3">
        <label class="col-sm-2 col-form-label">Επιτρεπόμενοι ρόλοι</label>
        <div class="col-sm-10">
            {% for role in roles %}
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="allowedRoles[]"
                        value="{{ role.getName }}"
                        id="role_{{ role.getName }}"
                        {{ role.getName in permission.getAllowedRoles ? 'checked' : '' }}
                    />
                    <label class="form-check-label" for="role_{{ role.getName }}">
                        {{ role.getLabel }}
                    </label>
                </div>
            {% endfor %}
        </div>
    </div>
    <hr/>
    <div class="row mb-3">
        <div class="col-sm-10 offset-sm-2">
            <button type="submit" class="btn btn-primary">Αποθήκευση</button>
            <a href="/admin/permissions" class="btn btn-secondary ms-2">Ακύρωση</a>
        </div>
    </div>
</form>
{% endblock %}
```

- [ ] **Step 3: Add the update route to `app/routes.php`**

Add the use statement:

```php
use Application\Actions\Admin\UpdatePermission;
```

Add the route inside the `/admin` group, after the create route:

```php
$group->map(['GET', 'POST'], '/permissions/update', UpdatePermission::class);
```

- [ ] **Step 4: Verify**

Click the path link on any row at `/admin/permissions` — you should reach the update form pre-populated with the existing path and pre-checked roles. Submit and confirm it saves and redirects back to the list.

- [ ] **Step 5: Commit**

```bash
git add src/Application/Actions/Admin/UpdatePermission.php src/templates/admin/update_permission.twig app/routes.php
git commit -m "feat: add update permission page"
```

---

### Task 4: DeletePermission action + route

**Files:**
- Create: `src/Application/Actions/Admin/DeletePermission.php`
- Modify: `app/routes.php`

- [ ] **Step 1: Create the DeletePermission action**

Create `src/Application/Actions/Admin/DeletePermission.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class DeletePermission
{
    public function __construct(
        private Twig $twig,
        private UserPermissionsRepository $userPermissionsRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $permission = $this->userPermissionsRepository->find($request->getQueryParams()['id']);

        $this->userPermissionsRepository->delete($permission);

        return $response->withHeader('Location', '/admin/permissions')->withStatus(302);
    }
}
```

- [ ] **Step 2: Add the delete route to `app/routes.php`**

Add the use statement:

```php
use Application\Actions\Admin\DeletePermission;
```

Add the route inside the `/admin` group, after the update route:

```php
$group->get('/permissions/delete', DeletePermission::class);
```

- [ ] **Step 3: Verify**

On the permissions list page, click the trash icon on any row — Bootbox should show a confirmation dialog. Confirm and verify the permission is deleted and the list updates.

- [ ] **Step 4: Commit**

```bash
git add src/Application/Actions/Admin/DeletePermission.php app/routes.php
git commit -m "feat: add delete permission action"
```

---

### Task 5: Add sidebar nav link

**Files:**
- Modify: `src/templates/admin/skeleton.twig`

- [ ] **Step 1: Add the nav link to the sidebar**

In `src/templates/admin/skeleton.twig`, locate the Ρόλοι nav item (around line 110) and the Γλώσσες nav item (around line 114). Insert the new nav item between them:

```twig
<li class="nav-item">
    <a class="nav-link {{activeNavLink == 'permissions' ? 'active' : ''}}" href="/admin/permissions">
        Άδειες
    </a>
</li>
```

The result should look like:

```twig
<li class="nav-item">
    <a class="nav-link {{activeNavLink == 'roles' ? 'active' : ''}}" href="/admin/roles">
        Ρόλοι
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{activeNavLink == 'permissions' ? 'active' : ''}}" href="/admin/permissions">
        Άδειες
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{activeNavLink == 'languages' ? 'active' : ''}}" href="/admin/languages">
        Γλώσσες
    </a>
</li>
```

- [ ] **Step 2: Verify**

Visit any admin page — "Άδειες" should appear in the sidebar between Ρόλοι and Γλώσσες. The link should be highlighted when on the permissions pages.

- [ ] **Step 3: Commit**

```bash
git add src/templates/admin/skeleton.twig
git commit -m "feat: add permissions nav link to admin sidebar"
```
