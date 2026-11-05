# System Flowchart

The current application has no login or user accounts. The Administrator opens
the dashboard directly. All pages use query-string routes such as
`index.php?r=invoices/index`; Apache rewrite rules are not required.

```mermaid
flowchart TD
    A([Start]) --> B[Open application]
    B --> C[Dashboard]
    C --> D{Choose module}
    D -->|Companies| E[Companies list and search]
    D -->|Invoices| I[Invoices list and filters]
    D -->|Payments| P[Payments list and filters]
    D -->|Reports| R[Reports and summaries]
    E --> E1[Create / Read / Update / Delete]
    E --> E2[Company ledger]
    I --> I1[Create / Read / Update / Delete]
    I --> I2[Invoice print or save as PDF]
    I1 -->|Record payment| P1[Payment form]
    P --> P1
    P1 --> P2[Validate date, method, and amount]
    E1 --> V[Validate request and CSRF token]
    I1 --> V
    P2 --> V
    V -->|Valid| M[Controller and service]
    V -->|Invalid| X[Redisplay or reject request]
    M -->|Company or invoice operation| DB[(MySQL database)]
    M -->|Payment operation| P3{Payment amount within balance?}
    P3 -->|No| P4[Show outstanding-balance error]
    P3 -->|Yes| P5[Lock invoice and insert payment in one transaction]
    P5 --> DB
    DB --> M
    M --> O[Render escaped view or redirect]
    R --> Q[Prepared report queries]
    C --> Q
    Q --> DB
    DB --> Q
    Q --> O
    O --> Z([End / next request])
    X --> Z
```

Every POST request is checked for a CSRF token. Create, update, and delete
actions use POST and redirect after processing. Invoice and payment amounts
are calculated or checked on the server; browser previews are not trusted.

## PNG for Word / PDF

![Login-less system flowchart](images/system-flowchart.png)
