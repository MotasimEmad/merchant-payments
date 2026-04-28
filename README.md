# Merchant payments

A demo **ledger-based** payment stack: user and merchant **wallets**, **double-entry** batches, **deposits**, **payment intents** (customer and merchant flows), **merchant service catalog**, and a **5% platform application fee** on paid charges. The web UI uses **Laravel 12**, **Inertia**, and **Vue 3**; the API is **Sanctum**-protected where noted.

---

## What’s inside

| Area | Description |
|------|-------------|
| **Ledger** | Balances come from `ledger_lines` (no direct balance column on wallets). Every movement is a balanced `ledger_batches` + lines. |
| **Platform** | `platform_accounts` (e.g. clearing, fees, settlement) each have USD **wallets** for the other side of entries. |
| **Users** | Sign up / log in (Breeze). Personal wallet (USD) is created on first use. |
| **Merchants** | A `merchants` row + business wallet. Optional **services** (name, price, catalog for customers). |
| **Payments** | `payment_intents`: merchant can create an intent for a payer, or a **customer** can start **checkout** (by amount or by **service**). Capture moves funds: payer → merchant net + platform **fees** wallet. |
| **Fees** | **5%** of the gross charge, stored on the intent as `application_fee_minor` (rounded to cents). |

**Public (no token):** `GET /api/v1/merchants/{uuid}/services` (active services for a shop).

**Authenticated:** deposits, transfers, full payment intent lifecycle, and merchant service management. See [API (overview)](#api-overview) below.

---

## Requirements

- PHP **8.2+**
- Composer
- Node **18+** (for Vite)
- A database: **SQLite** (default in `.env.example`) or **MySQL**, etc.

---

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
```

**Database**

```bash
# SQLite: ensure database file exists
touch database/database.sqlite   # Unix/macOS; on Windows create an empty file or set MySQL in .env

php artisan migrate --seed
```

**Front end & app**

```bash
npm install
npm run build          # or npm run dev while developing
php artisan serve
```

Open `http://127.0.0.1:8000`. Register a user, or use seeded accounts (below).

> **Note:** The first-time **seed** creates **platform** clearing/fees/settlement and **wallets** (`SystemAccountsSeeder`), then **users + merchant + sample services** (`PlatformSeeder`). You do not need to insert platform rows by hand.

---

## Seeded users (local)

After `php artisan db:seed`, all use password: **`password`** (emails verified where applicable).

| Email | Role | Notes |
|--------|------|--------|
| `flow@payinfra.local` | customer | Flow testing |
| `customer@payinfra.local` | customer | Buyer |
| `merchant@payinfra.local` | merchant | *Cedar Street Coffee* + sample **services** |

Add more data via the UI: **Pay → My services** (as the merchant) or the API.

---

## Web (Pay) routes

All under **`/pay`**, with `auth` + `verified` (except you must be logged in to see Pay).

| URL | Purpose |
|-----|--------|
| `/pay` | Wallets + ledger snapshot |
| `/pay/deposit` | Simulated funding into your **personal** wallet |
| `/pay/ledger` | Your ledger lines |
| `/pay/platform-accounts` | Platform **clearing / fees / settlement** (read-only overview) |
| `/pay/shops` | List merchants with active services |
| `/pay/shop/{merchantUuid}` | Shop: pay for a **service** (one-step: intent + capture) |
| `/pay/merchant/services` | Merchant: add / list / deactivate **services** |

Breeze also exposes `/dashboard`, `/register`, `/login`, `/profile`, etc.

---

## API overview

Base URL: `/api/v1/`. **Bearer token** (Sanctum) for protected routes: `POST /api/v1/auth/login`, then `Authorization: Bearer {token}`.

| Method | Path | Auth | Summary |
|--------|------|------|--------|
| `POST` | `auth/login`, `auth/register` | — | Token |
| `GET` | `merchants/{uuid}/services` | — | Public **catalog** |
| `GET` | `wallets` | yes | List wallets |
| `POST` | `wallets/{uuid}/deposits` | yes | Deposit to **your** user wallet |
| `POST` | `payment_intents` | yes (merchant owner) | Create intent (fee **5%** of gross) |
| `POST` | `payment_intents/checkout` | yes (payer) | Customer checkout: `service_public_id` *or* `merchant_id` + `amount_minor` + `currency` |
| `POST` | `payment_intents/{publicId}/confirm` | yes (payer) | **Capture** charge |
| `GET` / `POST` / `PATCH` / `DELETE` | `merchants/mine/services` | yes | Merchant service CRUD |

JSON fields use **minor units** (e.g. cents) for money where applicable. Responses include `application_fee_minor` and, when relevant, a `service` object on the intent.

---

## Configuration

- **App:** `.env` — `APP_URL`, `APP_KEY`, `DB_*`.
- **Platform fee** is code-defined in `App\Services\Payments\PlatformApplicationFee` (**500** basis points = 5%). Change the constant to adjust the rate.
- **Ziggy** route names for Vue: shared via Inertia (`HandleInertiaRequests`).

---

## Tests

```bash
php artisan test
```

---

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). This project inherits that license unless you add your own.
