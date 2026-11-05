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

## Layers and planned responsibility

- `public/`: web entry point and local browser assets; Apache directory listing
  is disabled.
- `app/Controllers/`: later request coordination between routes, validation,
  services, and views. No CRUD controller exists in this foundation.
- `app/Models/`: one implemented model per table, containing PDO
  prepared-statement queries only. Screen-specific create, update, and delete
  operations are deferred until those features are built.
- `app/Services/`: business rules and transactions only. The implemented
  `PaymentService` serializes payment checks against the invoice row; invoice
  entry and payment screens remain deferred.
- `app/Core/`: implemented shared infrastructure (`Database`, `Router`,
  `Session`, `Csrf`).
- `app/Helpers/`: shared stateless helpers when a real feature needs them.
- `config/`: local environment configuration conventions; `.env` is not
  committed and is denied by the project-root Apache rules.
- `database/`: importable schema, migration, and future seed files.
- `resources/views/`: later presentation templates. Views must not contain SQL
  and must escape dynamic output with `htmlspecialchars`.
- `storage/logs/`: private application diagnostics, excluded from Git and
  denied by Apache.
- `tests/`: plain PHP checks, not a PHPUnit dependency.

## Request and session safety

The front controller starts a strict-mode PHP session with HttpOnly and
SameSite=Lax cookies. Any POST request is rejected unless it includes the
session CSRF token as `_csrf` or `X-CSRF-TOKEN`. No write routes or forms exist
yet. Future forms must request `Csrf::token()` and include it.

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
