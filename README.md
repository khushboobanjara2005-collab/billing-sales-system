# Billing and Sales Management System

A lightweight ERP-style academic project for managing companies, GST invoices,
payments, a company ledger, dashboard summaries, and reports. The application
uses PHP 8+, MySQL/MariaDB, HTML5, CSS3, and vanilla JavaScript. It has no
framework, Composer dependency, JavaScript package, CDN, or `mod_rewrite`
requirement.

## Requirements

- XAMPP on Windows with PHP 8+ and Apache/MySQL (or MariaDB)
- A modern browser and an editor such as VS Code
- MySQL database `billing_sales_db`

## Setup with XAMPP

1. Keep the project at `C:\xampp\htdocs\billing-sales-system`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`, choose **Import**, and import
   `database/database.sql`. It creates `billing_sales_db` and the four tables.
4. In PowerShell, from the project directory, create the local environment file:

   ```powershell
   Copy-Item .env.example .env
   ```

5. Edit `.env` to match local database settings. Set `DB_PORT` to the port
   shown in the XAMPP Control Panel **Port(s)** column (commonly `3306` or
   `3307`). `DB_PORT` defaults to `3306` if omitted. Keep `.env` private; it is
   ignored by Git.
6. Open the application:

   `http://localhost/billing-sales-system/public/`

No virtual host or URL rewriting is required. A virtual host pointing to
`public/` is optional.

## Main pages

- Dashboard: `http://localhost/billing-sales-system/public/index.php?r=dashboard`
- Companies: `http://localhost/billing-sales-system/public/index.php?r=companies/index`
- Invoices: `http://localhost/billing-sales-system/public/index.php?r=invoices/index`
- Payments: `http://localhost/billing-sales-system/public/index.php?r=payments/index`
- Reports: `http://localhost/billing-sales-system/public/index.php?r=reports`
- Health check: `http://localhost/billing-sales-system/public/index.php?r=health`

The dashboard shows sales, receipts, pending balances, record counts, recent
invoices, and a six-month chart drawn with local vanilla JavaScript. Reports
include date-range sales totals, company-wise outstanding, and payment-method
summaries. Invoice and report pages can be printed using the browser; choose
**Save as PDF** in the print dialog where desired.

## Database and demo data

The schema is in `database/database.sql`; the initial migration is in
`database/migrations/001_create_initial_tables.sql`. Optional sample data must
be imported manually in this order using phpMyAdmin:

1. `database/seeds/demo_data.sql`
2. `database/seeds/demo_invoices.sql`
3. `database/seeds/demo_payments.sql`

The seeds are not imported automatically. Import them only into a suitable
local/demo database; company and invoice records are required before their
dependent sample records.

## Testing

Check database connectivity with:

```powershell
C:\xampp\php\php.exe tests\db_connection_test.php
```

The script reports connection status without displaying credentials. A manual
test-case table is provided in [`docs/test-cases.md`](docs/test-cases.md);
fill its Actual and Pass/Fail columns during your own testing.

## Project structure

- `app/Controllers/` — request coordination for companies, invoices, payments,
  dashboard, reports, and company ledger.
- `app/Models/` — PDO prepared-statement queries for database tables and
  read-only reporting summaries.
- `app/Services/` — invoice calculations and transactional invoice/payment
  business rules.
- `app/Core/` — database connection, query-string router, views, sessions,
  validation, CSRF, and flash-message support.
- `app/Helpers/` — shared formatting helper.
- `config/`, `database/`, `public/`, `resources/views/`, `storage/`, `tests/`,
  and `docs/` — configuration conventions, SQL, web assets, templates, logs,
  checks, and project documentation.

## Documentation

- [ER diagram](docs/er-diagram.md)
- [System flowchart](docs/system-flowchart.md)
- [DFD Level 0](docs/dfd-level-0.md) and [DFD Level 1](docs/dfd-level-1.md)
- [Table structure](docs/table-structure.md)
- [Manual test cases](docs/test-cases.md)
- [Academic report outline](docs/report-outline.md)
- [Architecture](docs/architecture.md) and [database design](docs/database-design.md)

PNG diagrams for Word/PDF are stored in [`docs/images/`](docs/images/):
ER diagram, system flowchart, DFD Level 0, DFD Level 1, and table structure.
The Markdown pages retain Mermaid source where applicable and link to their
local PNG versions.

- [ER diagram PNG](docs/images/er-diagram.png)
- [System flowchart PNG](docs/images/system-flowchart.png)
- [DFD Level 0 PNG](docs/images/dfd-level-0.png)
- [DFD Level 1 PNG](docs/images/dfd-level-1.png)
- [Table structure PNG](docs/images/table-structure.png)

## Scope and known limitation

Companies and invoices have full CRUD; companies and invoices support search
or filters. Payments can be recorded, viewed, filtered by company/method/date,
and deleted, but payment update and free-text payment search are not currently
implemented. Reports are read-only.

There is **no authentication or authorization**. The project is for local
academic demonstration and must not be exposed as a public or shared
production application. See [architecture notes](docs/architecture.md).
