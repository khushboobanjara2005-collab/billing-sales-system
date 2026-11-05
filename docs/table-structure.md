# Table Structure

The importable schema is [`../database/database.sql`](../database/database.sql).
All tables use InnoDB and `utf8mb4_unicode_ci`. Fresh imports require a business ID for `companies` and `invoices`. Migration
003 temporarily allows `NULL` for those columns so the first local account can
claim legacy rows without losing them.

## `users`

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `user_id` | `INT UNSIGNED` | No | PK | `AUTO_INCREMENT` |
| `full_name` | `VARCHAR(150)` | No | — | Account display name |
| `email` | `VARCHAR(254)` | No | Unique `uq_users_email` | Normalized lowercase |
| `phone` | `VARCHAR(20)` | Yes | — | Optional |
| `password_hash` | `VARCHAR(255)` | No | — | PHP `password_hash()` output |
| `created_at` | `TIMESTAMP` | No | — | `CURRENT_TIMESTAMP` |
| `updated_at` | `TIMESTAMP` | No | — | Auto-updated |

## `businesses`

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `business_id` | `INT UNSIGNED` | No | PK | `AUTO_INCREMENT` |
| `owner_user_id` | `INT UNSIGNED` | No | FK `fk_businesses_owner`; indexed | `users.user_id`, delete cascades |
| `business_name` | `VARCHAR(150)` | No | — | Printed invoice issuer |
| `tagline` | `VARCHAR(255)` | Yes | — | Optional |
| `business_type` | `VARCHAR(100)` | Yes | — | Optional |
| `owner_name` | `VARCHAR(150)` | Yes | — | Optional |
| `address` | `VARCHAR(500)` | Yes | — | Optional |
| `city` | `VARCHAR(100)` | Yes | — | Optional |
| `state` | `VARCHAR(100)` | Yes | — | Optional |
| `pincode` | `VARCHAR(10)` | Yes | — | Optional |
| `phone` | `VARCHAR(20)` | Yes | — | Optional |
| `email` | `VARCHAR(254)` | Yes | — | Optional |
| `gstin` | `VARCHAR(15)` | Yes | — | Optional; used for GST state comparison |
| `state_code` | `CHAR(2)` | Yes | — | Optional GST state code |
| `bank_name` | `VARCHAR(100)` | Yes | — | Optional |
| `branch_name` | `VARCHAR(100)` | Yes | — | Optional |
| `account_holder_name` | `VARCHAR(150)` | Yes | — | Optional |
| `account_number` | `VARCHAR(34)` | Yes | — | Optional |
| `ifsc_code` | `VARCHAR(11)` | Yes | — | Optional |
| `invoice_prefix` | `VARCHAR(20)` | No | — | `INV` |
| `next_invoice_number` | `INT UNSIGNED` | No | — | Starts at `1`; service locks and increments |
| `default_gst_rate` | `DECIMAL(5,2)` | No | — | `2.50` percent each for CGST/SGST |
| `jurisdiction` | `VARCHAR(150)` | Yes | — | Used in invoice terms |
| `authorized_signatory` | `VARCHAR(150)` | Yes | — | Optional |
| `terms_and_conditions` | `TEXT` | Yes | — | Optional invoice text |
| `created_at` | `TIMESTAMP` | No | — | `CURRENT_TIMESTAMP` |
| `updated_at` | `TIMESTAMP` | No | — | Auto-updated |

## `companies`

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `company_id` | `INT UNSIGNED` | No | PK | `AUTO_INCREMENT` |
| `business_id` | `INT UNSIGNED` | No | FK `fk_companies_business`; indexed | Migration 003 temporarily allows `NULL` for legacy rows |
| `company_name` | `VARCHAR(150)` | No | Indexed | Customer/company name |
| `gstin` | `VARCHAR(15)` | Yes | Unique with business ID | Optional |
| `phone` | `VARCHAR(20)` | Yes | — | Optional |
| `email` | `VARCHAR(254)` | Yes | — | Optional |
| `address` | `TEXT` | Yes | — | Optional |
| `created_at` | `TIMESTAMP` | No | — | `CURRENT_TIMESTAMP` |

`uq_companies_business_company` provides the referenced composite key used by
the invoice-to-company tenant constraint.

