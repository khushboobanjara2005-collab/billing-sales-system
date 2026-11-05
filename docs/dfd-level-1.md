# Data Flow Diagram — Level 1

The Administrator interacts with four principal business processes. Dashboard
summary queries are included with **Generate Reports** because both display
read-only aggregates and summaries.

```mermaid
flowchart LR
    Admin["External entity: Admin"]
    Companies[("D1 Companies")]
    Invoices[("D2 Invoices")]
    Items[("D3 Invoice Items")]
    Payments[("D4 Payments")]

    Manage["1. Manage Companies"]
    CreateInvoice["2. Create Invoice"]
    RecordPayment["3. Record Payment"]
    Reports["4. Generate Reports and Dashboard"]

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

![Data Flow Diagram Level 1](images/dfd-level-1.png)
