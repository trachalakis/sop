# Invoice Scanning & Supply Alias — Design Spec

**Date:** 2026-04-16

## Overview

Allow staff to photograph supplier invoices on their phones, have the app extract and store the line item data via Claude's vision API, and progressively map invoice descriptions to known Supply entities via SupplyAlias records. Price trends per alias are visualised with a Chart.js graph.

---

## Data Model

### `invoices` table — one row per scanned invoice

| column | type | notes |
|---|---|---|
| `id` | integer PK | auto-increment |
| `supplier_id` | integer FK → suppliers | resolved automatically; user picks if ambiguous |
| `date` | date | extracted from invoice |
| `invoice_number` | varchar | nullable; extracted from invoice |
| `scanned_at` | timestamptz | set on confirm |

### `invoice_entries` table — one row per line item

| column | type | notes |
|---|---|---|
| `id` | integer PK | auto-increment |
| `invoice_id` | integer FK → invoices | not null |
| `description` | varchar | exactly as it appears on the invoice |
| `quantity` | float | not null |
| `unit_price` | float | not null |
| `extras` | JSON | unit, VAT rate, line total, anything else Claude extracts |
| `supply_alias_id` | integer FK → supply_aliases | nullable; null until mapped |

### `supply_aliases` table — maps a supplier-specific description to a Supply

| column | type | notes |
|---|---|---|
| `id` | integer PK | auto-increment |
| `supply_id` | integer FK → supplies | not null |
| `supplier_id` | integer FK → suppliers | not null |
| `description` | varchar | the exact string as it appears on invoices |

Unique constraint on `(supplier_id, description)`.

When a new invoice is confirmed, the system checks each `InvoiceEntry.description` against existing `SupplyAlias` records for that supplier and auto-sets `supply_alias_id` on matching entries.

---

## Claude API Integration

### Endpoint

`POST https://api.anthropic.com/v1/messages` with model `claude-sonnet-4-6`.

### Request

Send the invoice image as a base64-encoded `image` content block. The prompt asks Claude to return a single JSON object:

```json
{
  "supplier_name": "string — as it appears on the invoice",
  "invoice_number": "string or null",
  "date": "YYYY-MM-DD or null",
  "entries": [
    {
      "description": "string",
      "quantity": 1.0,
      "unit_price": 5.50,
      "extras": {
        "unit": "kg",
        "vat_rate": 13,
        "line_total": 5.50
      }
    }
  ]
}
```

### Supplier matching

After parsing, the `supplier_name` is matched case-insensitively against `suppliers.name`. Two outcomes:
- **Match found** — `supplier_id` is resolved automatically; review page shows supplier as read-only
- **No match** — review page shows a warning ("Προμηθευτής «{name}» δεν βρέθηκε") with two options: a dropdown to select an existing supplier, or a small inline form to create a new supplier (name + telephone) without leaving the review page

---

## Admin Sections

### Invoice scanning — `/admin/invoices/scan`

**`GET /admin/invoices/scan`**
A minimal, mobile-friendly form:
- File input: `accept="image/*" capture="environment"` (opens phone camera directly)
- Submit button

No supplier dropdown — supplier is determined from the invoice.

**`POST /admin/invoices/scan`**
- Receives uploaded image
- Calls Claude API, gets structured JSON
- Attempts supplier name match
- Stores parsed data temporarily (PHP session)
- Redirects to review page

**`GET /admin/invoices/review`**
Pre-filled review form showing:
- Supplier: auto-filled (read-only) or warning with choice of existing supplier dropdown or inline new supplier form (name + telephone) if unmatched
- Invoice date (editable)
- Invoice number (editable, optional)
- Editable table of line items: description, quantity, unit_price (extras stored silently)
- "Αποθήκευση" button

**`POST /admin/invoices/confirm`**
- Saves `Invoice` + `InvoiceEntry` records
- Auto-links entries to matching `SupplyAlias` records for this supplier
- Clears session data
- Redirects to invoice detail page

---

### Invoice list — `GET /admin/invoices`

Table of past invoices sorted newest first: date, supplier name, invoice number, number of entries. Each row links to the invoice detail page.

Create button links to `/admin/invoices/scan`.

---

### Invoice detail — `GET /admin/invoices/view?id=X`

Invoice header: supplier, date, invoice number, scanned_at.

Line items table with columns: description, quantity, unit_price, supply alias status.

- **Linked entries** — show supply name (linked to supply update page)
- **Unlinked entries** — show a "Σύνδεση" button that opens a small inline form with a supply dropdown. On save, creates a `SupplyAlias` and updates `supply_alias_id` on all past and future `InvoiceEntry` records with the same `(supplier_id, description)`.

---

### Supply aliases — `GET /admin/supply-aliases`

Table listing all aliases: supply name, supplier name, description string. Delete allowed. No create form — aliases are created only through the mapping flow on invoice detail pages.

---

### Price graph — on supply alias list

For each alias, a Chart.js line chart of `unit_price` over time, sourced from linked `InvoiceEntry` records (x = invoice date, y = unit_price). Follows the same pattern as the existing price history chart on `update_supply.twig`.

---

## Sidebar Nav

New entry "Τιμολόγια" added to the admin sidebar. "Ψευδώνυμα προμηθειών" (Supply aliases) also added.

---

## Service Layer

A dedicated `InvoiceParserService` handles the Claude API call and returns a structured DTO. This keeps the action class thin and the parsing logic testable in isolation.

---

## Non-goals

- No PDF support (images only for now)
- No automatic supply price updates from invoice data
- No integration with shopping lists
- No multi-page invoice support (single image per invoice)
- No OCR fallback if Claude API is unavailable
