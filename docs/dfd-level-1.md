# Data Flow Diagram — Level 1

The signed-in user first selects an owned business. The four business processes
then operate only on that active business. Dashboard summary queries are
included with **Generate Reports** because both display read-only aggregates.

```mermaid
flowchart LR
    Admin["External entity: Admin"]
    Users[("D0 Users and login attempts")]
    Businesses[("D1 Businesses")]
    Companies[("D2 Companies")]
    Invoices[("D3 Invoices")]
    Items[("D4 Invoice Items")]
    Payments[("D5 Payments")]

    Account["0. Authenticate and select business"]
    Manage["1. Manage Companies"]
    CreateInvoice["2. Create Invoice"]
    RecordPayment["3. Record Payment"]
    Reports["4. Generate Reports and Dashboard"]

    Admin -->|"Credentials and business selection"| Account
    Account <-->|"Account registration and authentication"| Users
    Account <-->|"Ownership validation and business profile"| Businesses
    Account -->|"Authorized active business context"| Manage
    Account -->|"Authorized active business context"| CreateInvoice
    Account -->|"Authorized active business context"| RecordPayment
    Account -->|"Authorized active business context"| Reports

    Admin -->|"Company details, search, update, delete"| Manage
    Manage -->|"Company list, details, validation and delete result"| Admin
    Manage <-->|"Company records"| Companies

    Admin -->|"Company, date, line items and GST rates"| CreateInvoice
    CreateInvoice -->|"Invoice details, printable view or validation errors"| Admin
    CreateInvoice -->|"Company lookup"| Companies
    Companies -->|"Selected company"| CreateInvoice
    CreateInvoice <-->|"Invoice header"| Invoices
    CreateInvoice <-->|"Invoice line items"| Items

    Admin -->|"Invoice, date, amount and payment method"| RecordPayment
    RecordPayment -->|"Payment result, balance or validation error"| Admin
    RecordPayment -->|"Invoice balance check and row lock"| Invoices
    Invoices -->|"Invoice total"| RecordPayment
    RecordPayment <-->|"Payment history and new payment"| Payments

    Admin -->|"Date range or dashboard request"| Reports
    Reports -->|"Dashboard cards, recent invoices, chart and report tables"| Admin
    Companies -->|"Company counts and names"| Reports
    Invoices -->|"Sales, invoice count, dates and totals"| Reports
    Items -->|"Invoice detail data"| Reports
    Payments -->|"Received totals, methods and pending calculations"| Reports
```

`Create Invoice` represents the invoice create, view, update, and delete
screens. `Manage Companies` represents full company CRUD, search, and the
company ledger. The payment process records and deletes payment rows; it does
not currently provide payment updates or a free-text payment search. Outstanding
balances and payment statuses are calculated from invoices and payments rather
than stored separately.

## PNG for Word / PDF

The included PNG is an older login-less snapshot; regenerate it from this Mermaid diagram.

![Data Flow Diagram Level 1](images/dfd-level-1.png)
