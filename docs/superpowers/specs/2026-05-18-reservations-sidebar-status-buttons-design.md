# Reservations sidebar: status-filter button group

## Summary

Replace the existing "Όλες" form-switch in the reservations grid sidebar with a 4-button single-select status filter. Each button corresponds to one reservation status (`PENDING`, `ARRIVED`, `NO_SHOW`, `CANCELLED`). Clicking a button filters the sidebar list to that status; clicking the active button again clears the filter. When no button is active, the sidebar shows every reservation for the day — all four statuses, with no time cutoff.

The sidebar and the grid placement now derive from different lists. The grid keeps its existing behavior (only `activeReservations` get cells, excluding CANCELLED + NO_SHOW + past slots).

Only `src/templates/reservations_app/homepage.twig` is touched.

## Goals

- Give staff one-click access to any subset of reservations by status.
- Show the full day's reservations by default, no time cutoff, including cancelled and no-show records.
- Replace the binary "Όλες" toggle with a 4-way control without expanding the sidebar header's footprint.

## Non-goals

- No change to the grid table, `activeReservations`, `unplacedReservations`, `coverCount`, `initCells`, or any placement logic. CANCELLED + NO_SHOW reservations remain absent from grid cells.
- No change to the page-header sidebar collapse chevron — it stays.
- No persistence of the filter — every page load starts with `statusFilter: null` (show all).
- No counts on the filter buttons.
- No multi-select; single-select only.
- No change to the click-to-place flow on individual cards. Clicking a CANCELLED or NO_SHOW card in the sidebar enters placement mode the same as any other card; the placement won't take effect on the grid because `initCells` still filters those statuses out. This is a harmless dead-end; out of scope to redesign.

## State changes

Replace the existing Vue data field:

```js
pendingOnly: true,
```

with:

```js
statusFilter: null,
```

`statusFilter` is either `null` (no filter, show all) or one of the four status strings (`'PENDING'`, `'ARRIVED'`, `'NO_SHOW'`, `'CANCELLED'`).

## New computed `sidebarReservations`

Replace the existing `visibleReservations` computed (which derived from `activeReservations` and therefore excluded CANCELLED + NO_SHOW) with `sidebarReservations`:

```js
sidebarReservations() {
    if (this.statusFilter) {
        return this.reservations.filter(r => r.status === this.statusFilter);
    }
    return this.reservations;
},
```

This intentionally bypasses `activeReservations`. The sidebar shows every reservation of the day regardless of status or time. The grid continues to derive from `activeReservations`, keeping its CANCELLED/NO_SHOW exclusion and time cutoff.

## New toggle method

In the Vue `methods:` block, add:

```js
toggleStatusFilter(status) {
    this.statusFilter = this.statusFilter === status ? null : status;
},
```

Click an inactive button → filter to that status. Click the active button → return to "no filter" (show all).

## Sidebar header markup

Replace the existing sidebar header content (currently a `d-flex` row with the now-redundant title plus the `Όλες` form-switch) with a 4-button row using Bootstrap's `btn-group`:

```html
<div class="reservations-sidebar-header">
    <div class="btn-group w-100" role="group">
        <button @click="toggleStatusFilter('PENDING')"
                class="btn btn-sm"
                :class="statusFilter === 'PENDING' ? 'btn-warning text-dark' : 'btn-outline-warning'">
            Εκκρεμής
        </button>
        <button @click="toggleStatusFilter('ARRIVED')"
                class="btn btn-sm"
                :class="statusFilter === 'ARRIVED' ? 'btn-success' : 'btn-outline-success'">
            Ήρθε
        </button>
        <button @click="toggleStatusFilter('NO_SHOW')"
                class="btn btn-sm"
                :class="statusFilter === 'NO_SHOW' ? 'btn-secondary' : 'btn-outline-secondary'">
            Δεν ήρθε
        </button>
        <button @click="toggleStatusFilter('CANCELLED')"
                class="btn btn-sm"
                :class="statusFilter === 'CANCELLED' ? 'btn-danger' : 'btn-outline-danger'">
            Ακύρωση
        </button>
    </div>
</div>
```

Details:

- `btn-group w-100` stretches the four buttons evenly across the 300px sidebar.
- `btn-sm` keeps them compact so each fits the short Greek label without truncation (~75px each).
- Active state uses the filled status color from the existing badge palette: `btn-warning text-dark` / `btn-success` / `btn-secondary` / `btn-danger`. The `text-dark` is needed on the warning button so the dark-on-yellow contrast matches the existing PENDING badge.
- Inactive state uses the outline equivalent.
- Button order matches the form picker and the list view: `PENDING → ARRIVED → NO_SHOW → CANCELLED`.