## `invoices`

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `invoice_id` | `INT UNSIGNED` | No | PK | `AUTO_INCREMENT` |
| `business_id` | `INT UNSIGNED` | No | FK `fk_invoices_business`; indexed | Migration 003 temporarily allows `NULL` for legacy rows |
| `invoice_number` | `VARCHAR(50)` | No | Unique with business ID | Business prefix, year, sequence |
| `company_id` | `INT UNSIGNED` | No | Composite FK with `business_id` | Company must belong to same business |
| `invoice_date` | `DATE` | No | Indexed | Not in the future |
| `subtotal` | `DECIMAL(12,2)` | No | — | Server-calculated |
| `cgst_rate` | `DECIMAL(5,2)` | No | — | `2.50` percent |
| `sgst_rate` | `DECIMAL(5,2)` | No | — | `2.50` percent |
| `tax_type` | `ENUM('intra','inter')` | No | — | Determines CGST/SGST or IGST |
| `cgst` | `DECIMAL(12,2)` | No | — | `0.00` |
| `sgst` | `DECIMAL(12,2)` | No | — | `0.00` |
| `igst` | `DECIMAL(12,2)` | No | — | `0.00` |
| `round_off` | `DECIMAL(5,2)` | No | — | Signed rounding adjustment |
| `total_amount` | `DECIMAL(12,2)` | No | — | Server-calculated |
| `created_at` | `TIMESTAMP` | No | — | `CURRENT_TIMESTAMP` |

## `invoice_items`

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `item_id` | `INT UNSIGNED` | No | PK | `AUTO_INCREMENT` |
| `invoice_id` | `INT UNSIGNED` | No | FK `fk_invoice_items_invoice`; indexed | Invoice deletion cascades |
| `weigh_bridge_serial` | `VARCHAR(50)` | Yes | — | Optional per-line number |
| `item_date` | `DATE` | Yes | — | Optional for older rows |
| `description` | `VARCHAR(255)` | No | — | Item description |
| `quantity` | `DECIMAL(10,3)` | No | — | Positive validation in PHP |
| `rate` | `DECIMAL(12,2)` | No | — | Non-negative validation in PHP |
| `line_total` | `DECIMAL(12,2)` | No | — | Server-calculated |

## `payments`

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `payment_id` | `INT UNSIGNED` | No | PK | `AUTO_INCREMENT` |
| `invoice_id` | `INT UNSIGNED` | No | FK `fk_payments_invoice`; indexed | Provides business scope |
| `payment_date` | `DATE` | No | Indexed | Not in the future |
| `amount` | `DECIMAL(12,2)` | No | `chk_payments_amount_positive` | Must be `> 0` |
| `payment_method` | `VARCHAR(50)` | No | — | Cash, UPI, Bank Transfer, Cheque |
| `created_at` | `TIMESTAMP` | No | — | `CURRENT_TIMESTAMP` |

## `installation_state`

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `state_id` | `TINYINT UNSIGNED` | No | PK; CHECK = 1 | Singleton row |
| `legacy_data_claimed` | `TINYINT(1)` | No | — | Starts `0`; first registration sets `1` |

## `auth_login_attempts`

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `attempt_key` | `CHAR(64)` | No | PK | SHA-256 email/IP-derived key |
| `failures` | `SMALLINT UNSIGNED` | No | — | Failed login count |
| `first_failed_at` | `DATETIME` | No | — | Start of rolling attempt window |
| `blocked_until` | `DATETIME` | Yes | — | Temporary login lockout |
| `updated_at` | `DATETIME` | No | — | Auto-updated |

## `seller_profile` (legacy)

This old singleton is read only during first-account setup and is not used for
current invoice rendering. The corresponding business row owns the invoice
issuer settings after migration.

| Column | Type | Null | Key / constraint | Default / notes |
|---|---|---:|---|---|
| `profile_id` | `TINYINT UNSIGNED` | No | PK; CHECK = 1 | Legacy singleton |
| `seller_name` | `VARCHAR(150)` | No | — | Copied into `business_name` |
| `business_description` | `VARCHAR(255)` | Yes | — | Copied into `tagline` |
| `address` | `VARCHAR(500)` | Yes | — | Legacy address |
| `phone` | `VARCHAR(30)` | Yes | — | Legacy phone |
| `gstin` | `VARCHAR(15)` | Yes | — | Legacy GSTIN |
| `bank_name` | `VARCHAR(100)` | Yes | — | Legacy bank name |
| `branch` | `VARCHAR(100)` | Yes | — | Copied into `branch_name` |
| `account_number` | `VARCHAR(34)` | Yes | — | Legacy account number |
| `ifsc_code` | `VARCHAR(11)` | Yes | — | Legacy IFSC |
| `terms` | `TEXT` | Yes | — | Copied into business terms |
| `updated_at` | `TIMESTAMP` | No | — | Auto-updated |

Paid amount, pending balance, and payment status are derived from payments and
are not columns in any table. PHP performs money calculations in integer paise.
