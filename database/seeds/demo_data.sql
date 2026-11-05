-- DEMO DATA ONLY: optional sample companies for local demonstrations.
-- Import manually after database/database.sql. GSTIN values are examples.
USE billing_sales_db;

INSERT INTO companies (company_name, gstin, phone, email, address) VALUES
('Aarav Office Supplies', '27ABCDE1234F1Z5', '9876543210', 'hello@aarav.example', '12 Market Road, Mumbai'),
('Blue Sky Traders', '29BCDEF2345G1Z6', '9123456780', 'sales@bluesky.example', '8 Lake View, Bengaluru'),
('Cedar Tech Services', '07CDEFG3456H1Z7', '9988776655', 'contact@cedar.example', '21 Central Avenue, New Delhi'),
('Dawn Fresh Foods', '33DEFGH4567J1Z8', '9001122334', 'orders@dawnfresh.example', '5 Garden Street, Chennai'),
('Evergreen Home Needs', '19EFGHI5678K1Z9', '9090909090', 'info@evergreen.example', '44 Station Road, Kolkata');
