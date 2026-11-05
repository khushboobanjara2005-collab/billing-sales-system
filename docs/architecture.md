# Architecture

## Request flow

Apache serves `public/` as the web entry point. `public/index.php` loads
`bootstrap.php`, starts a strict PHP session, checks authentication, loads the
authenticated user's owned business list, verifies the active business, checks
CSRF tokens on every POST, then dispatches the query-string route. No
`mod_rewrite` rule is required.

The public routes are sign-in, registration, password-help, and health. All
module routes require a logged-in user and an owned active business. The first
account can be registered only from localhost; its registration transaction
claims the existing seller profile and assigns all legacy companies/invoices
to that first business.

## Layers

- `public/`: web entry point and local browser assets; Apache directory listing
  is disabled.
- `app/Controllers/`: coordinates account, business, company, invoice,
  payment, ledger, dashboard, and report requests.
- `app/Models/`: prepared PDO queries. Company, invoice, payment, and reporting
  queries are scoped to the active business; business profile queries verify
  `owner_user_id`.
- `app/Services/`: invoice calculations and transactional invoice/payment
  rules. Invoice numbering locks the selected business row. Payment recording
  locks the selected business's invoice and checks its balance before insert.
- `app/Core/`: database connection, router, views, validation, CSRF, session,
  authentication, active-business context, and flash messages.
- `resources/views/`: presentation-only templates. Dynamic values are escaped
  with `htmlspecialchars`.
- `database/`: canonical schema, additive migrations, and optional demo seeds.
- `storage/logs/`: private diagnostics, excluded from Git and denied by Apache.

The dashboard chart uses local vanilla JavaScript. Invoice and report pages use
browser print styles. No external framework or PDF library is required.

## Authentication and tenant isolation

Passwords are stored as `password_hash()` output and checked with
`password_verify()`. Successful sign-in regenerates the session ID. Cookies are
HttpOnly, SameSite=Lax, and Secure when HTTPS is active; strict session mode and
cookie-only sessions are enabled. Sign-in failures are rate-limited per
email/IP-derived key, and all POST actions require CSRF.

Each business belongs to one `owner_user_id`; this version does not implement
shared memberships. The business switch POST accepts an ID only after
`Business::findOwned()` confirms that the logged-in user owns it. Module queries
include the active `business_id`; IDs received from the browser are never
treated as authorization. Invoices are constrained to companies from the same
business by a composite foreign key. Payments and items inherit scope through
their parent invoice.

Migration 003 leaves legacy business IDs nullable until the first registration.
That registration locks `installation_state`, creates the first business from
the existing seller profile, assigns legacy companies and invoices in the same
transaction, and marks the claim complete. Do not expose a not-yet-initialized
installation to the network.

## Invoice and payment rules

`InvoiceService` calculates item amounts, taxes, and totals in integer paise.
GST is either CGST+SGST or IGST, never both for one invoice. Invoice numbers
come from the selected business's prefix and locked sequence. The browser
preview is informational only; the server recalculates all values.

`PaymentService::record` uses one transaction with `SELECT ... FOR UPDATE`,
checks the selected invoice's outstanding amount, rejects overpayment, inserts
the payment, and commits. Paid amount, pending balance, and payment status are
derived from payment rows.

## Known limitations

- Password-reset email delivery is not implemented. The forgot-password page
  gives a generic local-installation message and does not change account data.
- Remember Me and shared business memberships are not implemented.
- This academic application still needs deployment hardening, backup policy,
  HTTPS, and mail configuration before public use.
