# Customer-Facing Take-Out Mini Site — Design

**Date:** 2026-06-12
**Status:** Approved

## Summary

A public, unauthenticated mini site inside the sop codebase where customers browse the
active take-out menu, build a cart, and submit a pickup order. Orders land in a pending
queue; staff accept (with an ETA) or reject them from the existing take-out app. Accepted
requests become real `Order` records via the same logic the staff take-out flow uses.
Payment happens at pickup — no online payment.

## Decisions

| Topic | Decision |
|---|---|
| Hosting | Inside sop, new public route group (no auth middleware) |
| Order flow | Pending queue; staff accepts/rejects; real `Order` created on accept |
| Payment | At pickup only |
| Customer identity | Name + phone; token-based status page; no accounts |
| Pickup timing | ASAP; staff sets ETA (minutes) on acceptance |
| Cart options | Quantity + extras per item; one order-level note (no per-item notes) |
| Languages | Greek + English toggle (gettext `|_` + `MenuItemTranslation`) |
| Architecture | New `TakeOutRequest` entity family, converted to `Order` on accept |
| Customer UI | Single-scroll menu page, category chips, sticky cart bar (mobile-first) |

## Data Model

Three new entities mirroring the `Order`/`OrderEntry`/`OrderEntryExtra` shape but fully
separate from it. One standalone migration script in `migrations/` creates the tables.

**`TakeOutRequest`** (`take_out_requests`)
- `id`, `createdAt`
- `token` — random UUID; the status URL credential
- `customerName`, `customerPhone`
- `notes` — order-level note
- `status` — enum `PENDING` / `ACCEPTED` / `REJECTED`
- `etaMinutes` — nullable int, set by staff on acceptance
- `respondedAt` — nullable timestamp of accept/reject
- `order` — nullable one-to-one FK to `Order`, set on acceptance

**`TakeOutRequestEntry`** (`take_out_request_entries`)
- `request` FK, `menuItem` FK, `menuItemPrice` (snapshotted at submission), `quantity`

**`TakeOutRequestEntryExtra`** (`take_out_request_entry_extras`)
- `entry` FK, `name`, `price` (snapshotted)

Prices are snapshotted at submission so the total the customer saw never changes.

## Public Routes & Customer Actions

New unauthenticated group in `app/routes.php` (no `Authentication`/`Authorization`
middleware, like `/login`):

```
GET  /order/                     CustomerSite\Menu        — ordering page
POST /order/submit               CustomerSite\SubmitRequest
GET  /order/status/{token}       CustomerSite\Status      — status page
GET  /order/status/{token}/poll  CustomerSite\StatusPoll  — JSON for auto-refresh
```

Actions: `src/Application/Actions/CustomerSite/`.
Templates: `src/templates/customer_site/` with a dedicated lightweight mobile-first
skeleton (not the staff `app_skeleton.twig`), Bootstrap + Vue (CDN), el/en language toggle.

**Menu page** — loads the active menu of type `take_out` with sections, items, prices,
extras. Sold-out tracked items (`trackAvailableQuantity` and quantity 0) render visible
but not addable. Layout: horizontal category chips that jump to sections, item rows with
a + button, sticky bottom bar opening the cart/checkout drawer. Cart state in Vue +
`localStorage` so refresh doesn't wipe it.

**Checkout drawer** — cart summary with extras and total, name, phone, optional order
note, "you pay at pickup" notice, send button.

**SubmitRequest** — server-side validation: items exist and belong to the take-out menu,
quantities 1–20, name/phone non-empty, phone plausible. Prices come from the DB, never
the client. On success: store request, return status URL; browser redirects there and
stores the token in `localStorage` so returning customers land back on their status page.

**Status page** — polls the JSON endpoint every ~10s while `PENDING`. States:
- Waiting: "Waiting for the restaurant…" + order summary
- Accepted: "Ready in about N min", ticket number, "pay at pickup"
- Rejected: "The restaurant couldn't take your order, please call us"

## Staff Side (existing take-out app)

- `/take-out/` homepage gains a "Pending online orders" section: customer name, phone,
  items, total, note, age of request. Polls every ~15s; plays a sound on new arrivals.
- **Accept** — staff picks ETA (quick buttons 15/25/40 min or custom). Creates a real
  `Order` exactly like the staff `CreateOrder` flow: ticket number, waiter = accepting
  staff member, quantity decrement for tracked items, ECR job. Links the order to the
  request, sets status `ACCEPTED`. Afterwards the order is handled like any staff-created
  take-out order (payment at pickup via existing flow).
- **Reject** — one tap, status `REJECTED`. No reason field in v1.

New authenticated routes in the `/take-out` group:
`POST /take-out/requests/{id}/accept`, `POST /take-out/requests/{id}/reject` — plus
permissions-table entries (authorization is path-based).

**Shared logic:** extract the order-creation logic from `CreateOrder.php` into a service
(`TakeOutOrderFactory`) used by both the staff create flow and the accept flow, so ticket
numbering / ECR / quantity logic lives in one place.

## Edge Cases & Security

- **Item runs out before acceptance** — accept flow re-checks tracked quantities; if
  insufficient, staff UI warns; expected move is reject + phone call. No partial accepts.
- **Menu changes between browse and submit** — submission validates against current DB
  state; deactivated items produce a clear "items no longer available" error.
- **Spam/abuse** — rate limit (~3 pending requests per phone), honeypot form field,
  quantity caps. No CAPTCHA in v1.
- **Token security** — UUID token in status URL; page shows name but masks the phone.
- **Opening hours** — out of scope; staff reject orders that arrive while closed.

## Out of Scope (v1)

Online payment, customer accounts, SMS/email notifications, delivery, customer-side
cancellation, opening-hours gating.

## Verification

No test suite exists; verify manually via DDEV:
1. Place an order on `/order/` (with extras, note, both languages).
2. See it appear in `/take-out/` pending section (with sound).
3. Accept with ETA → ticket number + ECR job created, tracked quantities decremented,
   status page flips to accepted with ETA and ticket number.
4. Reject path → status page flips to rejected.
5. Sold-out item → not addable on menu page; submission with stale cart rejected cleanly.
6. Status URL with wrong token → 404.
