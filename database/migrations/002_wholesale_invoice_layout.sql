USE billing_sales_db;

-- Existing invoice and item rows are preserved; new detail fields are nullable for old rows.
ALTER TABLE invoice_items
    ADD COLUMN weigh_bridge_serial VARCHAR(50) NULL AFTER invoice_id,
    ADD COLUMN item_date DATE NULL AFTER weigh_bridge_serial;

ALTER TABLE invoices
    ADD COLUMN tax_type ENUM('intra', 'inter') NOT NULL DEFAULT 'intra' AFTER sgst_rate,
    ADD COLUMN igst DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER sgst,
    ADD COLUMN round_off DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER igst;

CREATE TABLE seller_profile (
    profile_id TINYINT UNSIGNED PRIMARY KEY,
    seller_name VARCHAR(150) NOT NULL,
    business_description VARCHAR(255) NULL,
    address VARCHAR(500) NULL,
    phone VARCHAR(30) NULL,
    gstin VARCHAR(15) NULL,
    bank_name VARCHAR(100) NULL,
    branch VARCHAR(100) NULL,
    account_number VARCHAR(34) NULL,
    ifsc_code VARCHAR(11) NULL,
    terms TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_seller_profile_singleton CHECK (profile_id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- These are explicitly demo values; replace them in Seller Profile before issuing real invoices.
INSERT INTO seller_profile
    (profile_id, seller_name, business_description, address, phone, gstin,
     bank_name, branch, account_number, ifsc_code, terms)
VALUES
    (1, 'Sample Fuel Traders',
     'Wholesale Seller of Fuel and Pure Babul Sawdust Briquettes',
     '123 Industrial Estate, Sample City, Sample State',
     '6547025592', '08AAAAA0000A1Z5',
     'Sample Bank (Demo)', 'Sample City Branch (Demo)', '000000000000', 'SAMP0000000',
     CONCAT('1. All disputes subject to Sample City jurisdiction.', CHAR(10),
            '2. Goods once sold will not be taken back.', CHAR(10),
            '3. Interest @ 25% will be charged if payment is not made within 7 days.', CHAR(10),
            '4. Errors and omissions excepted.'));
