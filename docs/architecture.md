# Architecture

## Request flow

Apache serves `public/` as the web entry point. `public/index.php` loads
`bootstrap.php`, which reads the ignored local `.env`, registers the small
`App\` autoloader, and installs generic error handling. `Router` dispatches the
query-string route (`?r=health`); it does not depend on `mod_rewrite`.

The health route checks the database using `Database` and returns only safe
status information. PDO uses exceptions, native prepared statements, and
`utf8mb4`. Database details and stack traces go to `storage/logs/app.log`;
browser responses remain generic.

## Layers

- `public/`: web entry point and local browser assets; Apache directory listing
  is disabled.
- `app/Controllers/`: coordinates company, invoice, payment, company-ledger,
  dashboard, and report requests, validation, services, and views.
- `app/Models/`: table-specific PDO prepared-statement queries, plus reporting
  queries for dashboard and report summaries.
- `app/Services/`: business rules and transactions. `InvoiceService` calculates
  invoice amounts in paise and handles atomic create, update, and delete.
  `PaymentService` locks the invoice, checks its balance, and records a payment
  in one transaction.
- `app/Core/`: shared infrastructure (`Database`, `Router`, `View`, `Validator`,
  `Csrf`, `Session`, and `Flash`).
- `app/Helpers/`: shared stateless money formatting helper.
- `config/`: local environment configuration conventions; `.env` is not
  committed and is denied by the project-root Apache rules.
- `database/`: importable schema, migration, and future seed files.
- `resources/views/`: shared layout and company, invoice, payment, ledger,
  dashboard, and report presentation templates. Views contain no SQL and
  escape dynamic output with `htmlspecialchars`.
- `storage/logs/`: private application diagnostics, excluded from Git and
  denied by Apache.
- `tests/`: plain PHP database connectivity check, not a PHPUnit dependency.

The dashboard chart is drawn by a local vanilla JavaScript canvas script.
Report and invoice pages use browser print styles and need no PDF library.

## Request and session safety

The front controller starts a strict-mode PHP session with HttpOnly and
SameSite=Lax cookies. Any POST request is rejected unless it includes the
session CSRF token as `_csrf` or `X-CSRF-TOKEN`. Create, update, and delete
actions use POST and redirect after handling the request.

Invoice creation serializes invoice-number allocation by locking a company row
inside its transaction. The server calculates line amounts and GST using
integer paise; submitted browser preview totals are not persisted. Updating
replaces invoice items in the same transaction.

`PaymentService::record` uses one database transaction: it locks the invoice
row with `SELECT ... FOR UPDATE`, calculates the outstanding amount from
payments, rejects amounts above that balance using integer paise arithmetic,
inserts the payment, and commits. Paid amount, pending balance, and payment
status remain derived values, never stored columns.

## Known limitation and later phase

There is **no authentication or authorization** in this foundation. It is
intended for local college demonstration only and must not be exposed as a
public or shared production application. A later optional phase should add
login, password hashing, authorization checks, logout/session renewal, and
access-control tests before adding shared or remote access.
