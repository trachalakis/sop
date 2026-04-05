# User Roles Many-to-Many Refactoring Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the `simple_array` `roles` column on `users` with a proper Doctrine ManyToMany association to the `roles` table, update all callers, and provide a migration script.

**Architecture:** The `User` entity gets a `ManyToMany` collection to `Role` backed by a `user_roles` join table. `getRoles()` returns `Role[]`. All callers that compared role name strings are updated to extract names via `array_map`. The migration script creates the join table, auto-creates any missing roles, populates from the old column, then drops it.

**Tech Stack:** PHP 8, Doctrine ORM 3, Slim 4, Twig 3, PostgreSQL, PDO (migration script)

---

### Task 1: Update User entity

**Files:**
- Modify: `src/Domain/Entities/User.php`

- [ ] **Step 1: Replace the entire file content**

Write `src/Domain/Entities/User.php` with the following content:

```php
<?php

declare(strict_types=1);

namespace Domain\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Repositories\UsersRepository;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column(type: 'string', name: 'email_address')]
    private string $emailAddress;

    #[ORM\Column(type: 'string', name: 'full_name')]
    private string $fullName;

    #[ORM\Column(type: 'float', name: 'hourly_rate')]
    private ?float $hourlyRate;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive;

    #[ORM\Column(type: 'string', name: 'notes')]
    private ?string $notes;

    #[ORM\Column(type: 'string', name: 'password_hash')]
    private string $passwordHash;

    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: 'user_roles')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    private Collection $roles;

    public function __construct(
        bool $isActive,
        string $emailAddress,
        string $password,
        string $fullName,
        float $hourlyRate,
        array $roles
    ) {
        $this->roles = new ArrayCollection();
        $this->setIsActive($isActive);
        $this->setEmailAddress($emailAddress);
        $this->setPassword($password);
        $this->setFullName($fullName);
        $this->setHourlyRate($hourlyRate);
        $this->setRoles($roles);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getHourlyRate(): ?float
    {
        return $this->hourlyRate;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRoles(): array
    {
        return $this->roles->toArray();
    }

    public function hasRole(string $role): bool
    {
        foreach ($this->roles as $r) {
            if ($r->getName() === $role || $r->getName() === 'webmaster') {
                return true;
            }
        }
        return false;
    }

    public function isEmployee(): bool
    {
        foreach ($this->roles as $r) {
            if ($r->getName() === 'employee') {
                return true;
            }
        }
        return false;
    }

    public function isWaiter(): bool
    {
        foreach ($this->roles as $r) {
            if ($r->getName() === 'waiter') {
                return true;
            }
        }
        return false;
    }

    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function setHourlyRate(float $hourlyRate): void
    {
        $this->hourlyRate = $hourlyRate;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function setPassword(string $password): void
    {
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT);
    }

    public function setRoles(array $roles): void
    {
        $this->roles->clear();
        foreach ($roles as $role) {
            $this->roles->add($role);
        }
    }
}
```

Key changes from the original:
- Removed `use Domain\Enums\UserRole;` (was imported but unused)
- Replaced `#[ORM\Column(type: 'simple_array', name: 'roles')]` with ManyToMany mapping
- `$roles` is now a `Collection`, initialized to `new ArrayCollection()` before `setRoles()` in constructor
- `getRoles()` returns `$this->roles->toArray()` — returns `Role[]`
- `setRoles(array $roles)` accepts `Role[]` — clears collection then adds each
- `hasRole()`, `isEmployee()`, `isWaiter()` iterate Role objects and compare `getName()`

- [ ] **Step 2: Commit**

```bash
git add src/Domain/Entities/User.php
git commit -m "refactor: replace User.roles simple_array with ManyToMany association"
```

---

### Task 2: Update Authorization middleware and Login action

**Files:**
- Modify: `src/Middleware/Authorization.php`
- Modify: `src/Application/Actions/Login.php`

