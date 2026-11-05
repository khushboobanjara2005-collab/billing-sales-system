# Billing and Sales Management System

A lightweight PHP 8+ and MySQL foundation for a BCA billing and sales CRUD
project. It uses plain PHP, PDO, HTML, CSS, and vanilla JavaScript; there is no
framework, Composer dependency, or URL-rewrite requirement.

## Requirements

- XAMPP for Windows with PHP 8+ and MySQL/MariaDB
- Apache and MySQL started from the XAMPP Control Panel
- phpMyAdmin (included with XAMPP)

## XAMPP setup

1. Keep this project at `C:\xampp\htdocs\billing-sales-system`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`.
4. Choose **Import**, select `database/database.sql`, then select **Import**.
   The SQL creates `billing_sales_db` and its four tables.
5. In PowerShell, from the project directory, create the local configuration:

   ```powershell
   Copy-Item .env.example .env
   ```

6. Edit `.env` to match your MySQL settings. `DB_PORT` must match the MySQL
   port shown in the XAMPP Control Panel **Port(s)** column; XAMPP commonly
   uses `3306` or `3307`. Keep `DB_PASS` empty only when the local MySQL `root`
   account has no password.
7. Open the application:

   `http://localhost/billing-sales-system/public/`

   Health endpoint:

   `http://localhost/billing-sales-system/public/index.php?r=health`

The project reads `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS`
from `.env`. If `DB_PORT` is omitted, the application defaults to `3306`.

The health endpoint returns the application status and a database reachable
boolean. It never returns connection credentials. If the database is
unavailable, it returns HTTP 503 and logs diagnostic details to
`storage/logs/app.log`.

## Database test

Run this from PowerShell:

```powershell
C:\xampp\php\php.exe tests\db_connection_test.php
```

The test prints only pass/fail information and never prints credentials.

## Optional virtual host

The default URL above works without a virtual host or `mod_rewrite`. A local
Apache virtual host may be configured later if desired, with its document root
pointing to the project's `public` directory.

## Current scope

This deliverable is the application and database foundation only. It does not
yet include company, invoice, or payment CRUD screens, reports, or charts.
Authentication is also intentionally deferred; see
[`docs/architecture.md`](docs/architecture.md) for the known limitation and
later-phase plan.
