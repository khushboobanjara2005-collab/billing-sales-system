-- DEMO DATA ONLY: import demo_data.sql and demo_invoices.sql first.
-- These optional example payments demonstrate unpaid, partial, and paid states.
USE billing_sales_db;
SET @demo_business_id := (SELECT business_id FROM businesses ORDER BY business_id LIMIT 1);

INSERT INTO payments (invoice_id, payment_date, amount, payment_method)
SELECT invoice_id, '2026-01-20', 1050.00, 'UPI' FROM invoices
WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9001'
  AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.invoice_id = invoices.invoice_id);
INSERT INTO payments (invoice_id, payment_date, amount, payment_method)
SELECT invoice_id, '2026-02-15', 1000.00, 'Bank Transfer' FROM invoices
WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9002'
  AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.invoice_id = invoices.invoice_id);
INSERT INTO payments (invoice_id, payment_date, amount, payment_method)
SELECT invoice_id, '2026-03-10', 300.00, 'Cash' FROM invoices
WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9003'
  AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.invoice_id = invoices.invoice_id);
INSERT INTO payments (invoice_id, payment_date, amount, payment_method)
SELECT invoice_id, '2026-04-25', 1890.00, 'Cheque' FROM invoices
WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9004'
  AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.invoice_id = invoices.invoice_id);
