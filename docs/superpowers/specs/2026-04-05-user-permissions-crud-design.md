# User Permissions CRUD — Design Spec

**Date:** 2026-04-05  
**Scope:** Admin UI to create, read, update, and delete `UserPermission` records.

---

## Background

The `Authorization` middleware matches incoming request paths against regex patterns stored in the `user_permissions` table. Each record has a `path` (regex string) and `allowedRoles` (array of role name strings). Currently there is no UI to manage these records — they must be edited directly in the database.

---

## Approach

Standard multi-page CRUD, matching the existing Roles/Users/Tables pattern: list page → separate create page → separate update page → delete via GET with Bootbox confirmation dialog.

---

## Routes & Actions

All routes live under the existing `/admin` group (already protected by `Authentication` + `Authorization` + `Menus` middleware).

| Route | Action class | HTTP methods |
|---|---|---|
| `/admin/permissions` | `Permissions` | GET |
| `/admin/permissions/create` | `CreatePermission` | GET, POST |
| `/admin/permissions/update` | `UpdatePermission` | GET, POST (takes `?id=`) |
| `/admin/permissions/delete` | `DeletePermission` | GET (takes `?id=`) |

Action classes live in `src/Application/Actions/Admin/`.

---

## Templates

Three Twig templates in `src/templates/admin/`:

**`permissions.twig`**
- Breadcrumb + page header with a "+" button linking to `/admin/permissions/create`
- Table with columns: Path, Allowed Roles (comma-separated role labels), actions (edit link + delete button with `.confirm` class for Bootbox dialog)
- `activeNavLink = 'permissions'`

**`create_permission.twig`**
- `path` text input (the regex pattern, e.g. `^/orders-app`)
- Roles checklist: one checkbox per role (`name="allowedRoles[]"`, value = role name, label = role label), sorted alphabetically
- Save / Cancel (→ `/admin/permissions`) buttons

**`update_permission.twig`**
- Same as create form, pre-populated with existing values
- Checkboxes pre-checked for roles already in `allowedRoles`

**`skeleton.twig`** — add "Άδειες" nav link between Ρόλοι and Γλώσσες.

---

## Data Flow

**List:** `Permissions` action calls `findAll()`, passes results to template. Roles labels are looked up via `RolesRepository` to display human-readable names next to the stored role name strings.

**Create (POST):** Parse `path` and `allowedRoles[]` from body. Instantiate a new `UserPermission`, call `setPath()` and `setAllowedRoles()`, call `persist()`. Redirect to `/admin/permissions`.

**Update (POST):** Fetch entity via `find($id)`. Call `setPath()` and `setAllowedRoles()` with new values. Call `persist()`. Redirect to `/admin/permissions`.

**Delete (GET):** Fetch entity via `find($id)`. Call `delete()`. Redirect to `/admin/permissions`.

---

## Dependencies

- `UserPermissionsRepository` — already has `persist()` and `delete()`; no new methods needed.
- `RolesRepository` — injected into `Permissions`, `CreatePermission`, and `UpdatePermission` actions to populate the roles checklist and display labels.
- No new DI bindings needed — both repositories are already bound in `app/dependencies.php`.

---

## Authorization

The permissions UI lives under `/admin/*` which is already restricted to `webmaster` role in practice. No new `user_permissions` DB entry is required for this feature.
