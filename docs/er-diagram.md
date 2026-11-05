# Entity-Relationship Diagram

The database contains four InnoDB tables. A company can exist before it has
invoices, and an invoice can exist without payment rows. The invoice form
requires at least one item, although the database relationship itself permits
zero items.

```mermaid
erDiagram
    COMPANIES ||--o{ INVOICES : issues
    INVOICES ||--o{ INVOICE_ITEMS : contains
    INVOICES ||--o{ PAYMENTS : receives

    COMPANIES {
        int_unsigned company_id PK
        varchar_150 company_name
        varchar_15 gstin UK
        varchar_20 phone
        varchar_254 email
        text address
        timestamp created_at
    }
    INVOICES {
        int_unsigned invoice_id PK
        varchar_50 invoice_number UK
        int_unsigned company_id FK
        date invoice_date
        decimal_12_2 subtotal
        decimal_5_2 cgst_rate
        decimal_5_2 sgst_rate
        decimal_12_2 cgst
        decimal_12_2 sgst
        decimal_12_2 total_amount
        timestamp created_at
    }
    INVOICE_ITEMS {
        int_unsigned item_id PK
        int_unsigned invoice_id FK
        varchar_255 description
        decimal_10_3 quantity
        decimal_12_2 rate
        decimal_12_2 line_total
    }
    PAYMENTS {
        int_unsigned payment_id PK
        int_unsigned invoice_id FK
        date payment_date
        decimal_12_2 amount
        varchar_50 payment_method
        timestamp created_at
    }
```

`||--o{` means one parent row can be related to zero or many child rows, while
every child belongs to exactly one parent. Company deletion is restricted
while invoices exist. Deleting an invoice cascades to its items; an invoice
with payments cannot be deleted. Paid amount, pending balance, and payment
status are derived from payment rows and are not stored columns.

## PNG for Word / PDF

The Mermaid source above is retained for editing. This local PNG is included for
Word documents and PDF exports.

![Entity-relationship diagram](images/er-diagram.png)
