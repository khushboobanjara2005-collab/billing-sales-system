-- DEMO DATA ONLY: import demo_data.sql first so these five companies exist in a business.
-- These invoices are optional sample records and are never loaded automatically.
USE billing_sales_db;
SET @demo_business_id := (SELECT business_id FROM businesses ORDER BY business_id LIMIT 1);

INSERT INTO invoices (business_id, invoice_number, company_id, invoice_date, subtotal, cgst_rate, sgst_rate, cgst, sgst, total_amount)
SELECT business_id, 'INV-2026-9001', company_id, '2026-01-15', 1000.00, 2.50, 2.50, 25.00, 25.00, 1050.00
FROM companies WHERE business_id = @demo_business_id AND company_name = 'Aarav Office Supplies';
INSERT INTO invoices (business_id, invoice_number, company_id, invoice_date, subtotal, cgst_rate, sgst_rate, cgst, sgst, total_amount)
SELECT business_id, 'INV-2026-9002', company_id, '2026-02-10', 2400.00, 2.50, 2.50, 60.00, 60.00, 2520.00
FROM companies WHERE business_id = @demo_business_id AND company_name = 'Blue Sky Traders';
INSERT INTO invoices (business_id, invoice_number, company_id, invoice_date, subtotal, cgst_rate, sgst_rate, cgst, sgst, total_amount)
SELECT business_id, 'INV-2026-9003', company_id, '2026-03-05', 750.00, 2.50, 2.50, 18.75, 18.75, 787.50
FROM companies WHERE business_id = @demo_business_id AND company_name = 'Cedar Tech Services';
INSERT INTO invoices (business_id, invoice_number, company_id, invoice_date, subtotal, cgst_rate, sgst_rate, cgst, sgst, total_amount)
SELECT business_id, 'INV-2026-9004', company_id, '2026-04-20', 1800.00, 2.50, 2.50, 45.00, 45.00, 1890.00
FROM companies WHERE business_id = @demo_business_id AND company_name = 'Dawn Fresh Foods';
INSERT INTO invoices (business_id, invoice_number, company_id, invoice_date, subtotal, cgst_rate, sgst_rate, cgst, sgst, total_amount)
SELECT business_id, 'INV-2026-9005', company_id, '2026-05-12', 3200.00, 2.50, 2.50, 80.00, 80.00, 3360.00
FROM companies WHERE business_id = @demo_business_id AND company_name = 'Evergreen Home Needs';

INSERT INTO invoice_items (invoice_id, description, quantity, rate, line_total)
SELECT invoice_id, 'Office stationery pack', 2.000, 500.00, 1000.00 FROM invoices WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9001';
INSERT INTO invoice_items (invoice_id, description, quantity, rate, line_total)
SELECT invoice_id, 'Wholesale supply boxes', 4.000, 600.00, 2400.00 FROM invoices WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9002';
INSERT INTO invoice_items (invoice_id, description, quantity, rate, line_total)
SELECT invoice_id, 'Technical support hours', 3.000, 250.00, 750.00 FROM invoices WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9003';
INSERT INTO invoice_items (invoice_id, description, quantity, rate, line_total)
SELECT invoice_id, 'Fresh produce cases', 6.000, 300.00, 1800.00 FROM invoices WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9004';
INSERT INTO invoice_items (invoice_id, description, quantity, rate, line_total)
SELECT invoice_id, 'Home essentials bundle', 8.000, 400.00, 3200.00 FROM invoices WHERE business_id = @demo_business_id AND invoice_number = 'INV-2026-9005';
