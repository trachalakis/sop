# Edit reservation status from the offcanvas form

## Summary

Add a status picker to the reservations offcanvas form (visible only in update mode). The picker is a three-button group covering `PENDING`, `ARRIVED`, and `CANCELLED`, color-coded to match existing badge styles. The change is persisted through the existing `UpdateReservation` action and reflected immediately in the sidebar via the existing reactive update path.

## Goals

- Let staff change a reservation's status without leaving the grid view.
- Keep the change consistent with how every other field is edited (same offcanvas, same submit button).
- Update the sidebar status badge and the pending filter immediately on save, no reload.

## Non-goals

- No timestamping of status changes (no `arrivedAt`, no audit log).
- No side effects on table assignments when a reservation is cancelled.
- No status field for the create flow — new reservations always start `PENDING` as today.
- No status quick-action on sidebar cards (only the offcanvas form).
- No change to the non-JSON `/reservations-app/update` path's form template (`update_reservation.twig`). The backend whitelist still applies there for safety, but no UI changes.

## Status values

The reservation entity uses three string status values:

| Value | Greek label (form button) | Existing badge color class |
|-------|---------------------------|----------------------------|
| `PENDING` | "Εκκρεμής" | `bg-warning text-dark` |
| `ARRIVED` | "Ήρθε" | `bg-success` |
| `CANCELLED` | "Ακύρωση" | `bg-danger` |

`CANCELLED` reservations are already filtered out of `activeReservations` in `homepage.twig`, so changing a reservation to `CANCELLED` removes it from both the sidebar and the grid placement on the next reactive tick.

## Frontend changes (`src/templates/reservations_app/homepage.twig`)

### Form data

Add `status: ''` to the `form` initializer in Vue `data()`:

```js
form: {
    id: null,
    name: '',
    telephoneNumber: '',
    adults: 2,
    minors: 0,
    time: '',
    date: '',
    table: '',
    tables: [],
    comments: '',
    status: '',
},
```

### `openUpdate(reservation)`

Populate the new field from the reservation:

```js
this.form = {
    id: reservation.id,
    name: reservation.name,
    telephoneNumber: reservation.telephoneNumber ?? '',
    adults: reservation.adults,
    minors: reservation.minors,
    time: dt.substring(11, 16),
    date: dt.substring(0, 10),
    table: reservation.tables?.[0] ?? '',
    tables: reservation.tables ? [...reservation.tables] : [],
    comments: reservation.comments ?? '',
    status: reservation.status,
};
```

`submitCreate()` does NOT pass status. The server keeps the existing `PENDING` default on creation.

### Markup

A new `<div class="mb-3">` block placed at the top of the form body, immediately after `<div class="offcanvas-body">` and `<div v-if="formError" class="alert alert-danger">{{formError}}</div>`, before the "Όνομα" block:

```html
<div v-if="formMode === 'update'" class="mb-3">
    <label class="form-label fs-5">Κατάσταση</label>
    <div class="d-flex gap-2">
        <input type="radio" class="btn-check" id="f-status-PENDING" value="PENDING" v-model="form.status">
        <label class="btn fs-4 btn-outline-warning flex-fill" for="f-status-PENDING">Εκκρεμής</label>

        <input type="radio" class="btn-check" id="f-status-ARRIVED" value="ARRIVED" v-model="form.status">
        <label class="btn fs-4 btn-outline-success flex-fill" for="f-status-ARRIVED">Ήρθε</label>

        <input type="radio" class="btn-check" id="f-status-CANCELLED" value="CANCELLED" v-model="form.status">
        <label class="btn fs-4 btn-outline-danger flex-fill" for="f-status-CANCELLED">Ακύρωση</label>
    </div>
</div>
```

- `v-if="formMode === 'update'"` keeps it hidden during create.
- `flex-fill` on each label makes the three buttons share the row evenly.
- `btn-outline-{warning,success,danger}` matches the existing status badge palette so the colors are familiar.
- The radio uses the standard `btn-check` pattern that the rest of the form uses for adults/time/date.

### `submitUpdate()`

Add `status` to the POST body and to the local reactive update spread:

