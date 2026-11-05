# Data Flow Diagram — Level 0

This context-level view shows the user, the system boundary, account/business
stores, and the four transactional data stores.

```mermaid
flowchart LR
    Admin["External entity: Admin"]
    System(["0. Billing and Sales Management System"])
    Users[("D0 Users and login attempts")]
    Businesses[("D1 Businesses")]
    Companies[("D2 Companies")]
    Invoices[("D3 Invoices")]
    Items[("D4 Invoice Items")]
    Payments[("D5 Payments")]

    Admin -->|"Credentials, business settings, company, invoice, payment and report requests"| System
    System -->|"Authenticated workspace, validation messages, dashboard, reports and printable invoice"| Admin
    System <-->|"Account registration, authentication and login throttling"| Users
    System <-->|"Owned business profile and active business context"| Businesses
    System -->|"Company CRUD"| Companies
    Companies -->|"Company records"| System
    System -->|"Invoice headers"| Invoices
    Invoices -->|"Invoice totals and dates"| System
    System -->|"Invoice line items"| Items
    Items -->|"Descriptions, quantities, rates"| System
    System -->|"Payment records"| Payments
    Payments -->|"Payment history and derived balances"| System
```

Each module query is restricted to the active business owned by the signed-in
user. Database access uses PDO prepared statements in the PHP models.

## PNG for Word / PDF

![Data Flow Diagram Level 0](images/dfd-level-0.png)
