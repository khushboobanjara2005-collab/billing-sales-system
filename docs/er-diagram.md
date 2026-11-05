# Entity-Relationship Diagram

The diagram shows account ownership and business-level separation. Companies
and invoices store the active business ID; invoice items and payments are
scoped through their parent invoice. The legacy `seller_profile` is only used
once to initialize the first business.

```mermaid
erDiagram
    USERS ||--o{ BUSINESSES : owns
    BUSINESSES ||--o{ COMPANIES : manages
    BUSINESSES ||--o{ INVOICES : issues
    COMPANIES ||--o{ INVOICES : billed_to
    INVOICES ||--o{ INVOICE_ITEMS : contains
    INVOICES ||--o{ PAYMENTS : receives
    USERS {
        int user_id PK
        varchar full_name
        varchar email UK
        varchar password_hash
    }
    BUSINESSES {
        int business_id PK
        int owner_user_id FK
        varchar business_name
        varchar gstin
        varchar invoice_prefix
        int next_invoice_number
    }
    COMPANIES {
        int company_id PK
        int business_id FK
        varchar company_name
        varchar gstin
        varchar phone
        varchar email
    }
    INVOICES {
        int invoice_id PK
        int business_id FK
        int company_id FK
        varchar invoice_number
        date invoice_date
        decimal total_amount
    }
    INVOICE_ITEMS {
        int item_id PK
        int invoice_id FK
        varchar weigh_bridge_serial
        date item_date
        decimal quantity
        decimal rate
        decimal line_total
    }
    PAYMENTS {
        int payment_id PK
        int invoice_id FK
        date payment_date
        decimal amount
        varchar payment_method
    }
```

`||--o{` means one parent can be related to zero or many child rows, while
every child belongs to exactly one parent. Company deletion is restricted
while invoices exist. Deleting an invoice cascades to its items; an invoice
with payments cannot be deleted. Paid amount, pending balance, and payment
status are derived from payment rows.

## PNG for Word / PDF

The Mermaid source above is current. The included PNG is an older four-table
snapshot; regenerate it from this Mermaid source before using it in a report.

![Entity-relationship diagram](images/er-diagram.png)
