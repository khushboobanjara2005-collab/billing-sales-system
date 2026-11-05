-- DEMO DATA ONLY: optional sample companies for local demonstrations.
-- Import manually after creating a user and business. GSTIN values are examples.
-- This selects the first business in the local demo database.
USE billing_sales_db;

SET @demo_business_id := (SELECT business_id FROM businesses ORDER BY business_id LIMIT 1);

INSERT INTO companies (business_id, company_name, gstin, phone, email, address) VALUES
(@demo_business_id, 'Aarav Office Supplies', '27ABCDE1234F1Z5', '9876543210', 'hello@aarav.example', '12 Market Road, Mumbai'),
(@demo_business_id, 'Blue Sky Traders', '29BCDEF2345G1Z6', '9123456780', 'sales@bluesky.example', '8 Lake View, Bengaluru'),
(@demo_business_id, 'Cedar Tech Services', '07CDEFG3456H1Z7', '9988776655', 'contact@cedar.example', '21 Central Avenue, New Delhi'),
(@demo_business_id, 'Dawn Fresh Foods', '33DEFGH4567J1Z8', '9001122334', 'orders@dawnfresh.example', '5 Garden Street, Chennai'),
(@demo_business_id, 'Evergreen Home Needs', '19EFGHI5678K1Z9', '9090909090', 'info@evergreen.example', '44 Station Road, Kolkata');
