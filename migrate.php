<?php
// Simple migration runner so collaborators can run: php migrate.php
declare(strict_types=1);

// Reuse the same env loading logic as index.php
function loadEnv(string $path): array {
    $config = [];
    if (!is_file($path)) {
        return $config;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $value = substr($value, 1, -1);
        }
        $parts = explode('_', $key);
        if (count($parts) >= 2 && $parts[0] === 'DB' && $parts[1] === 'MYSQL') {
            $prefix = 'db_mysql';
            $last = strtolower($parts[count($parts) - 1]);
            if (!isset($config[$prefix])) {
                $config[$prefix] = [];
            }
            $config[$prefix][$last] = $value;
        } else {
            $config[strtolower($key)] = $value;
        }
    }
    return $config;
}

$envPath = __DIR__ . '/.env';
if (!is_file($envPath)) {
    fwrite(STDERR, "Missing .env file. Copy .env.example to .env and configure DB.\n");
    exit(1);
}
$config = loadEnv($envPath);
$mysql = $config['db_mysql'] ?? null;
if (empty($mysql) || empty($mysql['database'])) {
    fwrite(STDERR, "DB_MYSQL_* not configured in .env.\n");
    exit(1);
}

$dsn = 'mysql:host=' . ($mysql['host'] ?? '127.0.0.1')
    . ';port=' . ($mysql['port'] ?? 3306)
    . ';dbname=' . $mysql['database']
    . ';charset=' . ($mysql['charset'] ?? 'utf8mb4');