```js
axios.post(`/reservations-app/update?id=${this.form.id}`, {
    name: this.form.name,
    telephoneNumber: this.form.telephoneNumber,
    adults: this.form.adults,
    minors: this.form.minors,
    time: this.form.time,
    date: this.form.date,
    tables: this.form.tables,
    comments: this.form.comments,
    status: this.form.status,
}).then(() => {
    const idx = this.reservations.findIndex(r => r.id === this.form.id);
    if (idx !== -1) {
        if (this.form.date !== DATE_STR) {
            this.reservations.splice(idx, 1);
        } else {
            this.reservations.splice(idx, 1, {
                ...this.reservations[idx],
                name: this.form.name.toUpperCase(),
                telephoneNumber: this.form.telephoneNumber,
                adults: this.form.adults,
                minors: this.form.minors,
                dateTime: `${this.form.date} ${this.form.time}:00`,
                tables: this.form.tables,
                comments: this.form.comments.toUpperCase(),
                status: this.form.status,
            });
        }
    }
    // ... rest of existing handler unchanged
});
```

Adding `status` to the spread propagates the change to all derived computeds:
- `activeReservations` (filters CANCELLED) — cancelled reservations disappear from the sidebar AND from grid placement.
- `visibleReservations` — switching to ARRIVED removes the card from the sidebar when the "pending only" filter is on.
- `statusBadgeClass` / `statusLabel` on the card — color and label refresh.

## Backend changes (`src/Application/Actions/ReservationsApp/UpdateReservation.php`)

The action currently has two branches: a JSON branch (used by the sidebar offcanvas form) and a non-JSON branch (used by the legacy standalone update page). Both branches receive the same field list and currently do NOT touch status.

Add the same `setStatus` line in each branch, guarded by a whitelist:

```php
$allowed = ['PENDING', 'ARRIVED', 'CANCELLED'];
if (isset($requestData['status']) && in_array($requestData['status'], $allowed, true)) {
    $reservation->setStatus($requestData['status']);
}
```

Placement: directly before `$this->reservationsRepository->persist($reservation);` in each branch.

Behavior:
- If the client sends a valid status string, it is applied.
- If the client sends nothing or sends garbage, the reservation's current status is kept (no crash, no silent corruption).
- The whitelist is hardcoded; introducing a new status value later would also require adding it here. That's acceptable — three status values are unlikely to grow often, and the entity's column already accepts arbitrary strings, so this is the only guard.

The non-JSON branch already redirects to `/reservations-app/`. The JSON branch already returns `{"success": true}`. Neither response shape changes.

## Out of scope, restated

- No new entity column, no migration.
- No new repository method.
- No DI binding change.
- No automated tests (project has no test suite).
- No edit to `update_reservation.twig` (the legacy standalone update page).

## Edge cases

- **User opens an old reservation that has a status string outside the three values.** The form's radio group leaves all three buttons unchecked (since `v-model="form.status"` won't match any value). The user can still pick one and save. Until they do, the existing status persists server-side. Acceptable — no such garbage values exist today.
- **User changes status to CANCELLED and saves.** The local reactive update sets `status: 'CANCELLED'` on the reservation; `activeReservations` filters it out; the sidebar card disappears; the grid placement disappears (because `getCellReservation` reads from `activeReservations` via the same pipeline).
- **User changes status to ARRIVED while the pending filter is on.** The card disappears from the sidebar but stays in the grid placement. Toggling the "Όλες" switch brings it back into the sidebar.
- **User opens the offcanvas in create mode.** No status field is rendered (`v-if="formMode === 'update'"`). `submitCreate` ignores `form.status` (does not include it in the POST body). Server creates with `PENDING` as today.
- **Concurrent edit.** No locking is added. If two staff edit the same reservation, last-write-wins, same as every other field on this form today.

## Testing

Manual verification (no test runner per `CLAUDE.md`):

1. Click the pencil on an existing reservation card in the sidebar. The offcanvas opens with the new "Κατάσταση" field at the top, the current status button highlighted.
2. Click "Ήρθε". The yellow button deselects, the green button highlights.
3. Click "Αποθήκευση". The offcanvas closes. The card in the sidebar shows the green "ΗΡΘΕ" badge. With the pending filter on, the card disappears entirely.
4. Reopen the same reservation: the green "Ήρθε" button is highlighted, confirming persistence to the database.
5. Change to "Ακύρωση" and save. The card disappears from both the sidebar AND the grid placement immediately.
6. Open create mode via the "+" button. The "Κατάσταση" field is NOT rendered.
7. Create a new reservation. It appears with the yellow "ΑΝΑΜ." badge (default PENDING).
8. Use the browser dev tools to POST a garbage status (`{"status":"FOO"}`) to `/reservations-app/update?id=…`. The server returns success but the reservation's status is unchanged. Verify by reloading and confirming the original status persists.