- [ ] **Step 1: Update Authorization.php**

Replace the entire `process` method body in `src/Middleware/Authorization.php`. The full updated file:

```php
<?php

declare(strict_types=1);

namespace Middleware;

use Domain\Repositories\UserPermissionsRepository;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Server\MiddlewareInterface;

final class Authorization implements MiddlewareInterface
{
    private UserPermissionsRepository $userPermissionsRepository;

    public function __construct(UserPermissionsRepository $userPermissionsRepository)
    {
        $this->userPermissionsRepository = $userPermissionsRepository;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $user = $_SESSION['user'] ?? null;

        if ($user == null) {
            $response = $handler->handle($request);
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $userRoleNames = array_map(fn($r) => $r->getName(), $user->getRoles());

        if (in_array('webmaster', $userRoleNames)) {
            $response = $handler->handle($request);
            return $response;
        }

        $permissions = $this->userPermissionsRepository->findAll();
        foreach ($permissions as $permission) {
            $path = str_replace('/', '\/', $permission->getPath());
            $path = sprintf("/%s/", $path);
            if (preg_match($path, $request->getUri()->getPath())) {
                if (count(array_intersect($permission->getAllowedRoles(), $userRoleNames)) > 0) {
                    $response = $handler->handle($request);
                    return $response;
                }
            }
        }

        $response = $handler->handle($request);
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
```

- [ ] **Step 2: Update Login.php**

Replace the entire file content of `src/Application/Actions/Login.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions;

use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class Login
{
    public function __construct(
        private Twig $twig,
        private UsersRepository $usersRepository
    ) { }

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() == 'POST') {
            $requestParams = $request->getParsedBody();

            $user = $this->usersRepository->findOneBy([
                'emailAddress' => $requestParams['emailAddress'],
                'isActive' => true
            ]);

            if ($user != null) {
                if (password_verify($requestParams['password'], $user->getPasswordHash())) {
                    $_SESSION['user'] = $user;

                    $roleNames = array_map(fn($r) => $r->getName(), $user->getRoles());
                    if (in_array('webmaster', $roleNames)) {
                        return $response->withHeader('Location', '/admin/')->withStatus(302);
                    }
                    if (in_array('terminal', $roleNames)) {
                        return $response->withHeader('Location', '/orders-app/')->withStatus(302);
                    }
                    if (in_array('employee', $roleNames)) {
                        return $response->withHeader('Location', '/users-app/')->withStatus(302);
                    }
                }
            }
        }

        return $this->twig->render($response, 'login.twig');
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Middleware/Authorization.php src/Application/Actions/Login.php
git commit -m "refactor: update Authorization and Login to use Role objects from getRoles()"
```

---

### Task 3: Update Report action

**Files:**
- Modify: `src/Application/Actions/Admin/Report.php`

- [ ] **Step 1: Update the foh/boh role checks**

In `src/Application/Actions/Admin/Report.php`, find this block (around line 199):

```php
                if (in_array('foh', $scan->getUser()->getRoles())) {
                    $fohSalaries += $scan->getSalary();
                }

                if (in_array('boh', $scan->getUser()->getRoles())) {
                    $bohSalaries += $scan->getSalary();
                }
```

Replace it with:

```php
                $userRoleNames = array_map(fn($r) => $r->getName(), $scan->getUser()->getRoles());
                if (in_array('foh', $userRoleNames)) {
                    $fohSalaries += $scan->getSalary();
                }

                if (in_array('boh', $userRoleNames)) {
                    $bohSalaries += $scan->getSalary();
                }
```

- [ ] **Step 2: Commit**

```bash
git add src/Application/Actions/Admin/Report.php
git commit -m "refactor: update Report action to use Role objects from getRoles()"
```

---

### Task 4: Update CreateUser and UpdateUser actions

**Files:**
- Modify: `src/Application/Actions/Admin/CreateUser.php`
- Modify: `src/Application/Actions/Admin/UpdateUser.php`

