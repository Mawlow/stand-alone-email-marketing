-- Email Marketing – MySQL schema (e.g. XAMPP)
-- Create database: CREATE DATABASE email_marketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Then run this file in phpMyAdmin or: mysql -u root -p email_marketing < mysql_schema.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS sender_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name TEXT NOT NULL,
    email VARCHAR(255) NOT NULL,
    password TEXT NOT NULL,
    host VARCHAR(255) NOT NULL,
    port INT NOT NULL,
    encryption VARCHAR(32) DEFAULT NULL,
    is_active TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS marketing_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    company_name VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS email_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    recipient_filter VARCHAR(64) NOT NULL DEFAULT 'marketing_list',
    rotate_senders TINYINT NOT NULL DEFAULT 1,
    status VARCHAR(32) NOT NULL DEFAULT 'queued',
    total_recipients INT NOT NULL DEFAULT 0,
    sent_count INT NOT NULL DEFAULT 0,
    failed_count INT NOT NULL DEFAULT 0,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_campaign_id INT NOT NULL,
    sender_account_id INT DEFAULT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    sent_at DATETIME DEFAULT NULL,
    opened_at DATETIME DEFAULT NULL,
    open_tracking_token VARCHAR(64) DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_campaign_id) REFERENCES email_campaigns(id),
    FOREIGN KEY (sender_account_id) REFERENCES sender_accounts(id)
);

CREATE TABLE IF NOT EXISTS contact_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_group_members (
    contact_id INT NOT NULL,
    group_id INT NOT NULL,
    PRIMARY KEY (contact_id, group_id),
    FOREIGN KEY (contact_id) REFERENCES marketing_contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES contact_groups(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS email_design (
    id INT PRIMARY KEY,
    header_html TEXT NOT NULL DEFAULT '',
    footer_html TEXT NOT NULL DEFAULT '',
    footer_bg_color VARCHAR(32) NOT NULL DEFAULT '#f1f5f9',
    block_text_color VARCHAR(32) NOT NULL DEFAULT '#1e293b',
    header_logo_url VARCHAR(500) DEFAULT '',
    header_mode VARCHAR(20) NOT NULL DEFAULT 'text_only',
    footer_logo_url VARCHAR(500) DEFAULT '',
    footer_mode VARCHAR(20) NOT NULL DEFAULT 'text_only',
    body_outline_color VARCHAR(32) DEFAULT ''
);

INSERT IGNORE INTO email_design (id, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color) VALUES (1, '', '', '#f1f5f9', '#1e293b', '', 'text_only', '', 'text_only', '');
