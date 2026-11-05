# System Flowchart

The user signs in or registers before opening the workspace. All pages use
query-string routes such as `index.php?r=invoices/index`; Apache rewrite rules
are not required.

```mermaid
flowchart TD
    A([Start]) --> B[Open application]
    B --> C{Signed in?}
    C -->|No| L[Login or register]
    L --> L1[Verify credentials and session]
    L1 -->|Valid| S[Select active business]
    L1 -->|Invalid| L
    C -->|Yes| S
    S --> D[Dashboard for selected business]
    D --> E{Choose module}
    E -->|Companies| C1[Companies list and search]
    E -->|Invoices| I[Invoices list and filters]
    E -->|Payments| P[Payments list and filters]
    E -->|Reports| R[Reports and summaries]
    E -->|Business profile| B1[Edit business settings]
    C1 --> E1[Create / Read / Update / Delete]
    C1 --> E2[Company ledger]
    I --> I1[Create / Read / Update / Delete]
    I --> I2[Invoice print or save as PDF]
    I1 -->|Record payment| P1[Payment form]
    P --> P1
    P1 --> P2[Validate date, method, and amount]
    E1 --> V[Validate request, owner scope, and CSRF token]
    I1 --> V
    P2 --> V
    B1 --> V
    V -->|Valid| M[Controller and service]
    V -->|Invalid| X[Redisplay or reject request]
    M -->|Selected business company/invoice operation| DB[(MySQL database)]
    M -->|Payment operation| P3{Payment amount within balance?}
    P3 -->|No| P4[Show outstanding-balance error]
    P3 -->|Yes| P5[Lock invoice and insert payment in one transaction]
    P5 --> DB
    DB --> M
    M --> O[Render escaped view or redirect]
    R --> Q[Prepared report queries]
    D --> Q
    Q --> DB
    DB --> Q
    Q --> O
    O --> Z([End / next request])
    X --> Z
```

Every POST request is checked for a CSRF token. Create, update, delete, and
business-switch actions use POST. Server queries enforce the authenticated
owner's active business. Invoice and payment amounts are calculated or checked
on the server; browser previews are not trusted.

## PNG for Word / PDF

The included PNG is an older login-less snapshot; regenerate it from this Mermaid diagram.

![System flowchart](images/system-flowchart.png)
