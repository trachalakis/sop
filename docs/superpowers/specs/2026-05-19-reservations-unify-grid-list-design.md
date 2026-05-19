# Unify reservations grid + list into one responsive template

## Summary

Merge the reservations grid view (`/reservations-app/`) and list view (`/reservations-app/homepage-list`) into a single Vue-driven template that renders the appropriate layout based on the viewport width. The mobile-redirect JavaScript and the separate list template/action/route are removed. Mobile users edit reservations via the existing offcanvas instead of the standalone update page.

## Goals

- One canonical URL (`/reservations-app/`) for both desktop and mobile.
- One template (`homepage.twig`) that adapts its layout via Bootstrap's responsive utility classes — no client-side redirect, no flash.
- Mobile users get the same data shape, the same Vue methods, and the same offcanvas as desktop users.

## Non-goals

- No change to the offcanvas form, the `submitCreate` / `submitUpdate` flow, or any reservation-edit semantics.
- No change to the desktop grid, sidebar, status filter, or click-to-place flow.
- No change to the standalone `/reservations-app/create` and `/reservations-app/update` pages or their templates (`create_reservation.twig`, `update_reservation.twig`). They keep working for direct navigation; the mobile UI just stops pointing at them.
- No change to the cutoff filter that governs grid columns (`activeReservations`). The mobile list keeps the existing "no cutoff" behavior of `sidebarReservations`.
- No new functional dropdown for changing status from inside a row — status changes are made via the offcanvas only (matches today's desktop behavior).
- No backwards compatibility for the `/reservations-app/homepage-list` URL — after this change it returns 404.

## Routing changes

In `app/routes.php`, remove the line:

```php
$group->get('/homepage-list', ReservationsAppHomepageList::class);
```

…from the `/reservations-app` group, and remove the unused `ReservationsAppHomepageList` import alias if no other consumer remains.

The route stays a 404. Bookmarks and external links pointing at `/homepage-list` break — intentional per scope decision.

## Files removed

- `src/Application/Actions/ReservationsApp/HomepageList.php` — deleted in full.
- `src/templates/reservations_app/homepage_list.twig` — deleted in full.

## Data shape change (`Homepage.php`)

Add a single field to the JSON serialization so the mobile list can render the table-lock indicator. In `src/Application/Actions/ReservationsApp/Homepage.php`, extend the `array_map` lambda:

```php
$reservationsData = array_values(array_map(function ($r) {
    return [
        'id'              => $r->getId(),
        'name'            => $r->getName(),
        'adults'          => $r->getAdults(),
        'minors'          => $r->getMinors(),
        'dateTime'        => $r->getDateTime()->format('Y-m-d H:i:s'),
        'comments'        => $r->getComments() ?? '',
        'status'          => $r->getStatus(),
        'tables'          => $r->getTables() ?? [],
        'telephoneNumber' => $r->getTelephoneNumber() ?? '',
        'isTableLocked'   => $r->getIsTableLocked(),
    ];
}, $reservations));
```

The `getIsTableLocked()` accessor returns `bool` (verified in `src/Domain/Entities/Reservation.php:83`).

## Template changes (`homepage.twig`)

### Remove the mobile redirect

At the top of `{% block content %}{% verbatim %}`, delete the existing inline script:

```html
<script>
    if (window.matchMedia('(max-width: 768px)').matches) {
        ...location.replace...
    }
</script>
```

Mobile clients now render the page directly.

### Remove the list-view header link

In the page header's right-side button cluster, remove the `<a>` with `bi-list-ul` (currently links to `/reservations-app/homepage-list?date=…`). The remaining cluster is: sidebar collapse chevron + create "+" button.

### Hide the sidebar chevron on mobile

The sidebar collapse chevron has no meaning when there is no sidebar. Add Bootstrap responsive classes so it renders only on `md+`:

```html
<button @click="sidebarOpen = !sidebarOpen"
        class="btn btn-outline-secondary btn-lg me-2 d-none d-md-inline-block"
        :title="sidebarOpen ? 'Απόκρυψη κρατήσεων' : 'Εμφάνιση κρατήσεων'">
    <i class="bi" :class="sidebarOpen ? 'bi-chevron-double-right' : 'bi-chevron-double-left'"></i>
</button>
```

### Gate the desktop body on `md+`

The existing `.home-body` div (which contains the grid wrapper and the desktop sidebar) gets Bootstrap utility classes that swap its `display` from `flex` to `none` on `< md`:

```html
<div class="home-body d-none d-md-flex" :class="{ 'sidebar-collapsed': !sidebarOpen }">
    ... existing grid + sidebar markup, unchanged ...
</div>
```

Bootstrap's `.d-md-flex` overrides our CSS `.home-body { display: flex; }` so the responsive class wins. The custom `.sidebar-collapsed` class continues to drive the collapse animation on desktop.

### Add the mobile body

After the closing `</div>` of `.home-body`, insert the new mobile block:

```html
<!-- Mobile body: list -->
<div class="mobile-list d-md-none">
    <div class="mobile-list-header">
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

    <table class="table table-striped fs-4 mt-4">
        <tbody>
            <tr v-for="r in sidebarReservations" :key="r.id">
                <td>
                    <a class="text-decoration-none" href="#" @click.prevent="openUpdate(r)">
                        {{r.dateTime.substring(11, 16)}} /
                        {{r.adults}} / {{r.minors}} /
                        {{r.name}}
                    </a>
                    <div>
                        <a v-for="t in r.tables" :key="t"
                           :href="'/reservations-app/toggle-table-lock?reservationId=' + r.id"
                           class="badge text-bg-info text-decoration-none">
                            {{t}}<span v-if="r.isTableLocked">!</span>
                        </a>
                    </div>
                    <div v-if="r.comments">
                        <small class="text-body-secondary">{{r.comments}}</small>
                    </div>
                </td>
                <td class="text-end">
                    <span class="badge" :class="statusBadgeClass(r.status)">
                        {{statusLabel(r.status)}}
                    </span>
                </td>
            </tr>
            <tr v-if="sidebarReservations.length === 0">
                <td colspan="2" class="text-muted text-center py-4">
                    <span v-if="statusFilter">
                        Δεν υπάρχουν κρατήσεις σε αυτή την κατάσταση.
                    </span>
                    <span v-else>
                        Δεν υπάρχουν κρατήσεις για αυτή την ημέρα.
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

Notes:
- The 4-button status filter is a markup duplicate of the desktop sidebar's group. Both bind to the same Vue `statusFilter` field via the same `toggleStatusFilter` method, so they stay in sync automatically (only one is visible at a time anyway).
- `sidebarReservations` is the same computed that feeds the desktop sidebar — no cutoff, status-filtered.
- The row layout preserves today's list-view look: "time / adults / minors / name" on the left, table badges below, comments below that, status badge on the right.
- The clickable "edit" affordance is the name+time line. `@click.prevent="openUpdate(r)"` opens the offcanvas instead of navigating to `/update`. The empty `href="#"` exists for keyboard accessibility (`<a>` is focusable) — `.prevent` suppresses the default navigation.
- The table-lock toggle stays a plain `<a>` link, same as today's list view. Clicking it navigates to `/toggle-table-lock?reservationId=…`, which redirects back. No Vue handler.
- Empty-state branches mirror the desktop sidebar's filter-aware wording.

### CSS addition

Add one small CSS rule for the mobile filter strip (after the existing `.reservations-sidebar-header` block):

```css
.mobile-list-header {
    position: sticky;
    top: 0;
    background: white;
    padding: 8px 0;
    z-index: 1;
}
```

No new media queries — Bootstrap's `.d-none .d-md-flex` / `.d-md-none` utilities handle the layout swap at the `md` breakpoint (768px), matching the threshold the old redirect used.

## Behavior summary

| Viewport | Renders | Sidebar | Filter buttons | Edit action |
|----------|---------|---------|----------------|-------------|
| < 768px (mobile) | `mobile-list` only | Hidden (no DOM) | At top of mobile list | Row click → offcanvas |
| ≥ 768px (desktop / tablet) | `home-body` only (grid + sidebar) | Inside sidebar | At top of sidebar | Pencil button → offcanvas |

Both layouts share `Vue.createApp`, all data, all methods, and the offcanvas. There is no flash of the wrong layout because CSS controls visibility from the first paint.

## Edge cases

- **Resize across the breakpoint.** Bootstrap's responsive classes re-evaluate at the media query boundary; if a desktop browser is narrowed past 768px, the mobile layout becomes visible while desktop hides. Vue state (`statusFilter`, `sidebarOpen`, `selectedReservationId`, offcanvas state) carries over. Acceptable.
- **Tablet portrait at exactly 768px.** Bootstrap's `md` breakpoint is `min-width: 768px`, inclusive. A tablet at exactly 768px gets the desktop layout. Devices at 767px or below get the mobile layout.
- **Offcanvas opened on mobile.** Bootstrap's offcanvas is viewport-aware; `width:min(520px,100vw)` already handles narrow screens. The mobile UX is functional today via the offcanvas (verified by inspecting the existing CSS).
- **`/reservations-app/homepage-list` bookmarked.** Returns 404 after this change. Accepted scope.

## Scope of change

Five files touched:

- `src/templates/reservations_app/homepage.twig` — remove redirect script, remove list-view header link, wrap sidebar chevron with `.d-none .d-md-inline-block`, gate `.home-body` with `.d-none .d-md-flex`, add the mobile body block, add the `.mobile-list-header` CSS rule.
- `src/Application/Actions/ReservationsApp/Homepage.php` — add `isTableLocked` to the JSON serialization.
- `app/routes.php` — remove the `/homepage-list` route registration (and the corresponding `use` alias if no other consumer).
- `src/Application/Actions/ReservationsApp/HomepageList.php` — deleted.
- `src/templates/reservations_app/homepage_list.twig` — deleted.

## Testing

Manual verification (no test suite per `CLAUDE.md`):

1. Desktop browser at 1280px width: page renders the existing grid + sidebar, identical to today. No mobile list visible. Sidebar chevron, "+" create button work. Status filter buttons in sidebar still filter the card list.
2. Resize the same browser to 600px width: grid + sidebar disappear; mobile list appears with the same status filter buttons at top, then a table of reservation rows. PENDING is highlighted by default. The list-view link button in the header is gone.
3. Tap a row's name+time link on mobile: the offcanvas opens with the reservation pre-filled. Save changes — offcanvas closes, row updates reactively (status badge color/text reflect new value; row disappears or stays based on `statusFilter`).
4. Tap "+" on mobile: offcanvas opens in create mode. Save — new reservation appears in the list.
5. Tap a table badge on mobile: page navigates to `/toggle-table-lock?reservationId=…` then back. Lock state (`!` suffix) flips on next render.
6. Click a status filter button on mobile: list narrows. Click the active one to clear → list shows all four statuses.
7. Empty-day with `?date=<empty-day>`: mobile list shows the empty-state with the right message based on `statusFilter`.
8. Visit `/reservations-app/homepage-list` directly: 404.
9. Visit `/reservations-app/` on a phone or device emulator: no redirect; the mobile list renders immediately.