$pdo = new PDO($dsn, $mysql['username'] ?? 'root', $mysql['password'] ?? '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "Running database migrations...\n";

// Base schema (copied from index.php)
$mysqlSchema = [
    "CREATE TABLE IF NOT EXISTS sender_accounts (id INT AUTO_INCREMENT PRIMARY KEY, name TEXT NOT NULL, email VARCHAR(255) NOT NULL, password TEXT NOT NULL, host VARCHAR(255) NOT NULL, port INT NOT NULL, encryption VARCHAR(32) DEFAULT NULL, is_active TINYINT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS marketing_contacts (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, company_name VARCHAR(255) DEFAULT NULL, notes TEXT DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS email_campaigns (id INT AUTO_INCREMENT PRIMARY KEY, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, recipient_filter VARCHAR(64) NOT NULL DEFAULT 'marketing_list', rotate_senders TINYINT NOT NULL DEFAULT 1, status VARCHAR(32) NOT NULL DEFAULT 'queued', total_recipients INT NOT NULL DEFAULT 0, sent_count INT NOT NULL DEFAULT 0, failed_count INT NOT NULL DEFAULT 0, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS email_logs (id INT AUTO_INCREMENT PRIMARY KEY, email_campaign_id INT NOT NULL, sender_account_id INT DEFAULT NULL, recipient_email VARCHAR(255) NOT NULL, status VARCHAR(32) NOT NULL DEFAULT 'pending', sent_at DATETIME DEFAULT NULL, opened_at DATETIME DEFAULT NULL, open_tracking_token VARCHAR(64) DEFAULT NULL, error_message TEXT DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (email_campaign_id) REFERENCES email_campaigns(id), FOREIGN KEY (sender_account_id) REFERENCES sender_accounts(id))",
    "CREATE TABLE IF NOT EXISTS contact_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS contact_group_members (contact_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY (contact_id, group_id), FOREIGN KEY (contact_id) REFERENCES marketing_contacts(id) ON DELETE CASCADE, FOREIGN KEY (group_id) REFERENCES contact_groups(id) ON DELETE CASCADE)",
    "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS email_design (id INT PRIMARY KEY, header_html TEXT NOT NULL DEFAULT '', footer_html TEXT NOT NULL DEFAULT '', footer_bg_color VARCHAR(32) NOT NULL DEFAULT '#f1f5f9', block_text_color VARCHAR(32) NOT NULL DEFAULT '#1e293b', header_logo_url VARCHAR(500) DEFAULT '', header_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', footer_logo_url VARCHAR(500) DEFAULT '', footer_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', body_outline_color VARCHAR(32) DEFAULT '')",
    "INSERT IGNORE INTO email_design (id, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color) VALUES (1, '', '', '#f1f5f9', '#1e293b', '', 'text_only', '', 'text_only', '')",
    "CREATE TABLE IF NOT EXISTS api_keys (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, api_key VARCHAR(64) NOT NULL UNIQUE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS sms_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS sms_recipients (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, phone_number VARCHAR(32) NOT NULL, group_id INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (group_id) REFERENCES sms_groups(id) ON DELETE CASCADE)",
    "CREATE TABLE IF NOT EXISTS sms_logs (id INT AUTO_INCREMENT PRIMARY KEY, group_id INT NOT NULL, group_name VARCHAR(255) NOT NULL, recipient_name VARCHAR(255) NOT NULL, phone_number VARCHAR(32) NOT NULL, message TEXT NOT NULL, status VARCHAR(32) NOT NULL DEFAULT 'sent', error_message TEXT DEFAULT NULL, sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
];

// Run base schema (first N statements that don't depend on later alters)
foreach ($mysqlSchema as $i => $sql) {
    if ($i >= count($mysqlSchema) - 2) {
        break;
    }
    $pdo->exec($sql);
}

// Email design extra columns
foreach ([
    "ALTER TABLE email_design ADD COLUMN header_logo_url VARCHAR(500) DEFAULT ''",
    "ALTER TABLE email_design ADD COLUMN header_mode VARCHAR(20) NOT NULL DEFAULT 'text_only'",
    "ALTER TABLE email_design ADD COLUMN footer_logo_url VARCHAR(500) DEFAULT ''",
    "ALTER TABLE email_design ADD COLUMN footer_mode VARCHAR(20) NOT NULL DEFAULT 'text_only'",
    "ALTER TABLE email_design ADD COLUMN body_outline_color VARCHAR(32) DEFAULT ''",
] as $alterSql) {
    try { $pdo->exec($alterSql); } catch (Throwable $e) { }
}

// API keys extra columns
foreach ([
    "ALTER TABLE api_keys ADD COLUMN default_template_id INT NULL DEFAULT NULL",
    "ALTER TABLE api_keys ADD COLUMN default_sender_ids VARCHAR(255) NULL DEFAULT NULL",
    "ALTER TABLE api_keys ADD COLUMN link_slug VARCHAR(64) NULL DEFAULT NULL",
    "ALTER TABLE api_keys ADD UNIQUE KEY api_keys_link_slug (link_slug)",
] as $alterSql) {
    try { $pdo->exec($alterSql); } catch (Throwable $e) { }
}

// Email design templates table
$pdo->exec("CREATE TABLE IF NOT EXISTS email_design_templates (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL UNIQUE, header_html TEXT NOT NULL DEFAULT '', footer_html TEXT NOT NULL DEFAULT '', footer_bg_color VARCHAR(32) NOT NULL DEFAULT '#f1f5f9', block_text_color VARCHAR(32) NOT NULL DEFAULT '#1e293b', header_logo_url VARCHAR(500) DEFAULT '', header_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', footer_logo_url VARCHAR(500) DEFAULT '', footer_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', body_outline_color VARCHAR(32) DEFAULT '', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");

// Final two schema statements
$pdo->exec($mysqlSchema[count($mysqlSchema) - 2]);
$pdo->exec($mysqlSchema[count($mysqlSchema) - 1]);

$pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_recipients (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, phone_number VARCHAR(32) NOT NULL, group_id INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (group_id) REFERENCES whatsapp_groups(id) ON DELETE CASCADE)");
$pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_logs (id INT AUTO_INCREMENT PRIMARY KEY, group_id INT NOT NULL, group_name VARCHAR(255) NOT NULL, recipient_name VARCHAR(255) NOT NULL, phone_number VARCHAR(32) NOT NULL, message TEXT NOT NULL, status VARCHAR(32) NOT NULL DEFAULT 'sent', error_message TEXT DEFAULT NULL, sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");

// Per-user columns and indexes
foreach (['sender_accounts', 'email_campaigns', 'contact_groups', 'api_keys', 'sms_groups', 'whatsapp_groups'] as $tbl) {
    try { $pdo->exec("ALTER TABLE $tbl ADD COLUMN user_id INT NULL DEFAULT NULL"); } catch (Throwable $e) { }
    try { $pdo->exec("UPDATE $tbl SET user_id = 1 WHERE user_id IS NULL"); } catch (Throwable $e) { }
    try { $pdo->exec("ALTER TABLE $tbl ADD INDEX idx_user_id (user_id)"); } catch (Throwable $e) { }
}
try { $pdo->exec('ALTER TABLE marketing_contacts ADD COLUMN user_id INT NULL DEFAULT NULL'); } catch (Throwable $e) { }
try { $pdo->exec('UPDATE marketing_contacts SET user_id = 1 WHERE user_id IS NULL'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE marketing_contacts ADD INDEX idx_user_id (user_id)'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE marketing_contacts DROP INDEX email'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE marketing_contacts ADD UNIQUE KEY user_email (user_id, email)'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE email_design ADD COLUMN user_id INT NULL DEFAULT NULL'); } catch (Throwable $e) { }
try { $pdo->exec('UPDATE email_design SET user_id = 1 WHERE user_id IS NULL'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE email_design ADD INDEX idx_user_id (user_id)'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE email_design_templates ADD COLUMN user_id INT NULL DEFAULT NULL'); } catch (Throwable $e) { }
try { $pdo->exec('UPDATE email_design_templates SET user_id = 1 WHERE user_id IS NULL'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE email_design_templates ADD INDEX idx_user_id (user_id)'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE email_design_templates DROP INDEX name'); } catch (Throwable $e) { }
try { $pdo->exec('ALTER TABLE email_design_templates ADD UNIQUE KEY user_name (user_id, name)'); } catch (Throwable $e) { }

// Admin column and default admin creation
try { $pdo->exec('ALTER TABLE users ADD COLUMN is_admin TINYINT NOT NULL DEFAULT 0'); } catch (Throwable $e) { }
try {
    $defaultAdminEmail = trim($config['admin_email'] ?? 'admin@example.com');
    $defaultAdminPassword = $config['admin_password'] ?? 'admin123';
    if ($defaultAdminEmail !== '') {
        $defaultAdminHash = password_hash($defaultAdminPassword, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO users (email, password, name, is_admin) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE password = VALUES(password), is_admin = 1, name = VALUES(name)')
            ->execute([$defaultAdminEmail, $defaultAdminHash, 'Admin']);
        $pdo->prepare('UPDATE users SET is_admin = 0 WHERE email != ?')->execute([$defaultAdminEmail]);
    }
} catch (Throwable $e) { }

echo "Migrations completed.\n";
