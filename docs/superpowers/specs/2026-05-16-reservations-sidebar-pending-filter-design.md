# Reservations sidebar: default to pending, toggle to show all

## Summary

In the reservations grid view sidebar (`/reservations-app/`), filter the visible reservation cards to status `PENDING` by default. Add a Bootstrap form-switch in the sidebar header labeled "Όλες" (All) that, when checked, shows all active reservations instead.

Only `src/templates/reservations_app/homepage.twig` is touched. No PHP, routes, repositories, or other templates change.

## Goals

- Reduce visual noise in the sidebar: staff usually care about pending reservations that still need attention; arrived guests don't.
- Keep arrived reservations one click away when needed.

## Non-goals

- No persistence — every page load starts in "pending only" mode.
- No change to how the grid renders placed reservations. The grid continues to show every active reservation regardless of the sidebar filter.
- No change to the `coverCount` headline, the `unplacedReservations` badge, or the "Όλες τοποθετημένες" success badge. Those stats reflect the whole day, independent of the sidebar filter.
- `CANCELLED` reservations stay hidden in both modes — the existing `activeReservations` computed already excludes them, and "show all" means "all active", not "include cancelled".
- No change to placement, drag, click-to-place, or off-canvas behavior.

## Status values

The reservation entity (`src/Domain/Entities/Reservation.php`) stores `status` as a string. The values in use, found via `grep`, are:

| Value | Label (Greek, abbreviated) | Existing badge class |
|-------|---------------------------|----------------------|
| `PENDING` | ΑΝΑΜ. | `bg-warning text-dark` |
| `ARRIVED` | ΗΡΘΕ | `bg-success` |
| `CANCELLED` | ΑΚΥΡ. | `bg-danger` (hidden via `activeReservations`) |

Default status on creation is `PENDING` (see `Application/Actions/ReservationsApp/CreateReservation.php`).

## State

Add one Vue `data` field:

```js
pendingOnly: true,
```

Placed next to `sidebarOpen: true,` to group sidebar-related state.

## New computed

Add `visibleReservations`, placed immediately after the existing `activeReservations` computed:

```js
visibleReservations() {
    if (!this.pendingOnly) return this.activeReservations;
    return this.activeReservations.filter(r => r.status === 'PENDING');
},
```

When `pendingOnly` is true, only `PENDING` cards show. When false, all entries from `activeReservations` show (`PENDING` + `ARRIVED`; `CANCELLED` remains excluded upstream).

## Sidebar header markup

The existing sidebar header contains a `d-flex` row with title + unplaced badge, followed by a helper-text line. The form-switch joins the title row on the right:

```html
<div class="reservations-sidebar-header">
    <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
        <h5 class="mb-0">Κρατήσεις</h5>
        <span v-if="unplacedReservations.length > 0" class="badge bg-warning text-dark">
            {{unplacedReservations.length}} αναθέτηση
        </span>
        <span v-else class="badge bg-success">
            <i class="bi bi-check-lg"></i> Όλες τοποθετημένες
        </span>
        <div class="form-check form-switch ms-auto mb-0">
            <input class="form-check-input" type="checkbox" id="showAllToggle"
                   v-model="pendingOnly" :true-value="false" :false-value="true">
            <label class="form-check-label small" for="showAllToggle">Όλες</label>
        </div>
    </div>
    <small class="text-muted">Κάνε κλικ για τοποθέτηση</small>
</div>
```

Key markup details:

- `ms-auto` pushes the switch to the right edge of the flex row.
- `:true-value="false"` / `:false-value="true"` invert the checkbox semantics so a checked box means "show all" (matching the label "Όλες") while the underlying state is named after the more common case (`pendingOnly` defaults true).
- The unique `id` on the checkbox pairs with the `<label for>` so clicking the label toggles the input — Bootstrap form-switch convention.

## Sidebar list and empty state

In the sidebar body, change the `v-for` source from `activeReservations` to `visibleReservations`:

```html
<div
    v-for="reservation in visibleReservations"
    :key="reservation.id"
    class="reservation-card border rounded p-2"
    :style="reservationCardStyle(reservation)"
    @click="selectReservation(reservation)"
>
    ...existing card markup unchanged...
</div>
```

The empty-state branch becomes conditional on the active filter so the message matches what's happening:

```html
<div v-if="visibleReservations.length === 0" class="text-muted mt-3">
    <span v-if="pendingOnly && activeReservations.length > 0">
        Δεν υπάρχουν εκκρεμείς κρατήσεις.
    </span>
    <span v-else>
        Δεν υπάρχουν κρατήσεις για αυτή την ημέρα.
    </span>
</div>
```

- If the day has no active reservations at all, the second branch fires (same wording as today).
- If the day has active reservations but none are `PENDING` and the filter is on, the new "Δεν υπάρχουν εκκρεμείς κρατήσεις" message fires — explains why the list looks empty.
- If the filter is off and there are no active reservations, second branch (same as today).

## What does NOT change

- The grid table renders all placed reservations regardless of the filter — placement decisions persist visually.
- `unplacedReservations` continues to derive from `activeReservations`, so the badge counts all unplaced active reservations regardless of filter.
- `coverCount` continues to sum over `activeReservations`.
- `selectReservation`, `placedReservationIds`, `clickCell`, duration adjustment buttons — all unchanged.
- Click-to-place still works on cards visible in the sidebar. If the user has placed a `PENDING` reservation, it stays in the sidebar list (matching today's behavior, since placement doesn't change status).

## Edge cases

- **All reservations have `ARRIVED` status, filter on.** Sidebar shows the "Δεν υπάρχουν εκκρεμείς κρατήσεις" empty state. Toggling "Όλες" on reveals the cards. Acceptable.
- **Filter on, user marks a reservation `ARRIVED` (via the update off-canvas).** The card disappears from the sidebar on the next render. The grid placement stays. This is the desired behavior — the filter is reactive.
- **No reservations for the day at all.** Empty state shows the existing wording. Switch is still visible and toggleable but has no effect. Acceptable.
- **Filter on, sidebar collapsed.** No interaction change; toggling the sidebar back open shows the filtered list. No re-evaluation needed.

## Scope of change

One file: `src/templates/reservations_app/homepage.twig`.

- One new line in Vue `data()`: `pendingOnly: true,`.
- One new computed (~4 lines): `visibleReservations`.
- One new `<div class="form-check form-switch …">` block in the sidebar header.
- One identifier change in the card `v-for`: `activeReservations` → `visibleReservations`.
- The empty-state `<div>` rewritten with two branches.

No PHP, no entity, no route, no other template changes.

## Testing

Manual verification (no test suite per `CLAUDE.md`):

- Page loads with the switch off ("Όλες" unchecked). Sidebar shows only `PENDING` cards.
- Toggling the switch on reveals additional `ARRIVED` cards interleaved by time.
- Toggling the switch off again hides them; pending list returns.
- Click-to-place still works from any card visible in the filter.
- Update a reservation's status to `ARRIVED` via the off-canvas: the card disappears from the filtered sidebar; the grid cell stays as-is.
- A day with only `ARRIVED` reservations and the filter on shows the new "Δεν υπάρχουν εκκρεμείς κρατήσεις" message.
- Reload preserves nothing — switch is back to off on every load.
- Sidebar collapse + filter combine correctly: state is independent.
