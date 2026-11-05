# Manual Test Cases

Run these checks against a local test database. Use temporary records and
remove them after testing. No result is pre-filled: every **Actual** value is
left for the tester, and no test is claimed as passed.

| ID | Module | Test case | Input | Expected | Actual | Pass/Fail |
|---|---|---|---|---|---|---|
| TC-C-01 | Companies | Create a company | Valid name and optional contact details | Company is saved and appears in the list | To be filled by tester | To be filled by tester |
| TC-C-02 | Companies | Read company details | Open a saved company | Details page shows the saved fields | To be filled by tester | To be filled by tester |
| TC-C-03 | Companies | Update a company | Change the name or contact details and submit | Updated values appear on the details page and list | To be filled by tester | To be filled by tester |
| TC-C-04 | Companies | Delete a company without invoices | Submit its delete form with a valid CSRF token | Company is removed and a success message appears | To be filled by tester | To be filled by tester |
| TC-C-05 | Companies | Search companies | Search for a known name, GSTIN, phone, or email | Matching companies are listed; non-matches are excluded | To be filled by tester | To be filled by tester |
| TC-C-06 | Companies | Validate company fields | Empty/short name, malformed GSTIN, invalid phone/email, or long address | Invalid fields show validation errors; record is not saved | To be filled by tester | To be filled by tester |
| TC-C-07 | Companies | Block delete when invoices exist | Delete a company referenced by an invoice | Friendly delete-blocked message; company remains | To be filled by tester | To be filled by tester |
| TC-C-08 | Companies | Payment overpayment behavior | Not applicable to company records | Not applicable; payment balance is tested under Payments | To be filled by tester | To be filled by tester |
| TC-I-01 | Invoices | Create invoice | Valid company, non-future date, item(s), quantity, rate, GST rates | Invoice number is generated; server-calculated totals are saved | To be filled by tester | To be filled by tester |
| TC-I-02 | Invoices | Read and print invoice | Open a saved invoice and use Print / Save PDF | Header, items, GST, totals and derived balance are visible; print omits app navigation | To be filled by tester | To be filled by tester |
| TC-I-03 | Invoices | Update invoice | Change an unpaid invoice and its line items | Invoice and item replacement are saved atomically with recalculated totals | To be filled by tester | To be filled by tester |
| TC-I-04 | Invoices | Delete invoice without payments | Submit delete form for an unpaid invoice with no payment records | Invoice and its items are removed | To be filled by tester | To be filled by tester |
| TC-I-05 | Invoices | Search/filter invoices | Invoice number, company, date range, or status filter | Only matching invoices appear | To be filled by tester | To be filled by tester |
| TC-I-06 | Invoices | Validate invoice fields | Missing company/item, invalid/future date, non-positive quantity, or negative rate | Errors are shown; no invoice is saved | To be filled by tester | To be filled by tester |
| TC-I-07 | Invoices | Block delete when payments exist | Delete an invoice that has a payment | Friendly blocked-delete message; invoice and payment remain | To be filled by tester | To be filled by tester |
| TC-I-08 | Invoices | Prevent invoice-total overpayment | Attempt to record more than the invoice balance from its payment form | Payment service rejects it; invoice balance remains unchanged | To be filled by tester | To be filled by tester |
| TC-P-01 | Payments | Record payment | Valid invoice, non-future date, positive amount within balance, allowed method | Payment is saved; invoice paid/pending/status values update | To be filled by tester | To be filled by tester |
| TC-P-02 | Payments | Read payment details | Open a saved payment | Company, invoice, date, method, and amount are shown | To be filled by tester | To be filled by tester |
| TC-P-03 | Payments | Update payment | Not supported by current payment routes | Payment update is not available; no update route is exposed | To be filled by tester | To be filled by tester |
| TC-P-04 | Payments | Delete payment | Submit the payment delete form with a valid CSRF token | Payment is removed; derived invoice balance is recalculated | To be filled by tester | To be filled by tester |
| TC-P-05 | Payments | Filter payment list | Company, method, and date range filters | Only matching payment records appear; no free-text search is provided | To be filled by tester | To be filled by tester |
| TC-P-06 | Payments | Validate payment fields | Invalid invoice/date, future date, zero/invalid amount, or unsupported method | Field error is shown; no payment is saved | To be filled by tester | To be filled by tester |
| TC-P-07 | Payments | Delete-blocked behavior | Not applicable to payment deletion | Payment rows can be deleted; invoice deletion, not payment deletion, is blocked by payment history | To be filled by tester | To be filled by tester |
| TC-P-08 | Payments | Reject overpayment | Submit amount above the current balance | Payment is rejected with a clear outstanding-balance message; no row is inserted | To be filled by tester | To be filled by tester |
| TC-D-01 | Dashboard | Read summary cards and recent invoices | Open dashboard with existing data | Sales, received, pending, company/invoice counts, and up to five recent invoices are shown | To be filled by tester | To be filled by tester |
| TC-D-02 | Dashboard | Render monthly chart | Open dashboard at desktop and mobile widths | Six months are represented in chart and accessible data table | To be filled by tester | To be filled by tester |
| TC-D-03 | Dashboard | CRUD/search/validation/delete-blocked/overpayment | Not applicable to dashboard | Dashboard is read-only; related operations are tested in their modules | To be filled by tester | To be filled by tester |
| TC-R-01 | Reports | Generate sales summary | Valid optional invoice date range | Invoice count, sales, received, and pending totals are shown | To be filled by tester | To be filled by tester |
| TC-R-02 | Reports | Generate company outstanding report | Open reports with outstanding balances | Companies are listed in descending pending-balance order | To be filled by tester | To be filled by tester |
| TC-R-03 | Reports | Generate payment method summary | Valid optional payment date range | Payment count and received amount are grouped by method | To be filled by tester | To be filled by tester |
| TC-R-04 | Reports | Validate report date range and print | Start after end; then print a valid report | Invalid range displays an error; print output hides controls/navigation | To be filled by tester | To be filled by tester |
| TC-R-05 | Reports | CRUD/search/delete-blocked/overpayment | Not applicable to report pages | Reports are read-only; underlying operations are tested in their modules | To be filled by tester | To be filled by tester |
