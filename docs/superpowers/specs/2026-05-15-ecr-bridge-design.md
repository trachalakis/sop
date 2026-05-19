# ECR Bridge — Design Spec

## Goal

Allow the cloud-hosted app to feed order line items to a local fiscal ECR (MIRKA III / INFOCARINA i57 III) using the MCP protocol, so that every placed order is fiscally recorded. The ECR operator handles payment and closes the transaction at the physical device; the app only sends item data.

## Context

The app runs on the cloud. The fiscal ECR is on the restaurant's local network and is unreachable from the cloud. A local PHP CLI agent bridges the gap: it polls the cloud API for pending jobs, connects to the ECR over TCP, and sends item sale commands using the MCP protocol.

Orders are queued for the ECR when placed (not when paid), giving the local agent time to transmit items before the ECR operator processes payment.

---

## Section 1 — Architecture

```
Cloud App (PHP/Slim)          Local Network
┌─────────────────────┐       ┌──────────────────┐       ┌─────────┐
│  CreateOrder action │──────▶│  ecr_queue table │       │         │
│  (take-out + orders)│       └────────┬─────────┘       │   ECR   │
│                     │                │                  │ (MCP/   │
│  GET /api/ecr/jobs  │◀──────────────│─────────────────▶│  TCP)   │
│  POST /api/ecr/jobs │       ┌────────┴─────────┐       │         │
│       /{id}/ack     │       │  ecr-agent.php   │       └─────────┘
└─────────────────────┘       │  (cron, 1 min)   │
                              └──────────────────┘
```

- **Cloud app** exposes two REST endpoints protected by an API key.
- **Local agent** (`ecr-agent/ecr-agent.php`) runs every minute via cron, polls the cloud, sends MCP commands, and reports results back.
- **ECR** receives item sale commands only. It auto-opens a transaction on the first item. The ECR operator closes it by processing payment at the physical device.

---

## Section 2 — Cloud-side Data Model & API

### `fiscal_department` on `menu_items`

Nullable integer column. Set per item in the admin update form (number input, not required). `CopyMenuItem` copies it alongside other fields. The polling endpoint includes `fiscalDepartment` in each entry (null if unset). The local agent checks for null before building the MCP packet — if any entry in a job has null `fiscalDepartment`, the agent immediately POSTs a `failed` ack with a descriptive error, without connecting to the ECR.

### `ecr_queue` table

| Column | Type | Notes |
|---|---|---|
| `id` | serial PK | |
| `order_id` | integer FK | references `orders` |
| `status` | varchar | `pending`, `sent`, `failed` |
| `attempts` | integer | default 0 |
| `last_attempted_at` | timestamp | nullable |
| `error` | text | nullable, last failure reason |
| `created_at` | timestamp | |

Jobs with `attempts >= 5` stay `failed` permanently and are visible for admin inspection. No automatic purge.

### Enqueueing

Both `TakeOutApp/CreateOrder` and `OrdersApp/CreateOrder` insert an `EcrJob` row (status `pending`) immediately after the order is persisted.

### `GET /api/ecr/jobs`

Returns all jobs where `status = 'pending'` and `attempts < 5`.

Auth: `X-Api-Key` header, checked against `ECR_AGENT_API_KEY` in `.env`.

Response shape:
```json
[
  {
    "id": 42,
    "orderId": 123,
    "entries": [
      {
        "name": "Μπριζόλα χοιρινή",
        "quantity": "2.000",
        "unitPrice": "8.50",
        "fiscalDepartment": 1
      },
      {
        "name": "Extra τυρί",
        "quantity": "1.000",
        "unitPrice": "1.50",
        "fiscalDepartment": 1
      }
    ]
  }
]
```

Extras are included as separate line items using the same fiscal department as their parent order entry.

### `POST /api/ecr/jobs/{id}/ack`

Body:
```json
{ "status": "sent" }
```
or
```json
{ "status": "failed", "error": "Socket connection refused" }
```

Increments `attempts`, sets `last_attempted_at`, updates `status`. If `attempts` reaches 5, status is set to `failed` permanently regardless of the body.

---

## Section 3 — Local Agent & MCP Protocol

### Files

```
ecr-agent/
  ecr-agent.php         # main script
  ecr-agent-config.php  # local config (gitignored)
```

`ecr-agent-config.php` returns an array:
```php
<?php
return [
    'ecr_host'      => '192.168.1.100',
    'ecr_port'      => 9100,
    'cloud_api_url' => 'https://myapp.example.com',
    'api_key'       => 'secret-key-here',
    'timeout'       => 5,    // socket timeout (seconds) for the first reply byte
];
```

`ecr-agent-config.example.php` is committed with placeholder values.

### Run Loop

