# Data Flow Diagram — Level 0

This context-level view shows the Administrator, the system boundary, and the
four database stores used by the implemented modules.

```mermaid
flowchart LR
    Admin["External entity: Admin"]
    System(["0. Billing and Sales Management System"])
    Companies[("D1 Companies")]
    Invoices[("D2 Invoices")]
    Items[("D3 Invoice Items")]
    Payments[("D4 Payments")]

    Admin -->|"Company, invoice, and payment requests; search and report filters"| System
    System -->|"Screens, validation messages, dashboard, ledger, reports, printable invoice"| Admin
    System -->|"Company CRUD"| Companies
    Companies -->|"Company records"| System
    System -->|"Invoice headers"| Invoices
    Invoices -->|"Invoice totals and dates"| System
    System -->|"Invoice line items"| Items
    Items -->|"Descriptions, quantities, rates"| System
    System -->|"Payment records"| Payments
    Payments -->|"Payment history and derived balances"| System
```

There is no authentication process in the current system; the Administrator
accesses the application directly. Database access is through PDO prepared
statements in the PHP models.

## PNG for Word / PDF

![Data Flow Diagram Level 0](images/dfd-level-0.png)
