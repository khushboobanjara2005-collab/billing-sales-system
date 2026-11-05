CREATE DATABASE IF NOT EXISTS billing_sales_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE billing_sales_db;

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE businesses (
    business_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT UNSIGNED NOT NULL,
    business_name VARCHAR(150) NOT NULL,
    tagline VARCHAR(255) NULL,
    business_type VARCHAR(100) NULL,
    owner_name VARCHAR(150) NULL,
    address VARCHAR(500) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    pincode VARCHAR(10) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(254) NULL,
    gstin VARCHAR(15) NULL,
    state_code CHAR(2) NULL,
    bank_name VARCHAR(100) NULL,
    branch_name VARCHAR(100) NULL,
    account_holder_name VARCHAR(150) NULL,
    account_number VARCHAR(34) NULL,
    ifsc_code VARCHAR(11) NULL,
    invoice_prefix VARCHAR(20) NOT NULL DEFAULT 'INV',
    next_invoice_number INT UNSIGNED NOT NULL DEFAULT 1,
    default_gst_rate DECIMAL(5,2) NOT NULL DEFAULT 2.50,
    jurisdiction VARCHAR(150) NULL,
    authorized_signatory VARCHAR(150) NULL,
    terms_and_conditions TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_businesses_owner (owner_user_id),
    CONSTRAINT fk_businesses_owner FOREIGN KEY (owner_user_id)
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE installation_state (
    state_id TINYINT UNSIGNED PRIMARY KEY,
    legacy_data_claimed TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT chk_installation_state_singleton CHECK (state_id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO installation_state (state_id, legacy_data_claimed) VALUES (1, 0);

CREATE TABLE auth_login_attempts (
    attempt_key CHAR(64) PRIMARY KEY,
    failures SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    first_failed_at DATETIME NOT NULL,
    blocked_until DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE companies (
    company_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    gstin VARCHAR(15) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(254) NULL,
    address TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_companies_business_gstin (business_id, gstin),
    UNIQUE KEY uq_companies_business_company (business_id, company_id),
    INDEX idx_companies_business (business_id),
    INDEX idx_companies_company_name (company_name),
    CONSTRAINT fk_companies_business FOREIGN KEY (business_id)
        REFERENCES businesses(business_id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Retained only as the one-time source for the first registered user's business profile.
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

CREATE TABLE invoices (
    invoice_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    invoice_date DATE NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cgst_rate DECIMAL(5,2) NOT NULL DEFAULT 2.50,
    sgst_rate DECIMAL(5,2) NOT NULL DEFAULT 2.50,
    tax_type ENUM('intra', 'inter') NOT NULL DEFAULT 'intra',
    cgst DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    sgst DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    igst DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    round_off DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invoices_business_number (business_id, invoice_number),
    INDEX idx_invoices_business (business_id),
    INDEX idx_invoices_company_id (company_id),
    INDEX idx_invoices_invoice_date (invoice_date),
    CONSTRAINT fk_invoices_business FOREIGN KEY (business_id)
        REFERENCES businesses(business_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_invoices_business_company FOREIGN KEY (business_id, company_id)
        REFERENCES companies(business_id, company_id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoice_items (
    item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    weigh_bridge_serial VARCHAR(50) NULL,
    item_date DATE NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    rate DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    INDEX idx_invoice_items_invoice_id (invoice_id),
    CONSTRAINT fk_invoice_items_invoice
        FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
        ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payments_invoice_id (invoice_id),
    INDEX idx_payments_payment_date (payment_date),
    CONSTRAINT chk_payments_amount_positive CHECK (amount > 0),
    CONSTRAINT fk_payments_invoice
        FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
