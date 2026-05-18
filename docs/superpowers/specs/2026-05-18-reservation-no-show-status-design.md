# Add NO_SHOW reservation status

## Summary

Add a fourth reservation status value, `NO_SHOW`, treated the same way as `CANCELLED` (filtered out of `activeReservations`, can't be placed on the grid, removed from the sidebar). Editable from the existing offcanvas status picker via a new fourth button. Reflected in the list view's status display.

## Goals

- Let staff record that a guest didn't arrive, distinct from a cancellation.
- Keep the data model dead simple: same `status` string column, one more value.
- Reuse every existing pipeline — filter, badge, label, whitelist — by extending it with one entry.

## Non-goals

- No automatic table un-assignment when a reservation flips to NO_SHOW. Same as today's CANCELLED behavior.
- No reporting or analytics on no-show counts.
- No status editing from the list view (`homepage_list.twig`'s dropdown items are placeholder `href="#"` links — pre-existing, out of scope).
- No new migration, no entity change. The `status` column already accepts arbitrary strings; the action-level whitelist is the guard.
- No change to `CreateReservation.php` — new reservations still default to `PENDING`.

## Status values after this change

| Value | Greek button label (form) | Greek badge label (sidebar/cells) | Badge class | Filtered as "inactive"? |
|-------|--------------------------|-----------------------------------|-------------|------------------------|
| `PENDING` | Εκκρεμής | ΑΝΑΜ. | `bg-warning text-dark` | No |
| `ARRIVED` | Ήρθε | ΗΡΘΕ | `bg-success` | No |
| `NO_SHOW` | Δεν ήρθε | ΔΕΝ ΗΡΘΕ | `bg-secondary` | **Yes** |
| `CANCELLED` | Ακύρωση | ΑΚΥΡ. | `bg-danger` | Yes |

The form picker renders the buttons in this order (PENDING → ARRIVED → NO_SHOW → CANCELLED). The two "didn't happen" outcomes sit together at the right.

## Backend changes

`src/Application/Actions/ReservationsApp/UpdateReservation.php` — extend the whitelist constant:

```php
private const ALLOWED_STATUSES = ['PENDING', 'ARRIVED', 'NO_SHOW', 'CANCELLED'];
```

That's the only PHP change. The two `if (isset(...) && in_array(...))` blocks already reference `self::ALLOWED_STATUSES`, so they automatically accept the new value.

## Frontend changes — grid view (`src/templates/reservations_app/homepage.twig`)

### 1. Form picker — add fourth button

In the status picker added to the offcanvas, currently rendered as three `btn-check` radios (PENDING/ARRIVED/CANCELLED with `flex-fill` labels), insert a fourth radio between ARRIVED and CANCELLED:

```html
<input type="radio" class="btn-check" id="f-status-NO_SHOW" value="NO_SHOW" v-model="form.status">
<label class="btn fs-4 btn-outline-secondary flex-fill" for="f-status-NO_SHOW">Δεν ήρθε</label>
```

The four `flex-fill` buttons share the offcanvas width evenly; each Greek label fits in a button of approximately `(520 - 3*8) / 4 ≈ 124px`.

### 2. `activeReservations` — filter out NO_SHOW

The current computed filters out `CANCELLED`:

```js
activeReservations() {
    const notCancelled = this.reservations.filter(r => r.status !== 'CANCELLED');
    if (!this.cutoffTime) return notCancelled;
    return notCancelled.filter(r => {
        const rDate = new Date(r.dateTime.replace(' ', 'T'));
        return rDate >= this.cutoffTime;
    });
},
```

Extend the first filter to also exclude `NO_SHOW`. The local variable name no longer reflects the meaning, so rename it to `stillActive`:

```js
activeReservations() {
    const stillActive = this.reservations.filter(r => r.status !== 'CANCELLED' && r.status !== 'NO_SHOW');
    if (!this.cutoffTime) return stillActive;
    return stillActive.filter(r => {
        const rDate = new Date(r.dateTime.replace(' ', 'T'));
        return rDate >= this.cutoffTime;
    });
},
```

### 3. `clickCell` placement guard — block NO_SHOW

The current `clickCell(tableId, slot)` method early-returns if the candidate reservation is cancelled:

```js
if (reservation.status === 'CANCELLED') return;
```

Extend the guard to also block NO_SHOW:

```js
if (reservation.status === 'CANCELLED' || reservation.status === 'NO_SHOW') return;
```

(This guard fires when a user picks a sidebar card, switches it to NO_SHOW via the form somewhere else, then clicks a grid cell — the card is gone from the sidebar by then, but the in-flight placement target is still in scope. The guard is the existing safety net; we extend it for symmetry. In practice, the card disappears so quickly that this case is rare.)

### 4. Badge maps — add NO_SHOW entries

The current `statusBadgeClass(status)` and `statusLabel(status)` methods are lookup tables. Add a `NO_SHOW` key to each:

```js
statusBadgeClass(status) {
    return { PENDING: 'bg-warning text-dark', ARRIVED: 'bg-success', NO_SHOW: 'bg-secondary', CANCELLED: 'bg-danger' }[status] ?? 'bg-secondary';
},
statusLabel(status) {
    return { PENDING: 'ΑΝΑΜ.', ARRIVED: 'ΗΡΘΕ', NO_SHOW: 'ΔΕΝ ΗΡΘΕ', CANCELLED: 'ΑΚΥΡ.' }[status] ?? status;
},
```

The `?? 'bg-secondary'` fallback already matches the NO_SHOW class, so legacy values won't visually conflict, but the explicit entry is needed for the label map and for clarity.

## Frontend changes — list view (`src/templates/reservations_app/homepage_list.twig`)

The list view shows a Bootstrap dropdown button labeled with the current status text (`ΑΝΑΜΕΝΕΤΑΙ` / `ΗΡΘΕ` / `ΑΚΥΡΩΘΗΚΕ`). Add a fourth `elseif` branch between ARRIVED and CANCELLED:

```twig
{% elseif reservation.getStatus == 'NO_SHOW' %}
    ΔΕΝ ΗΡΘΕ
```

The dropdown's menu items (`<a href="#">…</a>`) are pre-existing placeholders that don't trigger anything — they stay untouched per "out of scope".

## Flow walkthrough

1. Staff clicks pencil on a PENDING reservation in the sidebar.
2. Offcanvas opens with "Κατάσταση" picker showing four buttons; PENDING is highlighted.
3. Staff clicks "Δεν ήρθε" — the gray button highlights, PENDING deselects.
4. Staff clicks Αποθήκευση.
5. Frontend POSTs `{ ..., status: 'NO_SHOW' }` to `/reservations-app/update?id=…`.
6. Backend's `UpdateReservation` JSON branch: `isset` passes, `in_array(..., ALLOWED_STATUSES, true)` matches the new value, `$reservation->setStatus('NO_SHOW')`, persist.
7. Response `{ success: true }`.
8. Frontend's local splice spread sets `status: 'NO_SHOW'` on the matching local reservation.
9. `activeReservations` re-derives → the reservation is filtered out.
10. Sidebar list (`visibleReservations`) loses the card. Grid placement (`initCells` reads from `activeReservations` via the same pipeline) loses the cell. `unplacedReservations` and `coverCount` recompute.

The list view (separate page, not a SPA) shows the new label on next page render.

## Edge cases

- **A NO_SHOW reservation is later switched back to PENDING.** Open the offcanvas (it's still loadable by ID — the entity isn't deleted), pick PENDING, save. The local splice puts it back into `activeReservations`; sidebar and grid placement recover. No special handling needed.
- **Cancelled reservation switched to NO_SHOW (or vice versa).** Trivial — both are non-active states; the local state moves from one excluded group to another. Visually nothing changes in the sidebar/grid; the next time the form opens it'll reflect the new value.
- **Legacy reservation rows with a status outside the four values.** Already handled by `?? 'bg-secondary'` / `?? status` fallbacks in the badge maps. The form picker leaves all four buttons unchecked until the user picks one. Same posture as today.

## Scope of change

Three files:
- `src/templates/reservations_app/homepage.twig` — picker (1 new radio+label), `activeReservations` filter widening + rename, `clickCell` guard widening, two badge map entries.
- `src/templates/reservations_app/homepage_list.twig` — one new `elseif` branch.
- `src/Application/Actions/ReservationsApp/UpdateReservation.php` — one new whitelist entry.

No migration, no entity change, no repository change, no DI binding change, no new test.

## Testing

Manual verification (no test suite per `CLAUDE.md`):

1. Open an existing PENDING reservation. The offcanvas shows four status buttons including the new "Δεν ήρθε" in gray.
2. Click "Δεν ήρθε" → save. The card disappears from the sidebar AND from any grid placement immediately.
3. Reload — the change persists.
4. Switch the reservation back to PENDING via the offcanvas → save. Sidebar and grid recover.
5. Open the list view (`/reservations-app/homepage-list`). The reservation's status button now reads "ΔΕΝ ΗΡΘΕ" when its status is NO_SHOW.
6. From devtools: POST `{ status: 'FOO' }` to `/reservations-app/update?id=…`. Whitelist rejects it (status unchanged on reload). Same posture as before for PENDING/ARRIVED/CANCELLED.
