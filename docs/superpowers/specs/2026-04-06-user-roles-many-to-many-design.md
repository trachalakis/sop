# User Roles Many-to-Many Refactoring — Design Spec

**Date:** 2026-04-06
**Scope:** Replace the `simple_array` `roles` column on `users` with a proper Doctrine ManyToMany association to the `roles` table, and migrate existing data.

---

## Background

`User.roles` is currently a `simple_array` column storing role name strings (e.g. `['webmaster', 'employee']`). A `Role` entity already exists in a separate `roles` table. This refactoring connects the two via a proper ORM association, making role management consistent and enabling typed access to role data.

---

## Section 1: Entity Changes

### `User.php`

- Remove `#[ORM\Column(type: 'simple_array', name: 'roles')]`
- Add:
  ```php
  #[ORM\ManyToMany(targetEntity: Role::class)]
  #[ORM\JoinTable(name: 'user_roles')]
  #[ORM\JoinColumn(onDelete: 'CASCADE')]
  #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
  private Collection $roles;
  ```
- Initialize `$roles` to `new ArrayCollection()` in the constructor before calling `setRoles()`
- `getRoles(): array` returns `$this->roles->toArray()` — returns `Role[]`
- `setRoles(array $roles)` accepts `Role[]` — clears the collection then adds each item
- Constructor `array $roles` parameter is `Role[]`
- `hasRole(string $roleName): bool` — iterates `$this->roles`, returns true if any role's `getName()` matches `$roleName` or equals `'webmaster'`
- `isEmployee(): bool` — iterates `$this->roles`, returns true if any role's `getName()` equals `'employee'`
- `isWaiter(): bool` — iterates `$this->roles`, returns true if any role's `getName()` equals `'waiter'`

### `Role.php`

No changes. The association is unidirectional from the `User` side.

---

## Section 2: Caller Updates

All callers of `getRoles()` that currently treat the return value as `string[]` must be updated.

### `src/Middleware/Authorization.php`

Extract role names before comparisons:
```php
$roleNames = array_map(fn($r) => $r->getName(), $user->getRoles());
if (in_array('webmaster', $roleNames)) { ... }
// and
if (count(array_intersect($permission->getAllowedRoles(), $roleNames)) > 0) { ... }
```

### `src/Application/Actions/Login.php`

Extract role names:
```php
$roleNames = array_map(fn($r) => $r->getName(), $user->getRoles());
if (in_array('webmaster', $roleNames)) { ... }
if (in_array('terminal', $roleNames)) { ... }
if (in_array('employee', $roleNames)) { ... }
```

### `src/Application/Actions/Admin/Report.php`

Extract role names on the scan's user:
```php
$roleNames = array_map(fn($r) => $r->getName(), $scan->getUser()->getRoles());
if (in_array('foh', $roleNames)) { ... }
if (in_array('boh', $roleNames)) { ... }
```

### `src/Application/Actions/Admin/CreateUser.php`

Fetch `Role[]` from `RolesRepository` by the submitted name strings before passing to constructor:
```php
$roleNames = $requestData['roles'] ?? [];
$roles = $roleNames ? $this->rolesRepository->findBy(['name' => $roleNames]) : [];
$user = new User(..., $roles);
```

### `src/Application/Actions/Admin/UpdateUser.php`

Same — fetch `Role[]` before calling `setRoles()`:
```php
$roleNames = $requestData['roles'] ?? [];
$roles = $roleNames ? $this->rolesRepository->findBy(['name' => $roleNames]) : [];
$user->setRoles($roles);
```

### `src/templates/admin/update_user.twig`

Build a role-names array with Twig's `map` filter, then use it for pre-selection:
```twig
{% set userRoleNames = user.getRoles()|map(r => r.getName) %}
<option value="{{userRole.getName}}" {{userRole.getName in userRoleNames ? 'selected' : ''}}>
```

`create_user.twig` — no change (no pre-selection logic).

---

## Section 3: Migration Script

**File:** `migrations/migrate_user_roles_to_join_table.php`

Follows the existing migration pattern (load `.env`, connect via PDO, guard against double-run, single transaction, rollback on failure).

Steps:
1. **Guard:** exit if `user_roles` table already exists
2. **Create `user_roles` join table:**
   ```sql
   CREATE TABLE user_roles (
       user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
       role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
       PRIMARY KEY (user_id, role_id)
   )
   ```
3. **Read all users** and their current `roles` column value (raw comma-separated string)
4. **Collect unique role names** across all users; for each that doesn't exist in `roles`, insert it with `label = name`
5. **Populate `user_roles`:** for each user, split the `roles` string by comma, skip empty strings, look up each role by name, insert `(user_id, role_id)` into `user_roles`
6. **Drop `roles` column** from `users`: `ALTER TABLE users DROP COLUMN roles`

---

## Files Changed

| File | Action |
|---|---|
| `src/Domain/Entities/User.php` | Modify |
| `src/Middleware/Authorization.php` | Modify |
| `src/Application/Actions/Login.php` | Modify |
| `src/Application/Actions/Admin/Report.php` | Modify |
| `src/Application/Actions/Admin/CreateUser.php` | Modify |
| `src/Application/Actions/Admin/UpdateUser.php` | Modify |
| `src/templates/admin/update_user.twig` | Modify |
| `migrations/migrate_user_roles_to_join_table.php` | Create |