The `<h5>Κρατήσεις</h5>` title and any leftover badge slots from the previous header design are removed in the replacement; the button group is the sole content of `.reservations-sidebar-header`.

## Sidebar body

The card iteration switches source:

```html
<div
    v-for="reservation in sidebarReservations"
    :key="reservation.id"
    ...
>
```

The card's inner markup, click handler, color, and pencil-edit affordance are unchanged.

The empty-state below the card list becomes filter-aware:

```html
<div v-if="sidebarReservations.length === 0" class="text-muted mt-3">
    <span v-if="statusFilter">
        Δεν υπάρχουν κρατήσεις σε αυτή την κατάσταση.
    </span>
    <span v-else>
        Δεν υπάρχουν κρατήσεις για αυτή την ημέρα.
    </span>
</div>
```

- When a filter is active and produces no matches: "Δεν υπάρχουν κρατήσεις σε αυτή την κατάσταση." (No reservations in this status.)
- When no filter is active and there are no reservations for the day: original wording, unchanged.

## What does NOT change

- Page header (date navigation, list-view link, sidebar collapse chevron, "+" create button).
- `activeReservations`, `unplacedReservations`, `coverCount`, `placedReservationIds`, `tableRows`, `initCells`.
- Click-to-place flow (`selectReservation`, `clickCell`).
- Status badge color / label maps (`statusBadgeClass`, `statusLabel`) — these still drive the per-card badge inside each reservation card.
- Offcanvas form (status picker, save logic).
- `submitUpdate` reactive spread — when a status changes, the reactive cascade updates `sidebarReservations` automatically because the underlying `reservations` array gets the new value. The currently-active filter button (if any) keeps its meaning.

## Edge cases

- **Active filter, user changes a reservation to that status from the offcanvas.** The card appears in the sidebar after save (reactive update).
- **Active filter, user changes the only matching reservation to a different status.** The card disappears from the sidebar; the empty-state "Δεν υπάρχουν κρατήσεις σε αυτή την κατάσταση." renders. User can click another button or click the active one again to clear the filter.
- **No filter, day has no reservations.** Empty-state shows the original "για αυτή την ημέρα." text.
- **User clicks PENDING then ARRIVED quickly.** Single-select replaces the filter — PENDING deactivates, ARRIVED activates. No flash of empty state because Vue re-renders synchronously.
- **Cancelled / no-show card clicked.** Same as today's behavior for any card: enters placement mode. The placement won't take because `initCells` filters these statuses. Harmless dead-end, intentionally not addressed here.
- **Sidebar collapsed when filter is active.** The chevron toggle hides the sidebar; the filter state persists in `statusFilter`. Reopening the sidebar shows the filtered list.

## Scope of change

One file: `src/templates/reservations_app/homepage.twig`.

- Vue `data`: replace `pendingOnly: true` with `statusFilter: null`.
- Vue `computed`: remove `visibleReservations`; add `sidebarReservations`.
- Vue `methods`: add `toggleStatusFilter(status)`.
- Markup: replace `.reservations-sidebar-header` inner contents (drop the `<h5>` title and `<div class="form-check form-switch …">` switch; add the new `btn-group`).
- Markup: change the card `v-for` from `visibleReservations` to `sidebarReservations`.
- Markup: rewrite the empty-state two-branch block to reflect the new filter semantics (using `statusFilter` truthiness rather than the old `pendingOnly && activeReservations.length > 0` form).

## Testing

Manual verification (no test suite per `CLAUDE.md`):

1. Page loads with all four buttons in outline state. Sidebar lists every reservation for the day (PENDING + ARRIVED + NO_SHOW + CANCELLED), no time cutoff.
2. Click "Εκκρεμής" — the button fills yellow; sidebar narrows to PENDING only.
3. Click "Ήρθε" — yellow button outlines again, green fills; sidebar shows ARRIVED only.
4. Click "Ήρθε" again — outline; sidebar returns to showing all four statuses.
5. Click "Δεν ήρθε" with no NO_SHOW reservations — sidebar shows "Δεν υπάρχουν κρατήσεις σε αυτή την κατάσταση."
6. Open a reservation, change status to ARRIVED, save while the ARRIVED filter is active — the card appears in the sidebar reactively.
7. Page-header chevron still collapses/expands the sidebar; the active filter persists across collapse/reopen.
8. Grid placement: CANCELLED and NO_SHOW reservations visible in the sidebar do NOT appear in any grid cell. Trying to place one (click card → click cell) does nothing visible.
9. Reload the page — `statusFilter` resets to `null`. No persistence.