- [ ] **Step 1: Update CreateUser.php**

Replace the entire file content of `src/Application/Actions/Admin/CreateUser.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Entities\User;
use Domain\Repositories\RolesRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class CreateUser
{
    public function __construct(
        private Twig $twig,
        private UsersRepository $usersRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        if ($request->getMethod() == 'POST') {
            $requestData = $request->getParsedBody();

            $roleNames = $requestData['roles'] ?? [];
            $roles = $roleNames ? $this->rolesRepository->findBy(['name' => $roleNames]) : [];

            $user = new User(
                boolval($requestData['isActive']),
                $requestData['emailAddress'],
                $requestData['password'],
                $requestData['fullName'],
                floatval($requestData['hourlyRate']),
                $roles
            );
            $user->setNotes($requestData['notes']);
            $this->usersRepository->persist($user);

            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        return $this->twig->render(
            $response,
            'admin/create_user.twig',
            ['userRoles' => $this->rolesRepository->findBy([], ['label' => 'asc'])]
        );
    }
}
```

- [ ] **Step 2: Update UpdateUser.php**

Replace the entire file content of `src/Application/Actions/Admin/UpdateUser.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Actions\Admin;

use Domain\Repositories\RolesRepository;
use Domain\Repositories\UsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final class UpdateUser
{
    public function __construct(
        private Twig $twig,
        private UsersRepository $usersRepository,
        private RolesRepository $rolesRepository
    ) {
    }

    public function __invoke(Request $request, Response $response)
    {
        $requestData = $request->getParsedBody();
        $user = $this->usersRepository->find($request->getQueryParams()['id']);

        if ($request->getMethod() == 'POST') {
            $user->setIsActive(boolval($requestData['isActive']));
            $user->setEmailAddress($requestData['emailAddress']);
            if (!empty($requestData['password'])) {
                $user->setPassword($requestData['password']);
            }
            $user->setFullName($requestData['fullName']);
            $user->setHourlyRate(floatval($requestData['hourlyRate']));
            $roleNames = $requestData['roles'] ?? [];
            $roles = $roleNames ? $this->rolesRepository->findBy(['name' => $roleNames]) : [];
            $user->setRoles($roles);
            $user->setNotes($requestData['notes']);

            $this->usersRepository->persist($user);

            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }

        return $this->twig->render(
            $response,
            'admin/update_user.twig',
            [
                'user' => $user,
                'userRoles' => $this->rolesRepository->findBy([], ['label' => 'asc']),
            ]
        );
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Application/Actions/Admin/CreateUser.php src/Application/Actions/Admin/UpdateUser.php
git commit -m "refactor: update CreateUser and UpdateUser to fetch Role objects for setRoles()"
```

---

### Task 5: Update update_user.twig template

**Files:**
- Modify: `src/templates/admin/update_user.twig`

- [ ] **Step 1: Update the roles select block**

In `src/templates/admin/update_user.twig`, find this block (around line 66):

```twig
	<div class="row mb-3">
		<label class="col-sm-2 col-form-label">Roles</label>
		<div class="col-sm-10">
			<select class="form-select" name="roles[]" size="15" multiple>
				{% for userRole in userRoles %}
                    <option value="{{userRole.getName}}" {{userRole.getName in user.getRoles ? 'selected' : ''}}>{{userRole.getLabel}}</option>
                {% endfor %}
			</select>
    	</div>
	</div>
```

Replace it with:

```twig
	<div class="row mb-3">
		<label class="col-sm-2 col-form-label">Roles</label>
		<div class="col-sm-10">
			<select class="form-select" name="roles[]" size="15" multiple>
				{% set userRoleNames = user.getRoles()|map(r => r.getName) %}
				{% for userRole in userRoles %}
                    <option value="{{userRole.getName}}" {{userRole.getName in userRoleNames ? 'selected' : ''}}>{{userRole.getLabel}}</option>
                {% endfor %}
			</select>
    	</div>
	</div>
```

