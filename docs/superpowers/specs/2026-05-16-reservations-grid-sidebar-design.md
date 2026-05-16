# Reservations grid: right-side reservations sidebar + mobile list default

## Summary

Two enhancements to the reservations app grid view (`/reservations-app/`):

1. Move the reservations panel from the bottom of the page into a collapsible sidebar on the right of the grid.
2. On small screens (≤768px), redirect users from the grid to the existing list view (`/reservations-app/homepage-list`) so phones land on the more readable layout by default.

Only `src/templates/reservations_app/homepage.twig` is touched. No PHP, routes, repositories, or other templates change.

## Goals

- Reservation cards are visible alongside the grid, not below it — easier to scan while placing.
- The grid can reclaim the horizontal space when the sidebar isn't needed.
- Phones default to the list view, which is already mobile-friendly.

## Non-goals

- No change to the underlying data, click-to-place behavior, status badges, or duration adjustment buttons.
- No change to `homepage_list.twig`, `create_reservation.twig`, or `update_reservation.twig`.
- No persistence of sidebar open/closed state — the sidebar is always open on page load.
- No escape hatch for phone users to view the grid; the mobile redirect is unconditional below 768px.

## Layout

The `.home-view` container stays a vertical flex column. The body below the header changes from a single full-width grid + bottom panel into a horizontal flex row:

```
.home-view (flex column, full height)
├── placement-banner (existing, conditional)
├── header (existing)
└── .home-body (flex row, flex:1, min-height:0, gap:12px)
    ├── .grid-wrapper (flex:1, min-width:0, scrolls — existing)
    └── aside.reservations-sidebar (width:300px, scrolls vertically)
```

When the sidebar is collapsed, the parent `.home-body` gains a `.sidebar-collapsed` class. CSS animates the sidebar's `width` to `0` (with `overflow:hidden` and `border-width:0`) over 0.2s, and the grid expands into the freed space because of its `flex:1` rule.

### CSS

Added to the existing `<style>` block in `homepage.twig`:

```css
.home-body {
    display: flex;
    flex: 1;
    min-height: 0;
    gap: 12px;
}
.grid-wrapper {
    flex: 1;
    min-width: 0; /* allows the grid to shrink when sidebar is open */
}
.reservations-sidebar {
    width: 300px;
    flex-shrink: 0;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    transition: width 0.2s ease;
}
.home-body.sidebar-collapsed .reservations-sidebar {
    width: 0;
    border-width: 0;
    overflow: hidden;
}
```

The existing `.reservations-panel` block (with `max-height:180px`) is removed.

## Toggle control

A single Vue `data` boolean `sidebarOpen: true` (always-open default). One button in the header, sitting next to the existing list-view and "+" buttons:

- Icon: `bi-chevron-double-right` when open, `bi-chevron-double-left` when closed (indicates the direction the sidebar will move).
- Class: `btn btn-outline-secondary btn-lg`.
- `@click="sidebarOpen = !sidebarOpen"`.

`.home-body` carries `:class="{ 'sidebar-collapsed': !sidebarOpen }"` so the CSS handles the rest. No persistence — every page load starts with the sidebar open.

## Sidebar contents

Same reservation cards, same data, same click-to-place behavior. Three layout adjustments:

- The cards wrapper changes from `d-flex flex-wrap gap-2` to `d-flex flex-column gap-2` — cards stack one per row.
- The per-card max-width constraint on the comment line (`max-width:160px`) is removed so cards fill the sidebar width.
- The header strip (title "Κρατήσεις" + unplaced badge + helper text) stays at the top of the sidebar and is sticky inside the scroll area (`position: sticky; top: 0; background: white;`) so it remains visible while the user scrolls the list.

The class `.reservations-panel` is renamed to `.reservations-sidebar` in the markup; references elsewhere are searched and updated if any exist (none expected — this class is local to `homepage.twig`).

## Mobile redirect

A small inline script runs at the top of `{% block content %}` in `homepage.twig`, before the Vue app's mount point and before any rendering work:

```html
<script>
  if (window.matchMedia('(max-width: 768px)').matches) {
    var params = new URLSearchParams(window.location.search);
    var date = params.get('date');
    var target = '/reservations-app/homepage-list';
    if (date) target += '?date=' + encodeURIComponent(date);
    window.location.replace(target);
  }
</script>
```

Behavior:

- Breakpoint `max-width: 768px` matches Bootstrap's `md` boundary, the conventional small-screen line in this app.
- `location.replace` (not `location.href = …`) so the back button doesn't bounce a phone user back to the grid.
- The selected `date` query param, if present, is forwarded so the user lands on the same day in the list view.
- Runs early enough that the grid never visibly renders on a phone.

`homepage_list.twig` is untouched and already mobile-friendly.

## Out-of-scope concerns and edge cases

- **Window resize across the breakpoint.** No live re-evaluation — the redirect only runs on initial page load. Resizing a desktop browser narrower than 768px does not redirect; this is intentional (rare desktop edge case, and reloading hits the redirect anyway).
- **Tablet portrait near 768px.** A tablet at exactly 768px stays on the grid (the boundary is `max-width: 768px`, which is inclusive of 768). A tablet at 767px or below redirects. Acceptable.
- **Sidebar scroll independence.** The sidebar has its own `overflow-y: auto`, separate from `.grid-wrapper`. Both scroll independently.
- **Placement banner.** The existing `.placement-banner` (shown when a reservation is selected for click-placement) stays at the top of `.home-view`, above the new `.home-body`. Unaffected by the sidebar toggle.

## Testing

Manual verification (no automated tests exist for this app):

- Desktop, sidebar open by default; cards visible vertically, click-to-place still works, status badges and duration buttons unchanged.
- Toggle button collapses the sidebar; grid expands; toggle again restores it. Animation is smooth.
- Date navigation (prev/next chevrons) and "+" create button still function with the new header layout.
- Phone-width browser load on `/reservations-app/?date=2026-05-16` immediately redirects to `/reservations-app/homepage-list?date=2026-05-16`; the date is preserved.
- Phone-width browser load on `/reservations-app/` with no date redirects to `/reservations-app/homepage-list` (no query string).
- Browser back from the list view after a mobile redirect does not return to the grid.

## Files changed

- `src/templates/reservations_app/homepage.twig` — CSS additions/removal, markup restructure inside `.home-view`, toggle button, inline mobile-redirect script, one new Vue `data` field (`sidebarOpen`).
