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

ALTER TABLE companies
    ADD COLUMN business_id INT UNSIGNED NULL AFTER company_id,
    DROP INDEX gstin,
    ADD UNIQUE INDEX uq_companies_business_gstin (business_id, gstin),
    ADD UNIQUE INDEX uq_companies_business_company (business_id, company_id),
    ADD INDEX idx_companies_business (business_id),
    ADD CONSTRAINT fk_companies_business FOREIGN KEY (business_id)
        REFERENCES businesses(business_id) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE invoices
    ADD COLUMN business_id INT UNSIGNED NULL AFTER invoice_id,
    DROP INDEX invoice_number,
    DROP FOREIGN KEY fk_invoices_company,
    ADD UNIQUE INDEX uq_invoices_business_number (business_id, invoice_number),
    ADD INDEX idx_invoices_business (business_id),
    ADD CONSTRAINT fk_invoices_business FOREIGN KEY (business_id)
        REFERENCES businesses(business_id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ADD CONSTRAINT fk_invoices_business_company
        FOREIGN KEY (business_id, company_id)
        REFERENCES companies(business_id, company_id)
        ON DELETE RESTRICT ON UPDATE RESTRICT;