The `map(r => r.getName)` Twig arrow function calls `getName()` on each `Role` object in the array, producing a `string[]` that the `in` operator can compare against.

- [ ] **Step 2: Commit**

```bash
git add src/templates/admin/update_user.twig
git commit -m "refactor: update update_user.twig to extract role names for pre-selection"
```

---

### Task 6: Write migration script

**Files:**
- Create: `migrations/migrate_user_roles_to_join_table.php`

- [ ] **Step 1: Create the migration script**

Create `migrations/migrate_user_roles_to_join_table.php`:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

$envFile = __DIR__ . '/../app/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found at {$envFile}\n");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$host     = $_ENV['DB_HOST']     ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbName   = $_ENV['DB_NAME']     ?? '';

$dsn = "pgsql:host={$host};dbname={$dbName}";

try {
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "Connected to database '{$dbName}'.\n";

// Guard: exit if migration already ran
$tableExists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_name = 'user_roles'
")->fetchColumn();

if ($tableExists) {
    die("Table 'user_roles' already exists. Migration may have already run.\n");
}

$pdo->beginTransaction();

try {
    // Step 1: Create user_roles join table
    $pdo->exec("
        CREATE TABLE user_roles (
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
            PRIMARY KEY (user_id, role_id)
        )
    ");
    echo "Created table 'user_roles'.\n";

    // Step 2: Read all users and their current roles column
    $users = $pdo->query("SELECT id, roles FROM users")->fetchAll(PDO::FETCH_ASSOC);

    // Step 3: Collect all unique role names across all users
    $allRoleNames = [];
    foreach ($users as $user) {
        if (empty($user['roles'])) {
            continue;
        }
        foreach (explode(',', $user['roles']) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $allRoleNames[$name] = true;
            }
        }
    }

    // Fetch existing roles keyed by name => id
    $existingRoles = $pdo->query("SELECT name, id FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Auto-create any missing roles
    $insertRole = $pdo->prepare("INSERT INTO roles (name, label) VALUES (:name, :label) RETURNING id");
    foreach (array_keys($allRoleNames) as $name) {
        if (!isset($existingRoles[$name])) {
            $insertRole->execute(['name' => $name, 'label' => $name]);
            $existingRoles[$name] = $insertRole->fetchColumn();
            echo "Auto-created missing role: {$name}\n";
        }
    }

    // Step 4: Populate user_roles join table
    $insertUserRole = $pdo->prepare(
        "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id) ON CONFLICT DO NOTHING"
    );
    foreach ($users as $user) {
        if (empty($user['roles'])) {
            continue;
        }
        foreach (explode(',', $user['roles']) as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $insertUserRole->execute(['user_id' => $user['id'], 'role_id' => $existingRoles[$name]]);
        }
        echo "Migrated roles for user ID {$user['id']}.\n";
    }

    // Step 5: Drop the old roles column from users
    $pdo->exec("ALTER TABLE users DROP COLUMN roles");
    echo "Dropped column 'roles' from 'users'.\n";

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migration failed, rolled back. Error: " . $e->getMessage() . "\n");
}
```

- [ ] **Step 2: Commit**

```bash
git add migrations/migrate_user_roles_to_join_table.php
git commit -m "feat: add migration script for user roles many-to-many join table"
```

- [ ] **Step 3: Run the migration**

```bash
ddev exec php migrations/migrate_user_roles_to_join_table.php
```

Expected output (example):
```
Connected to database 'db'.
Created table 'user_roles'.
Auto-created missing role: employee       ← only if 'employee' not already in roles table
Auto-created missing role: terminal       ← only for roles missing from roles table
Migrated roles for user ID 1.
Migrated roles for user ID 2.
...
Dropped column 'roles' from 'users'.
Migration completed successfully.
```
