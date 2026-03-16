<?php
/**
 * Standalone Email Marketing
 * Single-file app. Copy this folder to another computer and run: php -S localhost:8080
 * Optional: run "composer install" in this folder for SMTP sending via PHPMailer.
 * Configuration: copy .env.example to .env and set your values.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Load .env file into config array. Keys like DB_MYSQL_HOST become $config['db_mysql']['host']. */
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
if (!is_file($envPath) && is_file(__DIR__ . '/.env.example')) {
    copy(__DIR__ . '/.env.example', $envPath);
}
if (!is_file($envPath)) {
    die('Create a .env file (copy .env.example to .env) and set DB_MYSQL_DATABASE, DB_MYSQL_USERNAME, etc.');
}
$config = loadEnv($envPath);
$appName = $config['app_name'] ?? 'Standalone Email Marketing';

$mysql = $config['db_mysql'] ?? null;
if (empty($mysql) || empty($mysql['database'])) {
    die('Set DB_MYSQL_* in .env (see .env.example).');
}
$dsn = 'mysql:host=' . ($mysql['host'] ?? '127.0.0.1') . ';port=' . ($mysql['port'] ?? 3306) . ';dbname=' . $mysql['database'] . ';charset=' . ($mysql['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $mysql['username'] ?? 'root', $mysql['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Create tables if not exist (MySQL / XAMPP) — one statement per exec for compatibility
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
foreach ($mysqlSchema as $i => $sql) {
    if ($i >= count($mysqlSchema) - 2) {
        break;
    }
    $pdo->exec($sql);
}
foreach (["ALTER TABLE email_design ADD COLUMN header_logo_url VARCHAR(500) DEFAULT ''", "ALTER TABLE email_design ADD COLUMN header_mode VARCHAR(20) NOT NULL DEFAULT 'text_only'", "ALTER TABLE email_design ADD COLUMN footer_logo_url VARCHAR(500) DEFAULT ''", "ALTER TABLE email_design ADD COLUMN footer_mode VARCHAR(20) NOT NULL DEFAULT 'text_only'", "ALTER TABLE email_design ADD COLUMN body_outline_color VARCHAR(32) DEFAULT ''"] as $alterSql) {
    try { $pdo->exec($alterSql); } catch (Throwable $e) { /* column exists */ }
}
foreach (["ALTER TABLE api_keys ADD COLUMN default_template_id INT NULL DEFAULT NULL", "ALTER TABLE api_keys ADD COLUMN default_sender_ids VARCHAR(255) NULL DEFAULT NULL", "ALTER TABLE api_keys ADD COLUMN link_slug VARCHAR(64) NULL DEFAULT NULL", "ALTER TABLE api_keys ADD UNIQUE KEY api_keys_link_slug (link_slug)"] as $alterSql) {
    try { $pdo->exec($alterSql); } catch (Throwable $e) { /* column exists */ }
}
$pdo->exec("CREATE TABLE IF NOT EXISTS email_design_templates (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL UNIQUE, header_html TEXT NOT NULL DEFAULT '', footer_html TEXT NOT NULL DEFAULT '', footer_bg_color VARCHAR(32) NOT NULL DEFAULT '#f1f5f9', block_text_color VARCHAR(32) NOT NULL DEFAULT '#1e293b', header_logo_url VARCHAR(500) DEFAULT '', header_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', footer_logo_url VARCHAR(500) DEFAULT '', footer_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', body_outline_color VARCHAR(32) DEFAULT '', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec($mysqlSchema[count($mysqlSchema) - 2]);
$pdo->exec($mysqlSchema[count($mysqlSchema) - 1]);

// Per-user dashboard: add user_id to all data tables so each user sees only their own data
foreach (['sender_accounts', 'email_campaigns', 'contact_groups', 'api_keys', 'sms_groups'] as $tbl) {
    try { $pdo->exec("ALTER TABLE $tbl ADD COLUMN user_id INT NULL DEFAULT NULL"); } catch (Throwable $e) { /* exists */ }
    try { $pdo->exec("UPDATE $tbl SET user_id = 1 WHERE user_id IS NULL"); } catch (Throwable $e) { }
    try { $pdo->exec("ALTER TABLE $tbl ADD INDEX idx_user_id (user_id)"); } catch (Throwable $e) { /* exists */ }
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

function h($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function normalizeEmailBlockHtml(string $html): string {
    $html = preg_replace('#<head\b[^>]*>.*?</head>#is', '', $html) ?? $html;
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
    $html = preg_replace('#<link\b[^>]*>#is', '', $html) ?? $html;
    $html = preg_replace('#</?(html|body)\b[^>]*>#is', '', $html) ?? $html;
    return trim($html);
}

/** Split combined header+footer HTML by delimiter. Returns [header_html, footer_html]. Delimiter: <!-- FOOTER --> (case-insensitive). */
function splitHeaderFooterHtml(string $combined): array {
    $combined = trim($combined);
    if ($combined === '') {
        return ['', ''];
    }
    if (preg_match('#\s*<!--\s*FOOTER\s*-->\s*#is', $combined, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1];
        $header = trim(substr($combined, 0, $pos));
        $footer = trim(substr($combined, $pos + strlen($m[0][0])));
        return [$header, $footer];
    }
    return [$combined, $combined];
}

// Clean paths used by router.php — links use these so the address bar shows /compose not index.php?page=compose
$cleanPaths = [
    'landing' => '/',
    'index' => '/dashboard',
    'login' => '/login',
    'register' => '/register',
    'logout' => '/logout',
    'compose' => '/compose',
    'senders' => '/senders',
    'sender-edit' => '/sender-edit',
    'contacts' => '/contacts',
    'contact-edit' => '/contact-edit',
    'contacts-import' => '/contacts-import',
    'groups' => '/groups',
    'group-edit' => '/group-edit',
    'design' => '/design',
    'template-html' => '/template-html',
    'api' => '/api',
    'logs' => '/logs',
    'sms' => '/sms',
];

function baseUrl(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    return rtrim(dirname($script), '/') ?: '';
}

function url(string $page = '', array $q = []): string {
    global $cleanPaths;
    if (isset($cleanPaths[$page])) {
        $path = $cleanPaths[$page];
        return $path . (count($q) ? '?' . http_build_query($q) : '');
    }
    $base = baseUrl();
    $path = $base . ($base ? '/' : '') . 'index.php';
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    $params = $page ? ['page' => $page] : [];
    $params = array_merge($params, $q);
    return $path . (count($params) ? '?' . http_build_query($params) : '');
}

function currentPage(): string {
    return $_GET['page'] ?? 'landing';
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
$publicPages = ['landing', 'login', 'register', 'logout'];
if (!isLoggedIn() && !in_array(currentPage(), $publicPages)) {
    header('Location: ' . url('login'));
    exit;
}

// Current user id for per-user dashboard (all data scoped to this user)
$userId = isLoggedIn() ? (int) $_SESSION['user_id'] : 0;

if (currentPage() === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . url('landing'));
    exit;
}

function navClass(string $page): string {
    $current = currentPage();
    if ($page === 'groups' && ($current === 'groups' || $current === 'group-edit')) return 'bg-[#f54a00] text-white';
    if ($page === 'contacts' && in_array($current, ['contacts', 'contact-edit', 'contacts-import'])) return 'bg-[#f54a00] text-white';
    if ($page === 'senders' && ($current === 'senders' || $current === 'sender-edit')) return 'bg-[#f54a00] text-white';
    if ($page === 'sms' && $current === 'sms') return 'bg-[#f54a00] text-white';
    if ($page === 'logs' && in_array($current, ['logs', 'sms-logs', 'whatsapp-logs'], true)) return 'bg-[#f54a00] text-white';

    return $current === $page
        ? 'bg-[#f54a00] text-white'
        : 'text-slate-300 hover:bg-white/10 hover:text-white';
}

/** Base URL for open-tracking pixel (must be reachable by recipient's email client). */
function trackingBaseUrl(): string {
    global $config;
    $base = trim((string) ($config['tracking_base_url'] ?? ''));
    if ($base !== '') {
        if (!preg_match('#^https?://#i', $base)) {
            $base = 'https://' . $base;
        }
        return rtrim($base, '/');
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = $scheme . '://' . $host;
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($script && $script !== '/index.php' && $script !== '/router.php') {
        $base .= rtrim(dirname($script), '/');
    }
    return rtrim($base, '/');
}

/** Inject tracking pixel + optional "View in browser" link so opens are recorded even when images are blocked. */
function injectTrackingPixel(string $body, string $baseUrl, string $token): string {
    $url = $baseUrl . '/track/email-open/' . $token;
    $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    // 1) Pixel – works when the email client loads images
    $img = '<img src="' . $urlEsc . '" width="1" height="1" alt="" border="0" style="width:1px;height:1px;border:0;display:block;" />';
    // 2) Link – same URL: when recipient clicks (e.g. "View in browser") we still record the open
    $link = '<p style="margin:8px 0 0;font-size:11px;color:#94a3b8;"><a href="' . $urlEsc . '" style="color:#94a3b8;text-decoration:underline;">View in browser</a></p>';
    $trackingBlock = $img . "\n" . $link;
    if (stripos($body, '</body>') !== false) {
        return preg_replace('/(<\/body>)/i', $trackingBlock . "\n" . '$1', $body, 1);
    }
    if (stripos($body, '</html>') !== false) {
        return preg_replace('/(<\/html>)/i', $trackingBlock . "\n" . '$1', $body, 1);
    }
    return $body . "\n" . $trackingBlock;
}

// ---------- Open-tracking pixel (GET) ----------
if (isset($_GET['action']) && $_GET['action'] === 'track-open' && isset($_GET['token'])) {
    $token = trim((string) $_GET['token']);
    if (strlen($token) >= 16 && strlen($token) <= 64 && preg_match('/^[a-zA-Z0-9]+$/', $token)) {
        $st = $pdo->prepare("SELECT id FROM email_logs WHERE open_tracking_token = ? AND status = 'sent' AND (opened_at IS NULL OR opened_at = '')");
        $st->execute([$token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $pdo->prepare("UPDATE email_logs SET opened_at = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), (int) $row['id']]);
        }
    }
    $pixel = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQI12NgAAIABQAB'
        . 'Nl7BcQAAAABJRU5ErkJggg==',
        true
    );
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($accept, 'text/html') !== false) {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Done</title></head><body style="margin:0;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9;color:#64748b;font-size:14px;">You can close this tab.</body></html>';
    } else {
        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($pixel));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Cross-Origin-Resource-Policy: cross-origin');
        header('Access-Control-Allow-Origin: *');
        header('X-Content-Type-Options: nosniff');
        echo $pixel;
    }
    exit;
}

// ---------- POST handlers ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action === 'sender-save') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $host = trim($_POST['host'] ?? '');
        $port = (int) ($_POST['port'] ?? 587);
        $encryption = in_array($_POST['encryption'] ?? '', ['tls', 'ssl']) ? $_POST['encryption'] : null;
        $isActive = !empty($_POST['is_active']);

        $err = [];
        if ($name === '') $err[] = 'Name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Valid email is required.';
        if (!$id && $password === '') $err[] = 'Password is required.';
        if ($host === '') $err[] = 'Host is required.';
        if ($port < 1 || $port > 65535) $err[] = 'Port must be 1-65535.';

        if (empty($err)) {
            $pwStore = $password !== '' ? base64_encode($password) : null;
            if ($id) {
                $stmt = $pdo->prepare('UPDATE sender_accounts SET name=?, email=?, host=?, port=?, encryption=?, is_active=? WHERE id=?');
                $stmt->execute([$name, $email, $host, $port, $encryption, $isActive ? 1 : 0, $id]);
                if ($stmt->rowCount() && $pwStore !== null) {
                    $pdo->prepare('UPDATE sender_accounts SET password=? WHERE id=?')->execute([$pwStore, $id]);
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO sender_accounts (name, email, password, host, port, encryption, is_active) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$name, $email, $pwStore, $host, $port, $encryption, $isActive ? 1 : 0]);
            }
            header('Location: ' . url('senders', ['success' => 'Sender saved.']));
            exit;
        }
        $_GET['page'] = 'sender-edit';
        $_GET['id'] = $id;
        $flashError = implode(' ', $err);
    }

    if ($action === 'sender-delete' && isset($_POST['id'])) {
        $pdo->prepare('DELETE FROM sender_accounts WHERE id=?')->execute([(int) $_POST['id']]);
        header('Location: ' . url('senders', ['success' => 'Sender deleted.']));
        exit;
    }

    if ($action === 'contact-save') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $email = trim($_POST['email'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '') ?: null;
        $notes = trim($_POST['notes'] ?? '') ?: null;
        $groupIds = array_map('intval', array_filter((array) ($_POST['group_ids'] ?? [])));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flashError = 'Valid email is required.';
            $_GET['page'] = 'contact-edit';
            if ($id) $_GET['id'] = $id;
        } else {
            try {
                if ($id) {
                    $pdo->prepare('UPDATE marketing_contacts SET email=?, company_name=?, notes=? WHERE id=? AND user_id=?')->execute([$email, $companyName, $notes, $id, $userId]);
                    $contactId = $id;
                } else {
                    $pdo->prepare('INSERT INTO marketing_contacts (email, company_name, notes, user_id) VALUES (?,?,?,?)')->execute([$email, $companyName, $notes, $userId]);
                    $contactId = (int) $pdo->lastInsertId();
                }
                $pdo->prepare('DELETE FROM contact_group_members WHERE contact_id=?')->execute([$contactId]);
                $ins = $pdo->prepare('INSERT INTO contact_group_members (contact_id, group_id) VALUES (?,?)');
                foreach ($groupIds as $gid) {
                    if ($gid > 0) $ins->execute([$contactId, $gid]);
                }
                header('Location: ' . url('contacts', ['success' => 'Contact saved.']));
                exit;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                    $flashError = 'That email is already in the list.';
                } else {
                    $flashError = $e->getMessage();
                }
                $_GET['page'] = 'contact-edit';
                if ($id) $_GET['id'] = $id;
            }
        }
    }

    if ($action === 'contact-delete' && isset($_POST['id'])) {
        $cid = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM contact_group_members WHERE contact_id=?')->execute([$cid]);
        $pdo->prepare('DELETE FROM marketing_contacts WHERE id=? AND user_id=?')->execute([$cid, $userId]);
        header('Location: ' . url('contacts', ['success' => 'Contact removed.']));
        exit;
    }

    if ($action === 'api-key-create') {
        $name = trim($_POST['api_key_name'] ?? '');
        if ($name === '') {
            $flashError = 'Name is required for the API key.';
            $_GET['page'] = 'api';
        } else {
            $key = bin2hex(random_bytes(32));
            try {
                $pdo->prepare('INSERT INTO api_keys (name, api_key, user_id) VALUES (?, ?, ?)')->execute([$name, $key, $userId]);
                $_SESSION['new_api_key'] = $key;
                $_SESSION['new_api_key_name'] = $name;
                header('Location: ' . url('api', ['created' => 1]));
                exit;
            } catch (PDOException $e) {
                $flashError = 'Could not create key. Try again.';
                $_GET['page'] = 'api';
            }
        }
    }

    if ($action === 'api-key-delete' && isset($_POST['id'])) {
        $pdo->prepare('DELETE FROM api_keys WHERE id = ? AND user_id = ?')->execute([(int) $_POST['id'], $userId]);
        header('Location: ' . url('api', ['success' => 'API key deleted.']));
        exit;
    }

    if ($action === 'register') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirmation'] ?? '';
        $name = trim($_POST['name'] ?? '');
        
        if ($email === '' || $password === '') {
            $flashError = 'Email and password are required.';
            $_GET['page'] = 'register';
        } elseif ($password !== $confirm) {
            $flashError = 'Passwords do not match.';
            $_GET['page'] = 'register';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users (email, password, name) VALUES (?, ?, ?)')->execute([$email, $hash, $name]);
                header('Location: ' . url('login', ['success' => 'Registration successful. Please login.']));
                exit;
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                $flashError = (strpos($msg, 'UNIQUE') !== false || strpos($msg, 'Duplicate entry') !== false) ? 'Email already registered.' : $msg;
                $_GET['page'] = 'register';
            }
        }
    }

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare('SELECT id, password, name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: ' . url('index'));
            exit;
        } else {
            $flashError = 'Invalid email or password.';
            $_GET['page'] = 'login';
        }
    }

    if ($action === 'logout' || (currentPage() === 'logout')) {
        session_destroy();
        header('Location: ' . url('landing'));
        exit;
    }

    if ($action === 'group-save') {
        $gid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            header('Location: ' . url('group-edit', ['id' => $gid, 'error' => 'Group name is required.']));
            exit;
        } else {
            if ($gid) {
                $pdo->prepare('UPDATE contact_groups SET name=? WHERE id=? AND user_id=?')->execute([$name, $gid, $userId]);
            } else {
                $pdo->prepare('INSERT INTO contact_groups (name, user_id) VALUES (?, ?)')->execute([$name, $userId]);
                $gid = (int)$pdo->lastInsertId();
            }
            header('Location: ' . url('groups', ['success' => 'Group saved.']));
            exit;
        }
    }

    if ($action === 'group-delete' && isset($_POST['id'])) {
        $gid = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM contact_group_members WHERE group_id=?')->execute([$gid]);
        $pdo->prepare('DELETE FROM contact_groups WHERE id=? AND user_id=?')->execute([$gid, $userId]);
        header('Location: ' . url('groups', ['success' => 'Group deleted.']));
        exit;
    }

    if ($action === 'group-add-member') {
        $gid = (int)($_POST['group_id'] ?? 0);
        $cid = (int)($_POST['contact_id'] ?? 0);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($gid && $cid) {
            try {
                $pdo->prepare('INSERT INTO contact_group_members (contact_id, group_id) VALUES (?,?)')->execute([$cid, $gid]);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Member added.']);
                    exit;
                }
                header('Location: ' . url('group-edit', ['id' => $gid, 'success' => 'Member added.']));
                exit;
            } catch (PDOException $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Member already in group.']);
                    exit;
                }
                header('Location: ' . url('group-edit', ['id' => $gid, 'error' => 'Member already in group.']));
                exit;
            }
        }
    }

    if ($action === 'group-create-add-member') {
        $gid = (int)($_POST['group_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $company = trim($_POST['company_name'] ?? '');
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        
        if ($gid && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $pdo->beginTransaction();
                // Check if contact exists first
                $st = $pdo->prepare('SELECT id FROM marketing_contacts WHERE email = ? AND user_id = ?');
                $st->execute([$email, $userId]);
                $contact = $st->fetch();
                
                if ($contact) {
                    $cid = (int)$contact['id'];
                } else {
                    $pdo->prepare('INSERT INTO marketing_contacts (email, company_name, user_id) VALUES (?, ?, ?)')
                        ->execute([$email, $company ?: null, $userId]);
                    $cid = (int)$pdo->lastInsertId();
                }
                
                // Link to group - use IGNORE to avoid errors if already linked
                $pdo->prepare('INSERT IGNORE INTO contact_group_members (contact_id, group_id) VALUES (?, ?)')
                    ->execute([$cid, $gid]);
                
                $pdo->commit();
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Contact created and added to group.',
                        'data' => [
                            'id' => $cid,
                            'email' => $email,
                            'company_name' => $company ?: '—'
                        ]
                    ]);
                    exit;
                }
                
                header('Location: ' . url('group-edit', ['id' => $gid, 'success' => 'Contact created and added to group.']));
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Could not add contact: ' . $e->getMessage()]);
                    exit;
                }
                header('Location: ' . url('group-edit', ['id' => $gid, 'error' => 'Could not add contact: ' . addslashes($e->getMessage())]));
                exit;
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Valid email is required.']);
                exit;
            }
            header('Location: ' . url('group-edit', ['id' => $gid, 'error' => 'Valid email is required.']));
            exit;
        }
    }

    if ($action === 'group-remove-member') {
        $gid = (int)($_POST['group_id'] ?? 0);
        $cid = (int)($_POST['contact_id'] ?? 0);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($gid && $cid) {
            $pdo->prepare('DELETE FROM contact_group_members WHERE group_id = ? AND contact_id = ?')->execute([$gid, $cid]);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Member removed.']);
                exit;
            }
            header('Location: ' . url('group-edit', ['id' => $gid, 'success' => 'Member removed.']));
                exit;
            }
        }

    // ---------- SMS Notification ----------
    if ($action === 'sms-group-create') {
        $name = trim($_POST['sms_group_name'] ?? '');
        if ($name === '') {
            header('Location: ' . url('sms', ['error' => 'Group name is required.']));
            exit;
        }
        $pdo->prepare('INSERT INTO sms_groups (name, user_id) VALUES (?, ?)')->execute([$name, $userId]);
        header('Location: ' . url('sms', ['success' => 'SMS group created.']));
        exit;
    }

    if ($action === 'sms-group-delete' && isset($_POST['id'])) {
        $gid = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM sms_groups WHERE id=? AND user_id=?')->execute([$gid, $userId]);
        header('Location: ' . url('sms', ['success' => 'SMS group deleted.']));
        exit;
    }

    if ($action === 'sms-recipient-add') {
        $names = isset($_POST['recipient_name']) && is_array($_POST['recipient_name'])
            ? array_map('trim', $_POST['recipient_name'])
            : [trim($_POST['recipient_name'] ?? '')];
        $phones = isset($_POST['phone_number']) && is_array($_POST['phone_number'])
            ? array_map(function ($p) { return preg_replace('/\s+/', '', trim($p)); }, $_POST['phone_number'])
            : [preg_replace('/\s+/', '', trim($_POST['phone_number'] ?? ''))];
        $pairs = [];
        $max = max(count($names), count($phones));
        for ($i = 0; $i < $max; $i++) {
            $name = $names[$i] ?? '';
            $phone = $phones[$i] ?? '';
            if ($name !== '' || $phone !== '') {
                if ($name === '' || $phone === '') {
                    header('Location: ' . url('sms', ['error' => 'Each recipient must have both name and phone number.']));
                    exit;
                }
                $pairs[] = [$name, $phone];
            }
        }
        if (empty($pairs)) {
            header('Location: ' . url('sms', ['error' => 'Add at least one recipient (name and phone number).']));
            exit;
        }
        $groupChoice = trim($_POST['group_id'] ?? '');
        $newGroupName = trim($_POST['new_group_name'] ?? '');
        $groupId = null;
        if ($groupChoice === 'new') {
            if ($newGroupName === '') {
                header('Location: ' . url('sms', ['error' => 'Enter a name for the new group.']));
                exit;
            }
            $pdo->prepare('INSERT INTO sms_groups (name, user_id) VALUES (?, ?)')->execute([$newGroupName, $userId]);
            $groupId = (int) $pdo->lastInsertId();
        } else {
            $groupId = (int) $groupChoice;
            $chk = $pdo->prepare('SELECT id FROM sms_groups WHERE id = ? AND user_id = ?');
            $chk->execute([$groupId, $userId]);
            if (!$chk->fetch()) {
                header('Location: ' . url('sms', ['error' => 'Invalid group.']));
                exit;
            }
        }
        if ($groupId < 1) {
            header('Location: ' . url('sms', ['error' => 'Select a group or create a new one.']));
            exit;
        }
        $ins = $pdo->prepare('INSERT INTO sms_recipients (name, phone_number, group_id) VALUES (?, ?, ?)');
        foreach ($pairs as $p) {
            $ins->execute([$p[0], $p[1], $groupId]);
        }
        $n = count($pairs);
        $msg = $n === 1 ? 'Recipient added.' : $n . ' recipients added.';
        if ($groupChoice === 'new') $msg = 'Group created and ' . strtolower($msg);
        header('Location: ' . url('sms', ['success' => $msg]));
        exit;
    }

    if ($action === 'sms-recipient-delete' && isset($_POST['id'])) {
        $pdo->prepare('DELETE FROM sms_recipients WHERE id=?')->execute([(int) $_POST['id']]);
        header('Location: ' . url('sms', ['success' => 'Recipient removed.']));
        exit;
    }

    if ($action === 'sms-send') {
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $apiKey = trim($config['semaphore_api_key'] ?? '');
        if ($groupId < 1 || $message === '') {
            header('Location: ' . url('sms', ['error' => 'Please select a group and enter a message.']));
            exit;
        }
        if ($apiKey === '') {
            header('Location: ' . url('sms', ['error' => 'Semaphore API key is not configured. Add SEMAPHORE_API_KEY to your .env file.']));
            exit;
        }
        $stmt = $pdo->prepare('SELECT r.id, r.name, r.phone_number FROM sms_recipients r INNER JOIN sms_groups g ON g.id = r.group_id AND g.user_id = ? WHERE r.group_id = ? ORDER BY r.id');
        $stmt->execute([$userId, $groupId]);
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($recipients)) {
            header('Location: ' . url('sms', ['error' => 'Selected group has no recipients.']));
            exit;
        }
        $singleRecipientId = (int) ($_POST['recipient_id'] ?? 0);
        if ($singleRecipientId > 0) {
            $one = null;
            foreach ($recipients as $r) {
                if ((int)$r['id'] === $singleRecipientId) { $one = $r; break; }
            }
            if ($one) {
                $recipients = [$one];
            }
        }
        $numbers = array_map(function ($r) {
            $p = preg_replace('/[^0-9+]/', '', $r['phone_number']);
            if (strlen($p) === 10 && substr($p, 0, 1) === '9') {
                $p = '63' . $p;
            } elseif (strlen($p) === 11 && substr($p, 0, 2) === '09') {
                $p = '63' . substr($p, 1);
            }
            return $p;
        }, $recipients);
        $numbers = array_values(array_unique(array_filter($numbers, fn($n) => strlen($n) >= 10)));
        $sent = 0;
        $failed = 0;
        $errors = [];
        // Send one API request per recipient (single-message) — matches working PHP example and improves delivery
        $usePriority = in_array(trim(strtolower((string)($config['semaphore_use_priority'] ?? '0'))), ['1', 'true', 'yes'], true);
        $url = $usePriority ? 'https://api.semaphore.co/api/v4/priority' : 'https://api.semaphore.co/api/v4/messages';
        $sslVerify = trim(strtolower((string)($config['semaphore_ssl_verify'] ?? '1')));
        $verifyPeer = !in_array($sslVerify, ['0', 'false', 'no', 'off'], true);
        $statusByNumber = [];
        $logDir = __DIR__ . '/data';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/sms-api-response.log';

        foreach ($recipients as $r) {
            $num = preg_replace('/[^0-9+]/', '', $r['phone_number']);
            if (strlen($num) === 10 && $num[0] === '9') $num = '63' . $num;
            elseif (strlen($num) === 11 && substr($num, 0, 2) === '09') $num = '63' . substr($num, 1);
            $numberForApi = (strlen($num) === 12 && substr($num, 0, 2) === '63') ? '0' . substr($num, 2) : $num;
            // Semaphore docs: messages starting with "TEST" are silently ignored and not sent
            // Only include sendername if set in .env; otherwise omit so Semaphore uses account default (avoids "senderName not valid")
            $params = ['apikey' => $apiKey, 'number' => $numberForApi, 'message' => $message];
            $senderName = trim((string)($config['semaphore_sender_name'] ?? ''));
            if ($senderName !== '') $params['sendername'] = $senderName;
            $postData = http_build_query($params);
            $response = false;
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifyPeer ? 2 : 0);
                $response = curl_exec($ch);
                curl_close($ch);
            }
            if ($response === false && function_exists('file_get_contents')) {
                $ctx = stream_context_create([
                    'http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $postData, 'timeout' => 30],
                    'ssl' => ['verify_peer' => $verifyPeer],
                ]);
                $response = @file_get_contents($url, false, $ctx);
            }
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' to=' . $numberForApi . ' response=' . ($response ?: '') . "\n---\n", FILE_APPEND | LOCK_EX);

            $st = 'unknown';
            $err = null;
            if ($response !== false) {
                $decoded = json_decode($response, true);
                $item = is_array($decoded) && isset($decoded['recipient']) ? $decoded : (is_array($decoded) && isset($decoded[0]) ? $decoded[0] : null);
                if (is_array($item)) {
                    $st = isset($item['status']) ? strtolower(trim((string)$item['status'])) : 'sent';
                    $err = isset($item['error_message']) ? $item['error_message'] : null;
                }
            }
            $statusByNumber[$num] = ['status' => $st === 'failed' ? 'failed' : ($st !== 'unknown' ? $st : 'sent'), 'error' => $err];
            if ($st === 'failed') $failed++; else $sent++;
        }
        $responseParsed = true;

        $groupRow = $pdo->prepare('SELECT name FROM sms_groups WHERE id = ? AND user_id = ?');
        $groupRow->execute([$groupId, $userId]);
        $groupName = $groupRow->fetchColumn() ?: 'Unknown';
        $insertSmsLog = $pdo->prepare('INSERT INTO sms_logs (group_id, group_name, recipient_name, phone_number, message, status, error_message, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $sentAt = date('Y-m-d H:i:s');
        foreach ($recipients as $r) {
            $norm = preg_replace('/[^0-9+]/', '', $r['phone_number']);
            if (strlen($norm) === 10 && $norm[0] === '9') $norm = '63' . $norm;
            elseif (strlen($norm) === 11 && substr($norm, 0, 2) === '09') $norm = '63' . substr($norm, 1);
            $info = $statusByNumber[$norm] ?? ['status' => $responseParsed ? 'sent' : 'unknown', 'error' => null];
            $insertSmsLog->execute([
                $groupId,
                $groupName,
                $r['name'],
                $r['phone_number'],
                $message,
                $info['status'],
                $info['error'],
                $sentAt,
            ]);
        }

        $msg = $sent . ' message(s) sent.';
        if ($failed > 0) $msg .= ' ' . $failed . ' failed.';
        header('Location: ' . url('sms', ['success' => $msg]));
        exit;
    }

    if ($action === 'api-key-update-defaults' && isset($_POST['id'])) {
        $keyId = (int) $_POST['id'];
        $defaultTemplateId = isset($_POST['default_template_id']) ? (int) $_POST['default_template_id'] : 0;
        $defaultSenderIds = '';
        if (!empty($_POST['default_sender_ids']) && is_array($_POST['default_sender_ids'])) {
            $defaultSenderIds = implode(',', array_map('intval', array_filter($_POST['default_sender_ids'], fn($x) => (int)$x > 0)));
        } elseif (!empty($_POST['default_sender_ids']) && is_string($_POST['default_sender_ids'])) {
            $defaultSenderIds = implode(',', array_filter(array_map('intval', explode(',', str_replace(' ', '', $_POST['default_sender_ids']))), fn($x) => $x > 0));
        }
        $linkSlug = isset($_POST['link_slug']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$_POST['link_slug'])) : '';
        if ($linkSlug !== '' && strlen($linkSlug) < 2) $linkSlug = '';
        $linkSlugFinal = null;
        if ($linkSlug !== '') {
            $other = $pdo->prepare('SELECT id FROM api_keys WHERE link_slug = ? AND id != ? AND user_id = ?');
            $other->execute([$linkSlug, $keyId, $userId]);
            if (!$other->fetch()) $linkSlugFinal = $linkSlug;
        }
        $pdo->prepare('UPDATE api_keys SET default_template_id = ?, default_sender_ids = ?, link_slug = ? WHERE id = ? AND user_id = ?')
            ->execute([$defaultTemplateId > 0 ? $defaultTemplateId : null, $defaultSenderIds !== '' ? $defaultSenderIds : null, $linkSlugFinal, $keyId, $userId]);
        header('Location: ' . url('api') . '&success=' . urlencode('Defaults and API link saved.'));
        exit;
    }

    if ($action === 'api-key-set-link' && isset($_POST['id'])) {
        $keyId = (int) $_POST['id'];
        $linkSlug = isset($_POST['link_slug']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$_POST['link_slug'])) : '';
        if ($linkSlug !== '' && strlen($linkSlug) < 2) {
            $flashError = 'API link slug must be at least 2 characters.';
            $_GET['page'] = 'api';
        } elseif ($linkSlug !== '') {
            $other = $pdo->prepare('SELECT id FROM api_keys WHERE link_slug = ? AND id != ? AND user_id = ?');
            $other->execute([$linkSlug, $keyId, $userId]);
            if ($other->fetch()) {
                $flashError = 'That API link slug is already used by another key.';
                $_GET['page'] = 'api';
            } else {
                $pdo->prepare('UPDATE api_keys SET link_slug = ? WHERE id = ? AND user_id = ?')->execute([$linkSlug, $keyId, $userId]);
                header('Location: ' . url('api') . '&success=' . urlencode('API link saved.'));
                exit;
            }
        } else {
            $pdo->prepare('UPDATE api_keys SET link_slug = NULL WHERE id = ? AND user_id = ?')->execute([$keyId, $userId]);
            header('Location: ' . url('api') . '&success=' . urlencode('API link cleared.'));
            exit;
        }
    }

    if ($action === 'save-design') {
        $headerFooterHtml = normalizeEmailBlockHtml((string) ($_POST['header_footer_html'] ?? ''));
        $headerHtml = $headerFooterHtml;
        $footerHtml = $headerFooterHtml;
        $footerBg = trim($_POST['footer_bg_color'] ?? '#f1f5f9');
        if ($footerBg === '' || !preg_match('/^#[0-9A-Fa-f]{3,8}$/', $footerBg)) $footerBg = '#f1f5f9';
        $textColor = trim($_POST['block_text_color'] ?? '#1e293b');
        if ($textColor === '' || !preg_match('/^#[0-9A-Fa-f]{3,8}$/', $textColor)) $textColor = '#1e293b';
        $headerLogoUrl = trim($_POST['header_logo_url'] ?? '');
        $footerLogoUrl = trim($_POST['footer_logo_url'] ?? '');
        $uploadDir = __DIR__ . '/data/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $allowedTypes = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
        if (!empty($_FILES['header_logo']['tmp_name']) && $_FILES['header_logo']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['header_logo']['tmp_name']);
            finfo_close($finfo);
            if (isset($allowedTypes[$mime])) {
                $ext = $allowedTypes[$mime];
                $name = 'header_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['header_logo']['tmp_name'], $uploadDir . '/' . $name)) {
                    $headerLogoUrl = 'uploads/' . $name;
                }
            }
        }
        if (!empty($_FILES['footer_logo']['tmp_name']) && $_FILES['footer_logo']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['footer_logo']['tmp_name']);
            finfo_close($finfo);
            if (isset($allowedTypes[$mime])) {
                $ext = $allowedTypes[$mime];
                $name = 'footer_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['footer_logo']['tmp_name'], $uploadDir . '/' . $name)) {
                    $footerLogoUrl = 'uploads/' . $name;
                }
            }
        }
        $headerMode = $_POST['header_mode'] ?? 'text_only';
        $footerMode = $_POST['footer_mode'] ?? 'text_only';
        if (!in_array($headerMode, ['logo_only', 'text_only', 'logo_and_text'], true)) $headerMode = 'text_only';
        if (!in_array($footerMode, ['logo_only', 'text_only', 'logo_and_text'], true)) $footerMode = 'text_only';
        $bodyOutline = trim($_POST['body_outline_color'] ?? '');
        if ($bodyOutline !== '' && !preg_match('/^#[0-9A-Fa-f]{3,8}$/', $bodyOutline)) $bodyOutline = '';
        $templateName = trim((string)($_POST['template_name'] ?? ''));
        $templateEditId = (int)($_POST['template_edit_id'] ?? 0);
        $upd = $pdo->prepare('UPDATE email_design SET header_html=?, footer_html=?, footer_bg_color=?, block_text_color=?, header_logo_url=?, header_mode=?, footer_logo_url=?, footer_mode=?, body_outline_color=? WHERE id=1');
        $upd->execute([$headerHtml, $footerHtml, $footerBg, $textColor, $headerLogoUrl, $headerMode, $footerLogoUrl, $footerMode, $bodyOutline]);
        if ($upd->rowCount() === 0) {
            $pdo->prepare('INSERT INTO email_design (id, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color) VALUES (1,?,?,?,?,?,?,?,?,?,?)')->execute([$headerHtml, $footerHtml, $footerBg, $textColor, $headerLogoUrl, $headerMode, $footerLogoUrl, $footerMode, $bodyOutline]);
        }
        if ($templateName !== '') {
            if ($templateEditId > 0) {
                $pdo->prepare('UPDATE email_design_templates SET name=?, header_html=?, footer_html=?, footer_bg_color=?, block_text_color=?, header_logo_url=?, header_mode=?, footer_logo_url=?, footer_mode=?, body_outline_color=? WHERE id=?')
                    ->execute([$templateName, $headerHtml, $footerHtml, $footerBg, $textColor, $headerLogoUrl, $headerMode, $footerLogoUrl, $footerMode, $bodyOutline, $templateEditId]);
            } else {
                $pdo->prepare('INSERT INTO email_design_templates (name, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE header_html=VALUES(header_html), footer_html=VALUES(footer_html), footer_bg_color=VALUES(footer_bg_color), block_text_color=VALUES(block_text_color), header_logo_url=VALUES(header_logo_url), header_mode=VALUES(header_mode), footer_logo_url=VALUES(footer_logo_url), footer_mode=VALUES(footer_mode), body_outline_color=VALUES(body_outline_color)')->execute([$templateName, $headerHtml, $footerHtml, $footerBg, $textColor, $headerLogoUrl, $headerMode, $footerLogoUrl, $footerMode, $bodyOutline]);
            }
        }
        header('Location: ' . url('design') . '?success=' . urlencode($templateName !== '' ? 'Design saved as template "' . $templateName . '".' : 'Email design saved.'));
        exit;
    }

    if ($action === 'contacts-import-csv') {
        if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $flashError = 'Please upload a valid CSV file.';
            $_GET['page'] = 'contacts-import';
        } else {
        $added = 0;
        $skipped = 0;
        $rows = array_map('str_getcsv', file($_FILES['file']['tmp_name']));
        $header = $rows ? array_shift($rows) : [];
        $emailIdx = 0;
        $companyIdx = null;
        foreach ($header as $i => $col) {
            $c = strtolower(trim($col));
            if (in_array($c, ['email', 'e-mail', 'email address'], true)) $emailIdx = $i;
            if (in_array($c, ['company', 'company name', 'company_name'], true)) $companyIdx = $i;
        }
        $stmt = $pdo->prepare('INSERT IGNORE INTO marketing_contacts (email, company_name, user_id) VALUES (?,?,?)');
        foreach ($rows as $row) {
            $email = trim($row[$emailIdx] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }
            $company = $companyIdx !== null ? trim($row[$companyIdx] ?? '') : null;
            $stmt->execute([$email, $company ?: null, $userId]);
            if ($stmt->rowCount()) $added++; else $skipped++;
        }
        header('Location: ' . url('contacts') . '&success=' . urlencode("Import done: $added added, $skipped skipped."));
        exit;
        }
    }

    if ($action === 'send') {
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $recipientFilter = $_POST['recipient_filter'] ?? 'all';
        $recipientGroupIds = array_map('intval', array_filter((array) ($_POST['recipient_groups'] ?? [])));
        $rotateSenders = !empty($_POST['rotate_senders']);

        if ($subject === '' || $body === '') {
            $flashError = 'Subject and body are required.';
            $_GET['page'] = 'compose';
        } else {
            $recipients = null;
            if ($recipientFilter === 'groups') {
                if (empty($recipientGroupIds)) {
                    $flashError = 'Select at least one group, or choose "All contacts".';
                    $_GET['page'] = 'compose';
                } else {
                    $placeholders = implode(',', array_fill(0, count($recipientGroupIds), '?'));
                    $stmt = $pdo->prepare("SELECT DISTINCT c.id, c.email FROM marketing_contacts c INNER JOIN contact_group_members m ON m.contact_id = c.id AND c.user_id = ? WHERE m.group_id IN ($placeholders) ORDER BY c.email");
                    $stmt->execute(array_merge([$userId], $recipientGroupIds));
                    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            if ($recipients === null) {
                $stmt = $pdo->prepare('SELECT id, email FROM marketing_contacts WHERE user_id = ? ORDER BY email');
                $stmt->execute([$userId]);
                $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            if (!isset($flashError)) {
            $composeSender = $_POST['compose_sender'] ?? 'all';
            if ($composeSender !== '' && $composeSender !== 'all') {
                $senderId = (int) $composeSender;
                if ($senderId > 0) {
                    $stmt = $pdo->prepare('SELECT id FROM sender_accounts WHERE is_active = 1 AND id = ?');
                    $stmt->execute([$senderId]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $senders = $row ? [$row] : [];
                    if (empty($senders)) {
                        $flashError = 'Selected sender not found or inactive. Choose "All active senders" or another sender.';
                        $_GET['page'] = 'compose';
                    }
                } else {
                    $senders = $pdo->query('SELECT id FROM sender_accounts WHERE is_active=1 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                $senders = $pdo->query('SELECT id FROM sender_accounts WHERE is_active=1 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            }
            if (!isset($flashError)) {
            $pdo->prepare('INSERT INTO email_campaigns (subject, body, recipient_filter, rotate_senders, status, total_recipients, user_id) VALUES (?,?,?,?,?,?,?)')
                ->execute([$subject, $body, $recipientFilter, $rotateSenders ? 1 : 0, 'sending', count($recipients), $userId]);
            $campaignId = (int) $pdo->lastInsertId();

            $insertLog = $pdo->prepare('INSERT INTO email_logs (email_campaign_id, sender_account_id, recipient_email, status, open_tracking_token) VALUES (?,?,?,?,?)');
            $senderCount = count($senders);
            foreach ($recipients as $i => $r) {
                $senderId = $senderCount > 0 ? (int) $senders[$i % $senderCount]['id'] : null;
                $token = bin2hex(random_bytes(16));
                $insertLog->execute([$campaignId, $senderId, $r['email'], 'pending', $token]);
            }

            // Send immediately (sync) for standalone
            $logsStmt = $pdo->prepare('SELECT l.id, l.recipient_email, l.open_tracking_token, s.email as from_email, s.password, s.host, s.port, s.encryption, s.name as from_name FROM email_logs l LEFT JOIN sender_accounts s ON s.id = l.sender_account_id WHERE l.email_campaign_id = ? AND l.status = ?');
            $logsStmt->execute([$campaignId, 'pending']);
            $sent = 0;
            $failed = 0;
            $usePhpMailer = file_exists(__DIR__ . '/vendor/autoload.php');
            $trackingBase = trackingBaseUrl();
            if ($usePhpMailer) {
                require_once __DIR__ . '/vendor/autoload.php';
            }

            while ($log = $logsStmt->fetch(PDO::FETCH_ASSOC)) {
                if ($usePhpMailer && !empty($log['from_email']) && !empty($log['host'])) {
                    try {
                        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                        $mail->CharSet = 'UTF-8';
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $bodyWithPixel = !empty($log['open_tracking_token']) ? injectTrackingPixel($body, $trackingBase, $log['open_tracking_token']) : $body;
                        $mail->Body = $bodyWithPixel;
                        $mail->setFrom($log['from_email'], $log['from_name'] ?? '');
                        $mail->addAddress($log['recipient_email']);
                        $mail->isSMTP();
                        $mail->Host = $log['host'];
                        $mail->Port = (int) $log['port'];
                        $mail->SMTPAuth = true;
                        $mail->Username = $log['from_email'];
                        $mail->Password = base64_decode($log['password'], true) ?: $log['password'];
                        if (!empty($log['encryption'])) {
                            $mail->SMTPSecure = $log['encryption'] === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        } else {
                            $mail->SMTPSecure = false;
                        }
                        $mail->send();
                        $pdo->prepare('UPDATE email_logs SET status=?, sent_at=? WHERE id=?')->execute(['sent', date('Y-m-d H:i:s'), $log['id']]);
                        $sent++;
                    } catch (\Throwable $e) {
                        $pdo->prepare('UPDATE email_logs SET status=?, error_message=? WHERE id=?')->execute(['failed', $e->getMessage(), $log['id']]);
                        $failed++;
                    }
                } else {
                    $msg = empty($log['from_email']) ? 'No sender account for this recipient.' : 'Run composer install and add active sender accounts.';
                    $pdo->prepare('UPDATE email_logs SET status=?, error_message=? WHERE id=?')->execute(['failed', $msg, $log['id']]);
                    $failed++;
                }
            }

            $pdo->prepare('UPDATE email_campaigns SET status=?, sent_count=?, failed_count=?, completed_at=? WHERE id=?')
                ->execute(['completed', $sent, $failed, date('Y-m-d H:i:s'), $campaignId]);
            header('Location: ' . url('index') . '&success=' . urlencode("Campaign sent. $sent sent, $failed failed."));
            exit;
            }
            }
        }
    }
}

$flashSuccess = $_GET['success'] ?? null;
$flashError = $flashError ?? $_GET['error'] ?? null;
$page = currentPage();

// Sender count for nav (shared); contacts per user
$sendersCount = (int) $pdo->query('SELECT COUNT(*) FROM sender_accounts')->fetchColumn();
$activeSendersCount = (int) $pdo->query('SELECT COUNT(*) FROM sender_accounts WHERE is_active=1')->fetchColumn();
$navCountStmt = $pdo->prepare('SELECT COUNT(*) FROM marketing_contacts WHERE user_id = ?');
$navCountStmt->execute([$userId]);
$contactsCount = (int) $navCountStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= h($page === 'index' ? 'Email Marketing' : ucfirst(str_replace('-', ' ', $page))) ?> - <?= h($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <style>
        @media (max-width: 767px) {
            .mobile-nav-open { overflow: hidden; }
            body { overflow-x: hidden; }
            .no-horizontal-scroll { overflow-x: hidden; max-width: 100vw; }
        }
        @media (min-width: 768px) {
            .sidebar-overlay { display: none !important; }
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 40;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay:not(.hidden) {
            display: block;
        }
        .rotate-180 {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
<div class="flex min-h-screen">
    <?php if (!in_array(currentPage(), $publicPages)): ?>
    <!-- Mobile Header -->
    <header class="md:hidden fixed top-0 left-0 right-0 bg-slate-900 z-30 h-14 flex items-center justify-between px-4 shadow-lg">
        <button id="mobile-menu-btn" type="button" class="p-2 -ml-2 text-white hover:bg-white/10 rounded-lg touch-manipulation" aria-label="Open menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <a href="<?= url('index') ?>" class="text-white font-semibold text-base truncate">Email Marketing</a>
        <div class="w-10"></div>
    </header>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="sidebar-overlay hidden" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="mobile-sidebar" class="w-64 flex-shrink-0 bg-slate-900 flex flex-col fixed md:sticky top-0 h-screen z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="p-6 border-b border-slate-700/50 flex flex-col items-center relative">
            <a href="<?= url('index') ?>" class="flex flex-col items-center text-center">
                <img src="/public/images/logo1.png" alt="Logo" class="h-12 w-auto mb-3">
                <h1 class="text-lg font-semibold text-white tracking-tight">FH Email Marketing</h1>
            </a>
            <button id="mobile-close-btn" type="button" class="md:hidden absolute top-5 right-5 p-2 text-slate-400 hover:text-white" aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto" aria-label="Main">
            <a href="<?= url('index') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= currentPage() === 'index' ? 'bg-[#f54a00] text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>

            <a href="<?= url('compose') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= in_array(currentPage(), ['compose', 'senders', 'contacts']) ? 'bg-[#f54a00] text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                <span>Email Marketing</span>
            </a>

            <a href="<?= url('groups') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= navClass('groups') ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Groups</span>
            </a>
            <a href="<?= url('design') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= navClass('design') ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                <span>Design</span>
            </a>
            <a href="<?= url('api') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= navClass('api') ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                <span>API</span>
            </a>
            <a href="<?= url('logs') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= navClass('logs') ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Logs</span>
            </a>
            <a href="<?= url('sms') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= navClass('sms') ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>SMS</span>
            </a>
        </nav>
        <!-- START: Isolated Logout Section -->
        <div id="logout-section-isolated" class="mt-auto p-3 border-t border-slate-700/50 bg-slate-800">
            <button type="button" class="logout-btn-isolated logout-btn flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium bg-transparent text-red-400 border border-red-500 hover:bg-red-500/20 hover:text-white hover:border-red-400 transition-all w-full text-left">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>LOG OUT</span>
            </button>
        </div>
        <!-- END: Isolated Logout Section -->
    </aside>
    <?php endif; ?>

    <!-- Main content -->
    <main class="flex-1 overflow-auto pt-14 md:pt-0">
        <div class="<?= in_array(currentPage(), $publicPages) ? '' : 'max-w-6xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 py-4 md:py-8' ?> <?= !in_array(currentPage(), $publicPages) ? 'no-horizontal-scroll' : '' ?>">
            <?php if (!in_array(currentPage(), $publicPages)): ?>
                <?php if (!in_array($page, ['api', 'design', 'compose', 'senders', 'contacts', 'group-edit', 'groups', 'logs', 'contact-edit', 'sender-edit', 'contacts-import', 'index', 'sms'])): ?>
                <div class="mb-4 md:mb-6">
                    <h2 class="text-xl md:text-2xl font-semibold text-slate-900"><?= $page === 'index' ? 'Dashboard' : ucfirst(str_replace('-', ' ', $page)) ?></h2>
                    <p class="text-slate-500 text-xs md:text-sm mt-0.5"><?= $page === 'index' ? 'Overview and recent campaigns' : 'Manage your email marketing' ?></p>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (currentPage() !== 'sms' && $flashSuccess): ?>
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 font-medium <?= in_array(currentPage(), $publicPages) ? 'max-w-md mx-auto mt-4' : '' ?>"><?= h($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if (currentPage() !== 'sms' && !empty($flashError)): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 font-medium <?= in_array(currentPage(), $publicPages) ? 'max-w-md mx-auto mt-4' : '' ?>"><?= h($flashError) ?></div>
            <?php endif; ?>

            <?php
            $viewPath = __DIR__ . '/views/' . ($page === 'index' ? 'dashboard' : $page) . '.php';
            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                echo '<div class="p-8 bg-white rounded-2xl shadow border border-slate-100 text-center text-slate-500">Page not found.</div>';
            }
            ?>
        </div>
    </main>
</div>
<script>
(function() {
    var menuBtn = document.getElementById('mobile-menu-btn');
    var closeBtn = document.getElementById('mobile-close-btn');
    var sidebar = document.getElementById('mobile-sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    
    function openMenu() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.classList.add('mobile-nav-open');
    }
    
    function closeMenu() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('mobile-nav-open');
    }
    
    if (menuBtn) menuBtn.addEventListener('click', openMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    if (overlay) overlay.addEventListener('click', closeMenu);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeMenu();
    });
})();
</script>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-[2px] flex items-center justify-center z-50 hidden">
    <div class="bg-slate-800 rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all border border-slate-700/50">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-slate-700 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-red-400">LOG OUT</h3>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Confirm your action</p>
                </div>
            </div>
            <p class="text-slate-300 mb-6">Are you sure you want to LOG OUT from your account?</p>
            <div class="flex items-center justify-center gap-3">
                <button type="button" id="cancelLogout" class="flex-1 px-4 py-2 bg-slate-700 text-slate-300 font-bold rounded-lg border border-slate-600 hover:bg-slate-600 transition-colors">Cancel</button>
                <a href="<?= url('logout') ?>" id="confirmLogout" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-transparent text-red-400 font-black rounded-lg border border-red-400/20 hover:bg-red-500/20 hover:text-white hover:border-red-400 transition-all text-center uppercase tracking-widest text-sm shadow-lg">LOG OUT</a>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize logout modal functionality
(function() {
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogoutBtn = document.getElementById('cancelLogout');
    const confirmLogoutBtn = document.getElementById('confirmLogout');

    // Handle logout button clicks
    document.addEventListener('click', function(e) {
        const logoutBtn = e.target.closest('.logout-btn');
        if (logoutBtn) {
            e.preventDefault();
            logoutModal.classList.remove('hidden');
            logoutModal.classList.add('flex');
            return false;
        }
    });

    // Handle cancel button
    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.classList.add('hidden');
            logoutModal.classList.remove('flex');
            return false;
        });
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !logoutModal.classList.contains('hidden')) {
            cancelLogoutBtn.click();
        }
    });

    // Close modal on backdrop click
    logoutModal.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            cancelLogoutBtn.click();
        }
    });
})();
</script>
</body>
</html>