1. Acquire `/tmp/ecr-agent.lock` — exit immediately if already locked (prevents cron overlap)
2. `GET /api/ecr/jobs` — fetch pending jobs
3. For each job:
   - Open TCP socket to ECR
   - For each entry: send one item sale command (command `3`)
   - On full success: `POST /api/ecr/jobs/{id}/ack` with `{ status: "sent" }`
   - On any failure: `POST /api/ecr/jobs/{id}/ack` with `{ status: "failed", error: "..." }`
   - Close socket
4. Release lock file, exit

### MCP Protocol — Per Command

The MCP spec (`MCP.pdf` section 6) documents an `ENQ`/`ACK` + `STX`/`ETX` framing for the RS-232 serial layer (section 4). **This device's TCP/Ethernet interface does NOT use that framing** — packets are exchanged bare in both directions, with TCP segmentation as the message boundary. The cloud agent must NOT send `ENQ`, NOT wrap in `STX`/`ETX`, and NOT expect `ACK`. Verified empirically against MIRKA III firmware V1 R1 T37 by issuing a bare `a/44` (Read ECR identification) and receiving a properly-formatted reply with no control bytes.

**Step 1 — Build packet:**

Data section: `3/S/<name>/<>/<qty>/<unit_price>/<dept>/`

Fields (per MCP section 8.2.15):
| Field | Value |
|---|---|
| Request code | `3` |
| Operation | `S` (positive sale) |
| Item name | Greek translation, converted from UTF-8 to Windows-1253, then truncated to 20 bytes. The MCP String class is single-byte (§6.3.2) and the field limit is 20 bytes; sending UTF-8 directly causes ERR-2 because each Greek letter is 2 bytes in UTF-8. |
| Extra description | empty string |
| Quantity | formatted to 3 decimal places (e.g. `2.000`) |
| Unit price | formatted to 2 decimal places (e.g. `8.50`) |
| Department | fiscal department integer (e.g. `1`) |

Checksum (per MCP section 6.3.1): sum of all bytes in the data section, held in an 8-bit accumulator (wraps at 256), then `mod 100`, zero-padded to 2 digits. Equivalent PHP: `sprintf('%02d', ($sum & 0xFF) % 100)`. The naive `sum % 100` is WRONG — it does not match the device when the byte sum exceeds 255.

Full packet on the wire: `<data section>` + `<2-digit checksum>`. No `STX`, no `ETX`.

**Step 2 — Send and receive:**

Write packet bytes to socket. Read reply: wait up to `timeout` seconds for the first byte, then drain until the socket has been idle for ~500ms. The accumulated bytes are the complete reply.

Reply structure (per MCP section 8.1.2):

`<reply_code>/<device_status>/<fiscal_status>/<data_fields…>/<checksum>`

- Reply code is a 2-digit field. `00` = success. Anything else is an MCP error code (table in MCP.pdf section 8.3). The agent throws with the code and full reply for debugging.
- Device status and fiscal status are 2-hex-digit bit fields (section 8.1.2.2). The agent does not currently surface these but they are present.

### Cron (added to local server's crontab)

```
* * * * * php /path/to/ecr-agent/ecr-agent.php >> /var/log/ecr-agent.log 2>&1
```

---

## Files Changed

| File | Change |
|---|---|
| `migrations/add_fiscal_department_to_menu_items.php` | Create — adds `fiscal_department` column |
| `migrations/add_ecr_queue.php` | Create — adds `ecr_queue` table |
| `src/Domain/Entities/MenuItem.php` | Add `fiscal_department` property, getter, setter |
| `src/Domain/Entities/EcrJob.php` | Create — maps to `ecr_queue` table |
| `src/Domain/Repositories/EcrJobsRepository.php` | Create |
| `src/Application/Actions/Admin/UpdateMenuItem.php` | Persist `fiscal_department` from POST body |
| `src/Application/Actions/Admin/CopyMenuItem.php` | Copy `fiscal_department` on clone |
| `src/templates/admin/update_menu_item.twig` | Add fiscal department number input |
| `src/Application/Actions/TakeOutApp/CreateOrder.php` | Enqueue ECR job on order creation |
| `src/Application/Actions/OrdersApp/CreateOrder.php` | Enqueue ECR job on order creation |
| `src/Application/Actions/Api/EcrJobs.php` | Create — GET handler (polling endpoint) |
| `src/Application/Actions/Api/AckEcrJob.php` | Create — POST handler (ack endpoint) |
| `app/routes.php` | Register two new `/api/ecr/*` routes |
| `app/dependencies.php` | Bind `EcrJobsRepository` |
| `app/.env` / `app/.env.example` | Add `ECR_AGENT_API_KEY` |
| `ecr-agent/ecr-agent.php` | Create — local agent main script |
| `ecr-agent/ecr-agent-config.example.php` | Create — config template |
| `.gitignore` | Add `ecr-agent/ecr-agent-config.php` |
