# Supplier Entity & Admin CRUD — Design Spec

**Date:** 2026-04-16

## Problem

Supplier information is currently stored as a free-text `"supplier"` key inside the `custom_fields` JSON column on the `supplies` table. This makes it impossible to enforce uniqueness, add structured data (e.g. telephone), or build relationships. We need a first-class `Supplier` entity.

## Solution

Introduce a `Supplier` entity with its own table, migrate existing supplier names from `custom_fields` into it, wire up `supplies` to their supplier via a FK, and provide a full admin CRUD.

---

## Data Layer

### `suppliers` table (new)

| column | type | constraints |
|---|---|---|
| `id` | integer | PK, auto-increment |
| `name` | varchar | unique, not null |
| `telephone` | varchar | nullable |

### `Supplier` entity

`src/Domain/Entities/Supplier.php` — Doctrine ORM entity backed by `SuppliersRepository`.

Fields: `id`, `name`, `telephone` (nullable).

### `Supply` entity changes

Add a nullable `ManyToOne` relation to `Supplier`:

```php
#[ORM\ManyToOne(targetEntity: Supplier::class)]
#[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: true)]
private ?Supplier $supplier = null;
```

`supplier_id` is nullable because existing supplies may have no supplier value in `custom_fields`.

### Migration

`migrations/create_suppliers_and_migrate_supply_data.php` — single transaction:

1. Create `suppliers` table (`id`, `name`, `telephone`)
2. Read all distinct non-null, non-empty `"supplier"` values from `supplies.custom_fields` JSON
3. Insert each as a `Supplier` row (name only; telephone starts empty)
4. Add `supplier_id` nullable integer FK column to `supplies`
5. `UPDATE supplies SET supplier_id = (SELECT id FROM suppliers WHERE name = custom_fields->>'supplier') WHERE custom_fields->>'supplier' IS NOT NULL`
6. Remove `"supplier"` key from every supply's `custom_fields` JSON using `jsonb_strip_nulls` / key deletion

---

## Admin CRUD

### Routes (`app/routes.php`)

```
GET/POST  /admin/suppliers           ListSuppliers
GET/POST  /admin/suppliers/create    CreateSupplier
GET/POST  /admin/suppliers/update    UpdateSupplier
GET/POST  /admin/suppliers/delete    DeleteSupplier
```

### Actions (`src/Application/Actions/Admin/`)

- `ListSuppliers` — fetches all suppliers ordered by name, renders `admin/suppliers.twig`
- `CreateSupplier` — GET renders form; POST creates `Supplier` and redirects to `/admin/suppliers`
- `UpdateSupplier` — GET renders pre-filled form; POST updates and redirects to `/admin/suppliers`
- `DeleteSupplier` — POST deletes and redirects to `/admin/suppliers`

### Templates (`src/templates/admin/`)

- `suppliers.twig` — table listing name and telephone, with edit links
- `create_supplier.twig` — form: name (required), telephone (optional)
- `update_supplier.twig` — same form pre-filled, with delete button

### DI (`app/dependencies.php`)

```php
SuppliersRepository::class => fn($c) => $c->get(EntityManager::class)->getRepository(Supplier::class),
```

### Sidebar (`src/templates/admin/skeleton.twig`)

New nav entry "Προμηθευτές" added between "Προμήθειες" and "Συνταγές".

---

## Supply Form Updates

### Templates

`create_supply.twig` and `update_supply.twig` get a "Προμηθευτής" dropdown:
- First option: blank (no supplier)
- Remaining options: all suppliers ordered by name
- On `update_supply.twig`, the current supplier is pre-selected

The "Προμηθευτής" custom field entry is removed from the custom fields list in `update_supply.twig` (it no longer exists in `custom_fields` after migration).

### Actions

`CreateSupply` and `UpdateSupply` are injected with `SuppliersRepository`. On POST:
- If `supplier` param is non-empty, resolve to a `Supplier` entity and call `$supply->setSupplier($supplier)`
- If blank, call `$supply->setSupplier(null)`

---

## Non-goals

- No supplier–supply list view on the supplier detail page (can be added later)
- No telephone validation (stored as plain string)
- No soft-delete for suppliers
