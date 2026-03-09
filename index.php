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
    "CREATE TABLE IF NOT EXISTS email_design (id INT PRIMARY KEY, header_html TEXT NOT NULL DEFAULT '', footer_html TEXT NOT NULL DEFAULT '', footer_bg_color VARCHAR(32) NOT NULL DEFAULT '#f1f5f9', block_text_color VARCHAR(32) NOT NULL DEFAULT '#1e293b', header_logo_url VARCHAR(500) DEFAULT '', header_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', footer_logo_url VARCHAR(500) DEFAULT '', footer_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', body_outline_color VARCHAR(32) DEFAULT '')",
    "INSERT IGNORE INTO email_design (id, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color) VALUES (1, '', '', '#f1f5f9', '#1e293b', '', 'text_only', '', 'text_only', '')",
    "CREATE TABLE IF NOT EXISTS api_keys (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, api_key VARCHAR(64) NOT NULL UNIQUE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)",
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
$pdo->exec("CREATE TABLE IF NOT EXISTS email_design_templates (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL UNIQUE, header_html TEXT NOT NULL DEFAULT '', footer_html TEXT NOT NULL DEFAULT '', footer_bg_color VARCHAR(32) NOT NULL DEFAULT '#f1f5f9', block_text_color VARCHAR(32) NOT NULL DEFAULT '#1e293b', header_logo_url VARCHAR(500) DEFAULT '', header_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', footer_logo_url VARCHAR(500) DEFAULT '', footer_mode VARCHAR(20) NOT NULL DEFAULT 'text_only', body_outline_color VARCHAR(32) DEFAULT '', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec($mysqlSchema[count($mysqlSchema) - 2]);
$pdo->exec($mysqlSchema[count($mysqlSchema) - 1]);

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
    'index' => '/',
    'compose' => '/compose',
    'senders' => '/senders',
    'sender-edit' => '/sender-edit',
    'contacts' => '/contacts',
    'contact-edit' => '/contact-edit',
    'contacts-import' => '/contacts-import',
    'groups' => '/groups',
    'group-edit' => '/group-edit',
    'design' => '/design',
    'api' => '/api',
    'logs' => '/logs',
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
    return $_GET['page'] ?? 'index';
}

function navClass(string $page): string {
    return currentPage() === $page
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
                if ($pwStore !== null) {
                    $pdo->prepare('UPDATE sender_accounts SET password=? WHERE id=?')->execute([$pwStore, $id]);
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO sender_accounts (name, email, password, host, port, encryption, is_active) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$name, $email, $pwStore, $host, $port, $encryption, $isActive ? 1 : 0]);
            }
            header('Location: ' . url('senders') . '&success=' . urlencode('Sender saved.'));
            exit;
        }
        $_GET['page'] = 'sender-edit';
        $_GET['id'] = $id;
        $flashError = implode(' ', $err);
    }

    if ($action === 'sender-delete' && isset($_POST['id'])) {
        $pdo->prepare('DELETE FROM sender_accounts WHERE id=?')->execute([(int) $_POST['id']]);
        header('Location: ' . url('senders') . '&success=' . urlencode('Sender deleted.'));
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
                    $pdo->prepare('UPDATE marketing_contacts SET email=?, company_name=?, notes=? WHERE id=?')->execute([$email, $companyName, $notes, $id]);
                    $contactId = $id;
                } else {
                    $pdo->prepare('INSERT INTO marketing_contacts (email, company_name, notes) VALUES (?,?,?)')->execute([$email, $companyName, $notes]);
                    $contactId = (int) $pdo->lastInsertId();
                }
                $pdo->prepare('DELETE FROM contact_group_members WHERE contact_id=?')->execute([$contactId]);
                $ins = $pdo->prepare('INSERT INTO contact_group_members (contact_id, group_id) VALUES (?,?)');
                foreach ($groupIds as $gid) {
                    if ($gid > 0) $ins->execute([$contactId, $gid]);
                }
                header('Location: ' . url('contacts') . '&success=' . urlencode('Contact saved.'));
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
        $pdo->prepare('DELETE FROM marketing_contacts WHERE id=?')->execute([$cid]);
        header('Location: ' . url('contacts') . '&success=' . urlencode('Contact removed.'));
        exit;
    }

    if ($action === 'group-save') {
        $gid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $flashError = 'Group name is required.';
            $_GET['page'] = 'group-edit';
            if ($gid) $_GET['id'] = $gid;
        } else {
            if ($gid) {
                $pdo->prepare('UPDATE contact_groups SET name=? WHERE id=?')->execute([$name, $gid]);
            } else {
                $pdo->prepare('INSERT INTO contact_groups (name) VALUES (?)')->execute([$name]);
            }
            header('Location: ' . url('groups') . '&success=' . urlencode('Group saved.'));
            exit;
        }
    }

    if ($action === 'group-delete' && isset($_POST['id'])) {
        $pdo->prepare('DELETE FROM contact_group_members WHERE group_id=?')->execute([(int) $_POST['id']]);
        $pdo->prepare('DELETE FROM contact_groups WHERE id=?')->execute([(int) $_POST['id']]);
        header('Location: ' . url('groups') . '&success=' . urlencode('Group deleted.'));
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
                $pdo->prepare('INSERT INTO api_keys (name, api_key) VALUES (?, ?)')->execute([$name, $key]);
                $_SESSION['new_api_key'] = $key;
                $_SESSION['new_api_key_name'] = $name;
                header('Location: ' . url('api') . '&created=1');
                exit;
            } catch (PDOException $e) {
                $flashError = 'Could not create key. Try again.';
                $_GET['page'] = 'api';
            }
        }
    }

    if ($action === 'api-key-delete' && isset($_POST['id'])) {
        $pdo->prepare('DELETE FROM api_keys WHERE id = ?')->execute([(int) $_POST['id']]);
        header('Location: ' . url('api') . '&success=' . urlencode('API key deleted.'));
        exit;
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
        $pdo->prepare('REPLACE INTO email_design (id, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$headerHtml, $footerHtml, $footerBg, $textColor, $headerLogoUrl, $headerMode, $footerLogoUrl, $footerMode, $bodyOutline]);
        if ($templateName !== '') {
            if ($templateEditId > 0) {
                $pdo->prepare('UPDATE email_design_templates SET name=?, header_html=?, footer_html=?, footer_bg_color=?, block_text_color=?, header_logo_url=?, header_mode=?, footer_logo_url=?, footer_mode=?, body_outline_color=? WHERE id=?')
                    ->execute([$templateName, $headerHtml, $footerHtml, $footerBg, $textColor, $headerLogoUrl, $headerMode, $footerLogoUrl, $footerMode, $bodyOutline, $templateEditId]);
            } else {
                $pdo->prepare('INSERT INTO email_design_templates (name, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE header_html=VALUES(header_html), footer_html=VALUES(footer_html), footer_bg_color=VALUES(footer_bg_color), block_text_color=VALUES(block_text_color), header_logo_url=VALUES(header_logo_url), header_mode=VALUES(header_mode), footer_logo_url=VALUES(footer_logo_url), footer_mode=VALUES(footer_mode), body_outline_color=VALUES(body_outline_color)')->execute([$templateName, $headerHtml, $footerHtml, $footerBg, $textColor, $headerLogoUrl, $headerMode, $footerLogoUrl, $footerMode, $bodyOutline]);
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
        $stmt = $pdo->prepare('INSERT IGNORE INTO marketing_contacts (email, company_name) VALUES (?,?)');
        foreach ($rows as $row) {
            $email = trim($row[$emailIdx] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }
            $company = $companyIdx !== null ? trim($row[$companyIdx] ?? '') : null;
            $stmt->execute([$email, $company ?: null]);
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
                    $stmt = $pdo->prepare("SELECT DISTINCT c.id, c.email FROM marketing_contacts c INNER JOIN contact_group_members m ON m.contact_id = c.id WHERE m.group_id IN ($placeholders) ORDER BY c.email");
                    $stmt->execute(array_values($recipientGroupIds));
                    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            if ($recipients === null) {
                $recipients = $pdo->query('SELECT id, email FROM marketing_contacts ORDER BY email')->fetchAll(PDO::FETCH_ASSOC);
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
            $pdo->prepare('INSERT INTO email_campaigns (subject, body, recipient_filter, rotate_senders, status, total_recipients) VALUES (?,?,?,?,?,?)')
                ->execute([$subject, $body, $recipientFilter, $rotateSenders ? 1 : 0, 'sending', count($recipients)]);
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
$flashError = $flashError ?? null;
$page = $_GET['page'] ?? 'index';

// Sender count for nav
$sendersCount = (int) $pdo->query('SELECT COUNT(*) FROM sender_accounts')->fetchColumn();
$activeSendersCount = (int) $pdo->query('SELECT COUNT(*) FROM sender_accounts WHERE is_active=1')->fetchColumn();
$contactsCount = (int) $pdo->query('SELECT COUNT(*) FROM marketing_contacts')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page === 'index' ? 'Email Marketing' : ucfirst(str_replace('-', ' ', $page))) ?> - <?= h($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
<div class="flex min-h-screen">
    <!-- Left sidebar -->
    <aside class="w-64 flex-shrink-0 bg-slate-900 flex flex-col sticky top-0 h-screen">
        <div class="p-5 border-b border-slate-700/50">
            <a href="<?= url('index') ?>" class="block">
                <h1 class="text-lg font-semibold text-white tracking-tight">Email Marketing</h1>
                <p class="text-slate-400 text-xs mt-0.5">Campaigns & contacts</p>
            </a>
        </div>
        <nav class="flex-1 p-3 space-y-0.5" aria-label="Main">
            <a href="<?= url('index') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= currentPage() === 'index' ? 'bg-[#f54a00] text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>
            <a href="<?= url('compose') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= navClass('compose') ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <span>Compose</span>
            </a>
            <a href="<?= url('senders') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= navClass('senders') ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>Senders</span>
            </a>
            <a href="<?= url('contacts') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= navClass('contacts') ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Contacts</span>
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
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Logs</span>
            </a>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-auto">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold text-slate-900"><?= $page === 'index' ? 'Dashboard' : ucfirst(str_replace('-', ' ', $page)) ?></h2>
                <p class="text-slate-500 text-sm mt-0.5"><?= $page === 'index' ? 'Overview and recent campaigns' : 'Manage your email marketing' ?></p>
            </div>

            <?php if ($flashSuccess): ?>
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 font-medium"><?= h($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashError)): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 font-medium"><?= h($flashError) ?></div>
            <?php endif; ?>

            <?php
    // ---------- Dashboard ----------
    if ($page === 'index'):
        $campaigns = $pdo->query('SELECT * FROM email_campaigns ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow border-2 border-slate-200 p-4 md:p-6">
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-1">Sender Accounts</p>
            <p class="text-2xl font-bold text-slate-900"><?= $sendersCount ?> total, <?= $activeSendersCount ?> active</p>
            <a href="<?= url('senders') ?>" class="text-[#ff8904] font-semibold text-sm mt-2 inline-block hover:underline">Manage →</a>
        </div>
        <div class="bg-white rounded-xl shadow border-2 border-slate-200 p-4 md:p-6">
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-1">Marketing list</p>
            <p class="text-2xl font-bold text-slate-900"><?= $contactsCount ?> contact(s)</p>
            <a href="<?= url('contacts') ?>" class="text-[#ff8904] font-semibold text-sm mt-2 inline-block hover:underline">Manage →</a>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10">
            <h2 class="text-sm md:text-base font-semibold text-white uppercase">Recent Campaigns</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Subject</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Recipients</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Status</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Sent / Failed</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $c): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 font-semibold text-slate-900"><?= h(mb_substr($c['subject'], 0, 50)) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($c['total_recipients']) ?></td>
                        <td class="px-4 md:px-8 py-4"><span class="px-2 py-1 rounded text-xs font-bold <?= $c['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>"><?= h($c['status']) ?></span></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($c['sent_count']) ?> / <?= h($c['failed_count']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-500 text-sm"><?= h($c['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($campaigns)): ?>
                    <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500">No campaigns yet. <a href="<?= url('compose') ?>" class="text-[#ff8904] font-bold hover:underline">Compose one</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-4"><a href="<?= url('compose') ?>" class="inline-flex items-center px-4 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Compose campaign</a></p>
    <?php endif; ?>

    <?php
    // ---------- Compose ----------
    if ($page === 'compose'):
        $templateSubject = 'Simplify your hiring process — Start with us';
        $templateBody = '<p style="margin:0 0 16px; font-size:11px; font-weight:700; color:#ff8904;">Hire smarter. Hire faster.</p><h1 style="margin:0 0 20px; font-size:28px; font-weight:800; color:#0f172a;">Simplify your hiring process</h1><p style="margin:0 0 32px; font-size:15px; color:#64748b;">We streamline recruitment — from posting jobs to managing applicants — in one platform.</p><p><a href="#" style="display:inline-block; padding:14px 28px; background:#0f172a; color:#fff!important; text-decoration:none; font-weight:700; border-radius:12px;">Get started for free →</a></p>';
        $composeGroups = $pdo->query('SELECT g.id, g.name, (SELECT COUNT(*) FROM contact_group_members WHERE group_id = g.id) as cnt FROM contact_groups g ORDER BY g.name')->fetchAll(PDO::FETCH_ASSOC);
        $composeSenders = $pdo->query('SELECT id, name, email FROM sender_accounts WHERE is_active=1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        // Header and footer start empty; user loads them via Load template
        $composeHeader = '';
        $composeFooter = '';
        $composeFooterBg = '#f1f5f9';
        $composeBlockTextColor = '#1e293b';
        $composeHeaderLogo = '';
        $composeFooterLogo = '';
        $composeBodyOutline = '';
        $composeHeaderMode = 'text_only';
        $composeFooterMode = 'text_only';
    ?>
    <?php if ($activeSendersCount === 0): ?>
    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800">No sender accounts yet. <a href="<?= url('senders') ?>" class="font-semibold underline">Add senders</a> to send emails (requires composer install for PHPMailer).</div>
    <?php endif; ?>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Compose campaign</h2></div>
        <form method="post" action="/compose" id="compose-form">
            <input type="hidden" name="action" value="send">
            <div class="p-6 space-y-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Subject *</label>
                        <input type="text" name="subject" id="compose-subject" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" placeholder="Email subject" value="<?= h($_POST['subject'] ?? '') ?>">
                    </div>
                    <div class="relative" id="load-template-wrap">
                        <button type="button" id="load-template-btn" class="px-4 py-2.5 rounded-xl border-2 border-[#02396E] text-[#02396E] font-bold text-sm hover:bg-[#02396E] hover:text-white">Load template</button>
                        <div id="load-template-dropdown" class="hidden absolute right-0 top-full mt-1 w-56 rounded-xl border border-slate-200 bg-white shadow-lg py-1 z-20">
                            <div class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">Saved templates</div>
                            <div id="load-template-list" class="max-h-64 overflow-y-auto"></div>
                            <div id="load-template-empty" class="hidden px-4 py-3 text-sm text-slate-500">No templates yet. Save a design with a template name in Design.</div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <label class="block text-sm font-bold text-slate-700">Body *</label>
                        <div class="flex rounded-lg border border-slate-200 p-0.5 bg-slate-50">
                            <button type="button" id="body-mode-visual" class="px-3 py-1.5 text-sm font-medium rounded-md bg-[#02396E] text-white">Visual</button>
                            <button type="button" id="body-mode-html" class="px-3 py-1.5 text-sm font-medium rounded-md text-slate-600 hover:bg-slate-200">HTML</button>
                        </div>
                    </div>
                    <div id="compose-body-wysiwyg-wrap" class="rounded-xl border border-slate-200 overflow-hidden bg-white">
                        <div class="border-b border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase">Header (fixed)</div>
                        <div id="compose-header-preview" class="min-h-[40px] p-0 m-0"></div>
                        <div class="border-y border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase">Your content</div>
                        <div class="max-w-[600px] w-full mx-auto">
                            <div id="compose-body-visual-wrap">
                                <div id="compose-body-outline-wrap" class="p-2">
                                    <div id="compose-body-editor" class="min-h-[280px] text-slate-800" style="min-height:280px"></div>
                                </div>
                            </div>
                            <div id="compose-body-html-wrap" class="hidden p-2 bg-white">
                                <textarea name="body" id="compose-body" rows="12" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 font-mono text-sm" aria-required="true"><?= h($_POST['body'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="border-t border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase">Footer (fixed)</div>
                        <div id="compose-footer-preview" class="min-h-[40px] p-0 m-0"></div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Recipients</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 p-3 rounded-xl border-2 border-slate-200 hover:border-[#02396E] hover:bg-blue-50/30 cursor-pointer">
                            <input type="radio" name="recipient_filter" value="all" checked class="text-[#02396E]">
                            <span>All contacts — <?= $contactsCount ?> total</span>
                        </label>
                        <?php if (!empty($composeGroups)): ?>
                        <div class="p-3 rounded-xl border-2 border-slate-200">
                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                <input type="radio" name="recipient_filter" value="groups" class="text-[#02396E]">
                                <span class="font-medium">Select groups:</span>
                            </label>
                            <div class="flex flex-wrap gap-3 pl-6">
                                <?php foreach ($composeGroups as $cg): ?>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="recipient_groups[]" value="<?= (int)$cg['id'] ?>" class="rounded border-slate-300 text-[#02396E] recipient-group-cb">
                                    <span class="text-sm"><?= h($cg['name']) ?> (<?= (int)$cg['cnt'] ?>)</span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1" for="compose-sender">Sender (who sends this campaign)</label>
                    <select id="compose-sender" name="compose_sender" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-800 font-bold focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                        <option value="all" class="font-bold">All active senders (rotate)</option>
                        <?php foreach ($composeSenders as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" class="font-bold"><?= h($s['name']) ?> (<?= h($s['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="rotate_senders" id="rotate_senders" value="1" checked class="rounded border-slate-300 text-[#02396E]">
                    <label for="rotate_senders" class="text-sm font-medium text-slate-700">Rotate sender accounts</label>
                </div>
            </div>
            <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Send campaign</button>
                <a href="<?= url('index') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300">Cancel</a>
            </div>
        </form>
    </div>
    <script>
        var composeDesignHeader = <?= json_encode($composeHeader) ?>;
        var composeDesignFooter = <?= json_encode($composeFooter) ?>;
        var composeDesignFooterBg = <?= json_encode($composeFooterBg) ?>;
        var composeBlockTextColor = <?= json_encode($composeBlockTextColor) ?>;
        var composeHeaderLogo = <?= json_encode($composeHeaderLogo) ?>;
        var composeFooterLogo = <?= json_encode($composeFooterLogo) ?>;
        var composeHeaderMode = <?= json_encode($composeHeaderMode) ?>;
        var composeFooterMode = <?= json_encode($composeFooterMode) ?>;
        var composeBodyOutline = <?= json_encode($composeBodyOutline) ?>;
        var logoBaseUrl = <?= json_encode(trackingBaseUrl()) ?>;
    </script>
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
    (function() {
        var wrap = document.getElementById('compose-body-visual-wrap');
        var htmlWrap = document.getElementById('compose-body-html-wrap');
        var ta = document.getElementById('compose-body');
        var visualBtn = document.getElementById('body-mode-visual');
        var htmlBtn = document.getElementById('body-mode-html');
        var form = ta.closest('form');
        var headerPreview = document.getElementById('compose-header-preview');
        var footerPreview = document.getElementById('compose-footer-preview');
        var isVisualMode = true;
        function escapeHtmlAndBreaks(t) {
            if (t == null || t === '') return '';
            var div = document.createElement('div');
            div.textContent = t;
            return div.innerHTML.replace(/\n/g, '<br>');
        }
        function escapeAttr(s) {
            if (s == null || s === '') return '';
            var div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML.replace(/"/g, '&quot;').replace(/\n/g, ' ');
        }
        function normalizeTemplateHtml(html) {
            if (!html) return '';
            return String(html)
                .replace(/<head\b[^>]*>[\s\S]*?<\/head>/gi, '')
                .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '')
                .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '')
                .replace(/<link\b[^>]*>/gi, '')
                .replace(/<\/?(html|body)\b[^>]*>/gi, '')
                .trim();
        }
        function makeAbsoluteUrls(html, baseUrl) {
            if (!html || !baseUrl) return html || '';
            var base = String(baseUrl).replace(/\/$/, '');
            return html
                .replace(/\b(src|href)=(["\'])(?!\/\/|https?:|data:)([^"\']*)\2/gi, function(m, attr, q, url) {
                    var u = url.trim();
                    if (!u) return m;
                    if (u.indexOf('/') === 0) return attr + '=' + q + base + u + q;
                    return attr + '=' + q + base + '/' + u.replace(/^\//, '') + q;
                })
                .replace(/\burl\s*\(\s*(["\']?)(?!\/\/|https?:|data:)([^"\')\s]*)\1\s*\)/gi, function(m, q, url) {
                    var u = url.trim();
                    if (!u) return m;
                    var outQuote = q || '"';
                    if (u.indexOf('/') === 0) return 'url(' + outQuote + base + u + outQuote + ')';
                    return 'url(' + outQuote + base + '/' + u.replace(/^\//, '') + outQuote + ')';
                });
        }
        function buildBlock(logoUrl, text, bg, textColor, mode) {
            mode = mode || 'text_only';
            if (['logo_only', 'text_only', 'logo_and_text'].indexOf(mode) === -1) mode = 'text_only';
            var showLogo = (mode === 'logo_only' || mode === 'logo_and_text') && logoUrl && String(logoUrl).trim();
            var showText = (mode === 'text_only' || mode === 'logo_and_text') && text && String(text).trim();
            if (!showLogo && !showText) return '';
            var altText = (showText ? String(text).trim().split('\n')[0] : 'Logo').substring(0, 100);
            var inner = '';
            if (showLogo) {
                var logoSrc = (logoUrl.indexOf('http') === 0) ? logoUrl : (logoBaseUrl ? logoBaseUrl + '/' + logoUrl.replace(/^\//, '') : '/' + logoUrl.replace(/^\//, ''));
                logoSrc = String(logoSrc).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;');
                var altEsc = escapeAttr(altText);
                var fallbackEsc = escapeHtmlAndBreaks(altText);
                inner += '<span style="display:inline-block; min-height:40px;"><img src="' + logoSrc + '" alt="' + altEsc + '" style="max-width:180px; height:auto; display:block; margin:0 auto 8px;" onerror="this.style.display=\'none\'; var n=this.nextElementSibling; if(n) n.style.display=\'block\';" /><span style="display:none; font-size:15px; font-weight:600;">' + fallbackEsc + '</span></span>';
            }
            if (showText) inner += '<div style="margin:0; font-size:15px; line-height:1.4;">' + text + '</div>';
            return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:collapse; margin:0;"><tr><td align="center" style="padding:0; margin:0; text-align:center;">' + inner + '</td></tr></table>';
        }
        function buildBlockWithAbsoluteUrls(logoUrl, text, bg, textColor, mode) {
            var cleanText = normalizeTemplateHtml(text || '');
            var absText = makeAbsoluteUrls(cleanText, typeof logoBaseUrl !== 'undefined' ? logoBaseUrl : '');
            return buildBlock(logoUrl, absText, bg, textColor, mode);
        }
        if (headerPreview && typeof composeDesignFooterBg !== 'undefined') {
            var headerBlock = buildBlockWithAbsoluteUrls(typeof composeHeaderLogo !== 'undefined' ? composeHeaderLogo : '', typeof composeDesignHeader !== 'undefined' ? composeDesignHeader : '', composeDesignFooterBg, composeBlockTextColor, typeof composeHeaderMode !== 'undefined' ? composeHeaderMode : 'text_only');
            if (headerBlock) headerPreview.innerHTML = headerBlock;
            else headerPreview.innerHTML = '<span class="text-slate-400 text-sm">Load a template to add header</span>';
        }
        if (footerPreview && typeof composeDesignFooterBg !== 'undefined') {
            var footerBlock = buildBlockWithAbsoluteUrls(typeof composeFooterLogo !== 'undefined' ? composeFooterLogo : '', typeof composeDesignFooter !== 'undefined' ? composeDesignFooter : '', composeDesignFooterBg, composeBlockTextColor, typeof composeFooterMode !== 'undefined' ? composeFooterMode : 'text_only');
            if (footerBlock) { footerPreview.innerHTML = footerBlock; }
            else { footerPreview.innerHTML = '<span class="text-slate-400 text-sm">Load a template to add footer</span>'; }
        }
        function refreshDesignPreviews() {
            var hBlock = buildBlockWithAbsoluteUrls(typeof composeHeaderLogo !== 'undefined' ? composeHeaderLogo : '', typeof composeDesignHeader !== 'undefined' ? composeDesignHeader : '', composeDesignFooterBg, composeBlockTextColor, typeof composeHeaderMode !== 'undefined' ? composeHeaderMode : 'text_only');
            if (headerPreview) {
                if (hBlock) headerPreview.innerHTML = hBlock;
                else headerPreview.innerHTML = '<span class="text-slate-400 text-sm">Load a template to add header</span>';
            }
            var fBlock = buildBlockWithAbsoluteUrls(typeof composeFooterLogo !== 'undefined' ? composeFooterLogo : '', typeof composeDesignFooter !== 'undefined' ? composeDesignFooter : '', composeDesignFooterBg, composeBlockTextColor, typeof composeFooterMode !== 'undefined' ? composeFooterMode : 'text_only');
            if (footerPreview) {
                if (fBlock) { footerPreview.innerHTML = fBlock; }
                else { footerPreview.innerHTML = '<span class="text-slate-400 text-sm">Load a template to add footer</span>'; }
            }
        }
        var outlineWrap = document.getElementById('compose-body-outline-wrap');
        var loadTemplateBtn = document.getElementById('load-template-btn');
        var loadTemplateDropdown = document.getElementById('load-template-dropdown');
        var loadTemplateList = document.getElementById('load-template-list');
        var loadTemplateEmpty = document.getElementById('load-template-empty');
        var loadedTemplatesList = [];
        function composeBasePath() {
            var base = (window.location.pathname.indexOf('/compose') !== -1) ? window.location.pathname.replace(/\/compose.*$/, '') : window.location.pathname.replace(/\/[^/]*$/, '');
            if (!base) base = '';
            return base;
        }
        function applyLoadedTemplate(d) {
            if (!d) return;
            composeDesignHeader = d.header_html || '';
            composeDesignFooter = d.footer_html || '';
            composeDesignFooterBg = d.footer_bg_color || '#f1f5f9';
            composeBlockTextColor = d.block_text_color || '#1e293b';
            composeHeaderLogo = d.header_logo_url || '';
            composeFooterLogo = d.footer_logo_url || '';
            composeHeaderMode = d.header_mode || 'text_only';
            composeFooterMode = d.footer_mode || 'text_only';
            composeBodyOutline = '';
            refreshDesignPreviews();
            if (outlineWrap) { outlineWrap.style.border = ''; outlineWrap.style.borderRadius = ''; outlineWrap.style.background = ''; }
        }
        function renderLoadTemplateList() {
            if (!loadTemplateList) return;
            loadTemplateList.innerHTML = '';
            if (loadTemplateEmpty) loadTemplateEmpty.classList.add('hidden');
            if (loadedTemplatesList.length === 0) {
                if (loadTemplateEmpty) loadTemplateEmpty.classList.remove('hidden');
                return;
            }
            loadedTemplatesList.forEach(function(tpl, idx) {
                var row = document.createElement('div');
                row.className = 'flex items-center gap-0.5 px-2 py-1';

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'load-template-option flex-1 text-left px-2 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 rounded-lg hover:bg-slate-50';
                btn.textContent = tpl.name;
                btn.setAttribute('data-idx', String(idx));
                btn.onclick = function() {
                    loadTemplateDropdown.classList.add('hidden');
                    applyLoadedTemplate(loadedTemplatesList[idx]);
                };

                var editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'shrink-0 p-1.5 text-slate-500 rounded-lg hover:bg-slate-100 hover:text-[#02396E]';
                editBtn.setAttribute('aria-label', 'Edit template ' + tpl.name);
                editBtn.setAttribute('title', 'Edit template');
                editBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
                editBtn.onclick = function(e) {
                    e.stopPropagation();
                    var current = loadedTemplatesList[idx];
                    if (!current || !current.id) return;
                    window.location.href = composeBasePath() + '/design?edit_template=' + encodeURIComponent(String(current.id));
                };

                var delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'shrink-0 p-1.5 text-red-600 rounded-lg hover:bg-red-50';
                delBtn.setAttribute('aria-label', 'Delete template ' + tpl.name);
                delBtn.setAttribute('title', 'Delete template');
                delBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
                delBtn.onclick = function(e) {
                    e.stopPropagation();
                    var current = loadedTemplatesList[idx];
                    if (!current || !current.id) return;
                    if (!window.confirm('Delete template "' + current.name + '"?')) return;
                    delBtn.disabled = true;
                    fetch(composeBasePath() + '/api/v1/design/templates/delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: current.id })
                    }).then(function(r) {
                        return r.json().then(function(data) {
                            if (!r.ok) throw new Error((data && data.error) ? data.error : 'Could not delete template.');
                            return data;
                        });
                    }).then(function() {
                        loadedTemplatesList = loadedTemplatesList.filter(function(item) { return item.id !== current.id; });
                        renderLoadTemplateList();
                    }).catch(function(err) {
                        alert(err && err.message ? err.message : 'Could not delete template.');
                        delBtn.disabled = false;
                    });
                };

                row.appendChild(btn);
                row.appendChild(editBtn);
                row.appendChild(delBtn);
                loadTemplateList.appendChild(row);
            });
        }
        if (loadTemplateBtn && loadTemplateDropdown) {
            loadTemplateBtn.onclick = function(e) {
                e.stopPropagation();
                loadTemplateDropdown.classList.toggle('hidden');
                if (!loadTemplateDropdown.classList.contains('hidden') && loadTemplateList) {
                    fetch(composeBasePath() + '/api/v1/design/templates').then(function(r) { return r.json(); }).then(function(data) {
                        loadedTemplatesList = data.templates || [];
                        renderLoadTemplateList();
                    }).catch(function() { if (loadTemplateList) loadTemplateList.innerHTML = ''; if (loadTemplateEmpty) loadTemplateEmpty.classList.remove('hidden'); });
                }
            };
            document.addEventListener('click', function() { loadTemplateDropdown.classList.add('hidden'); });
            loadTemplateDropdown.addEventListener('click', function(e) { e.stopPropagation(); });
        }
        if (outlineWrap) { outlineWrap.style.border = ''; outlineWrap.style.borderRadius = ''; outlineWrap.style.background = ''; }
        var quill = new Quill('#compose-body-editor', { theme: 'snow', modules: { toolbar: [[{header:[1,2,3,false]}], ['bold','italic','underline'], [{list:'ordered'},{list:'bullet'}], ['link'], ['clean']] } });
        window.quill = quill;
        if (ta.value) quill.root.innerHTML = ta.value;
        quill.on('text-change', function() { ta.value = quill.root.innerHTML; });
        function setVisual(v) {
            isVisualMode = !!v;
            if (v) {
                quill.root.innerHTML = ta.value;
                if (wrap) wrap.classList.remove('hidden');
                if (htmlWrap) htmlWrap.classList.add('hidden');
                visualBtn.className = 'px-3 py-1.5 text-sm font-medium rounded-md bg-[#02396E] text-white';
                htmlBtn.className = 'px-3 py-1.5 text-sm font-medium rounded-md text-slate-600 hover:bg-slate-200';
            } else {
                ta.value = quill.root.innerHTML;
                if (wrap) wrap.classList.add('hidden');
                if (htmlWrap) htmlWrap.classList.remove('hidden');
                htmlBtn.className = 'px-3 py-1.5 text-sm font-medium rounded-md bg-[#02396E] text-white';
                visualBtn.className = 'px-3 py-1.5 text-sm font-medium rounded-md text-slate-600 hover:bg-slate-200';
            }
        }
        visualBtn.onclick = function() { setVisual(true); };
        htmlBtn.onclick = function() { ta.value = quill.root.innerHTML; setVisual(false); };
        setVisual(true);
        form.onsubmit = function() {
            var middle = isVisualMode ? quill.root.innerHTML : ta.value;
            if (!middle || middle.replace(/<[^>]*>|&nbsp;/g, '').trim() === '') {
                alert('Please enter a message in the body.');
                return false;
            }
            var headerBlock = buildBlockWithAbsoluteUrls(composeHeaderLogo || '', composeDesignHeader || '', composeDesignFooterBg, composeBlockTextColor, typeof composeHeaderMode !== 'undefined' ? composeHeaderMode : 'text_only');
            var footerBlock = buildBlockWithAbsoluteUrls(composeFooterLogo || '', composeDesignFooter || '', composeDesignFooterBg, composeBlockTextColor, typeof composeFooterMode !== 'undefined' ? composeFooterMode : 'text_only');
            var bodyWrapped = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:600px; margin:0 auto; border-collapse:collapse;"><tr><td style="padding:16px 20px; font-family:Arial, Helvetica, sans-serif;">' + middle + '</td></tr></table>';
            ta.value = headerBlock + bodyWrapped + footerBlock;
            return true;
        };
    })();
    </script>
    <?php endif; ?>

    <?php
    // ---------- Design (header & footer) ----------
    if ($page === 'design'):
        $designRow = $pdo->query('SELECT header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color FROM email_design WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
        $editingTemplateId = (int)($_GET['edit_template'] ?? 0);
        $editingTemplateName = '';
        if ($editingTemplateId > 0) {
            $stmt = $pdo->prepare('SELECT id, name, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color FROM email_design_templates WHERE id = ?');
            $stmt->execute([$editingTemplateId]);
            $templateRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($templateRow) {
                $editingTemplateName = (string)($templateRow['name'] ?? '');
                $designRow = $templateRow;
            } else {
                $editingTemplateId = 0;
            }
        }
        $designHeader = $designRow ? $designRow['header_html'] : '';
        $designFooter = $designRow ? $designRow['footer_html'] : '';
        $designHeaderFooterCombined = ($designHeader === $designFooter)
            ? $designHeader
            : $designHeader . "\n<!-- FOOTER -->\n" . $designFooter;
        $designFooterBg = $designRow && $designRow['footer_bg_color'] !== '' ? $designRow['footer_bg_color'] : '#f1f5f9';
        $designTextColor = $designRow && !empty($designRow['block_text_color']) ? $designRow['block_text_color'] : '#1e293b';
        $designHeaderLogo = $designRow && isset($designRow['header_logo_url']) ? $designRow['header_logo_url'] : '';
        $designFooterLogo = $designRow && isset($designRow['footer_logo_url']) ? $designRow['footer_logo_url'] : '';
        $designBodyOutline = $designRow && isset($designRow['body_outline_color']) ? $designRow['body_outline_color'] : '';
        $designHeaderMode = $designRow && in_array($designRow['header_mode'] ?? '', ['logo_only', 'text_only', 'logo_and_text'], true) ? $designRow['header_mode'] : 'text_only';
        $designFooterMode = $designRow && in_array($designRow['footer_mode'] ?? '', ['logo_only', 'text_only', 'logo_and_text'], true) ? $designRow['footer_mode'] : 'text_only';
    ?>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Email design (header &amp; footer)</h2></div>
        <p class="p-4 text-slate-600 text-sm border-b border-slate-100">Set header and footer using HTML code. Content is centered in the email.</p>
        <form method="post" action="<?= url('design') ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save-design">
            <input type="hidden" name="template_edit_id" value="<?= (int)$editingTemplateId ?>">
            <input type="hidden" name="header_logo_url" value="<?= h($designHeaderLogo) ?>">
            <input type="hidden" name="footer_logo_url" value="<?= h($designFooterLogo) ?>">
            <input type="hidden" name="header_mode" value="text_only">
            <input type="hidden" name="footer_mode" value="text_only">
            <input type="hidden" name="footer_bg_color" value="<?= h($designFooterBg) ?>">
            <input type="hidden" name="block_text_color" value="<?= h($designTextColor) ?>">
            <input type="hidden" name="body_outline_color" value="<?= h($designBodyOutline) ?>">
            <div class="p-6 space-y-8">
                <?php if ($editingTemplateId > 0): ?>
                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Editing template <strong><?= h($editingTemplateName) ?></strong>.
                </div>
                <?php endif; ?>
                <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-5">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Template name</label>
                    <p class="text-slate-500 text-xs mb-2">Give this design a name to show in the Compose &quot;Load template&quot; dropdown. Leave empty to only update the current design.</p>
                    <input type="text" name="template_name" maxlength="255" placeholder="e.g. Newsletter, Welcome email" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#02396E]" value="<?= h($editingTemplateName) ?>">
                </div>
                <!-- Header & Footer (single code block, split by delimiter) -->
                <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-5">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-3">Header &amp; Footer</h3>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Header &amp; Footer (HTML code)</label>
                    <p class="text-slate-500 text-xs mb-2">Paste your HTML in one block. To use different content for header and footer, put <code class="bg-slate-100 px-1 rounded">&lt;!-- FOOTER --&gt;</code> on its own line: everything above = header, everything below = footer. If you don’t use it, the same code is used for both. Use email-safe HTML with inline styles.</p>
                    <textarea name="header_footer_html" rows="14" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-[#02396E]" placeholder="Header HTML here...&#10;&#10;&lt;!-- FOOTER --&gt;&#10;&#10;Footer HTML here..."><?= h($designHeaderFooterCombined) ?></textarea>
                </div>
            </div>
            <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Save design</button>
                <a href="<?= url('compose') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300">Compose</a>
            </div>
        </form>
    </div>
    <script>
    (function() {
        // Design form: only header/footer text inputs; hidden fields preserve logo, mode, colors.
    })();
    </script>
    <?php endif; ?>

    <?php
    // ---------- API (for external websites) ----------
    if ($page === 'api'):
        $apiKeys = $pdo->query('SELECT id, name, created_at FROM api_keys ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        $newKey = $_SESSION['new_api_key'] ?? null;
        $newKeyName = $_SESSION['new_api_key_name'] ?? '';
        if ($newKey !== null) {
            unset($_SESSION['new_api_key'], $_SESSION['new_api_key_name']);
        }
        $apiBaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . (dirname($_SERVER['SCRIPT_NAME'] ?? '') !== '/' ? rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') : '');
    ?>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">API for external websites</h2></div>
        <p class="p-4 text-slate-600 text-sm border-b border-slate-100">Other sites can send email campaigns to this system using an API key. They send subject, body, and a list of recipient emails; campaigns are sent using your senders and (optionally) your header/footer design.</p>
        <?php if ($newKey): ?>
        <div class="mx-4 mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
            <p class="font-bold text-emerald-800 mb-1">API key created (copy it now — it won’t be shown again):</p>
            <div class="flex flex-wrap items-center gap-2">
                <code id="new-api-key" class="block flex-1 min-w-0 p-3 bg-white border border-emerald-300 rounded-lg text-sm break-all"><?= h($newKey) ?></code>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-api-key').textContent); this.textContent='Copied!';" class="px-4 py-2 bg-[#02396E] text-white rounded-lg font-medium text-sm">Copy</button>
            </div>
            <p class="text-emerald-700 text-xs mt-2">Use in requests: <code>X-API-Key: &lt;key&gt;</code> or <code>Authorization: Bearer &lt;key&gt;</code></p>
        </div>
        <?php endif; ?>
        <div class="p-6">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-3">Create API key</h3>
            <form method="post" action="<?= url('api') ?>" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="action" value="api-key-create">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Name (e.g. website or company)</label>
                    <input type="text" name="api_key_name" required maxlength="255" placeholder="e.g. Acme Corp Website" class="rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E] w-64">
                </div>
                <button type="submit" class="px-4 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Create key</button>
            </form>
        </div>
        <div class="border-t border-slate-200 p-6">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-3">Your API keys</h3>
            <?php if (empty($apiKeys)): ?>
            <p class="text-slate-500">No API keys yet. Create one above and give it to the external site.</p>
            <?php else: ?>
            <table class="w-full text-left">
                <thead class="bg-slate-50"><tr><th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase">Name</th><th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase">Created</th><th class="px-4 py-2 text-right text-xs font-bold text-slate-600 uppercase">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($apiKeys as $k): ?>
                <tr class="border-t border-slate-100"><td class="px-4 py-3 font-medium"><?= h($k['name']) ?></td><td class="px-4 py-3 text-slate-500 text-sm"><?= h($k['created_at']) ?></td><td class="px-4 py-3 text-right"><form method="post" action="<?= url('api') ?>" class="inline" onsubmit="return confirm('Delete this API key? The external site will no longer be able to send campaigns.');"><input type="hidden" name="action" value="api-key-delete"><input type="hidden" name="id" value="<?= (int)$k['id'] ?>"><button type="submit" class="text-red-600 hover:underline text-sm">Delete</button></form></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <div class="border-t border-slate-200 p-6 bg-slate-50">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-2">Endpoint</h3>
            <p class="text-slate-600 text-sm mb-2"><strong>POST</strong> <code class="bg-white px-2 py-1 rounded border border-slate-200"><?= h($apiBaseUrl) ?>/api/v1/send</code></p>
            <p class="text-slate-600 text-sm mb-2">Headers: <code>Content-Type: application/json</code>, <code>X-API-Key: &lt;your-key&gt;</code></p>
            <p class="text-slate-600 text-sm mb-2">Body (JSON):</p>
            <pre class="bg-white p-4 rounded-xl border border-slate-200 text-xs overflow-x-auto">{
  "subject": "Your email subject",
  "body": "&lt;p&gt;HTML content or plain text&lt;/p&gt;",
  "recipients": ["email1@example.com", "email2@example.com"],
  "use_design": true
}</pre>
            <p class="text-slate-500 text-xs mt-2"><code>use_design</code> (optional): if <code>true</code>, wraps the body with your header and footer from Design.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // ---------- Senders list ----------
    if ($page === 'senders'):
        $accounts = $pdo->query('SELECT * FROM sender_accounts ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm md:text-base font-semibold text-white uppercase">Sender accounts</h2>
            <a href="<?= url('sender-edit') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition">Add sender</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Name</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Email</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Host:Port</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Status</th>
                        <th class="px-4 md:px-8 py-3 text-right text-xs font-black text-slate-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $a): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 font-semibold"><?= h($a['name']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($a['email']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($a['host']) ?>:<?= h($a['port']) ?></td>
                        <td class="px-4 md:px-8 py-4"><span class="px-2 py-1 rounded text-xs font-bold <?= $a['is_active'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' ?>"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td class="px-4 md:px-8 py-4 text-right">
                            <a href="<?= url('sender-edit', ['id' => $a['id']]) ?>" class="p-2 text-[#02396E] hover:bg-blue-50 rounded" title="Edit">Edit</a>
                            <form method="post" action="<?= url('senders') ?>" class="inline" onsubmit="return confirm('Delete this sender?');">
                                <input type="hidden" name="action" value="sender-delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($accounts)): ?>
                    <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500">No senders. <a href="<?= url('sender-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // ---------- Sender form (add/edit) ----------
    if ($page === 'sender-edit') {
        $senderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $account = null;
        if ($senderId) {
            $st = $pdo->prepare("SELECT * FROM sender_accounts WHERE id = ?");
            $st->execute([$senderId]);
            $account = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    ?>
    <div class="mb-4"><a href="<?= url('senders') ?>" class="text-[#02396E] hover:underline text-sm font-medium">← Sender accounts</a></div>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase"><?= $account ? 'Edit' : 'Add' ?> sender</h2></div>
        <form method="post" action="<?= url('senders') ?>">
            <input type="hidden" name="action" value="sender-save">
            <?php if ($account): ?><input type="hidden" name="id" value="<?= (int)$account['id'] ?>"><?php endif; ?>
            <div class="p-6 space-y-4">
                <div><label class="block text-sm font-bold text-slate-700 mb-1">Name</label><input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($account['name'] ?? '') ?>" placeholder="e.g. Gmail Primary"></div>
                <div><label class="block text-sm font-bold text-slate-700 mb-1">Email</label><input type="email" name="email" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($account['email'] ?? '') ?>"></div>
                <div><label class="block text-sm font-bold text-slate-700 mb-1">Password <?= $account ? '(leave blank to keep)' : '' ?></label><input type="password" name="password" class="w-full rounded-xl border border-slate-200 px-4 py-2.5" placeholder="App password" <?= $account ? '' : 'required' ?>></div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1" for="sender-host">Host</label>
                    <input id="sender-host" type="text" name="host" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]" value="<?= h($account['host'] ?? 'smtp.gmail.com') ?>" placeholder="smtp.gmail.com">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1" for="sender-port">Port</label>
                    <input id="sender-port" type="number" name="port" min="1" max="65535" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]" value="<?= (int)($account['port'] ?? 587) ?>" placeholder="587">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1" for="sender-encryption">Encryption</label>
                    <select id="sender-encryption" name="encryption" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                        <option value="" <?= ($account['encryption'] ?? '') === '' ? 'selected' : '' ?>>None</option>
                        <option value="tls" <?= ($account['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (recommended for port 587)</option>
                        <option value="ssl" <?= ($account['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (e.g. port 465)</option>
                    </select>
                </div>
                <div class="flex items-center gap-2"><input type="checkbox" name="is_active" id="is_active" value="1" <?= ($account['is_active'] ?? 1) ? 'checked' : '' ?> class="rounded text-[#02396E]"><label for="is_active" class="text-sm font-medium text-slate-700">Active</label></div>
            </div>
            <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Save</button>
                <a href="<?= url('senders') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</a>
            </div>
        </form>
    </div>
    <?php } ?>

    <?php
    // ---------- Contacts list ----------
    if ($page === 'contacts'):
        $contacts = $pdo->query("SELECT c.*, (SELECT GROUP_CONCAT(g.name) FROM contact_group_members m JOIN contact_groups g ON g.id = m.group_id WHERE m.contact_id = c.id) as group_names FROM marketing_contacts c ORDER BY c.email")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm md:text-base font-semibold text-white uppercase">Contacts (<?= count($contacts) ?>)</h2>
            <div class="flex gap-2">
                <a href="<?= url('contact-edit') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition">Add contact</a>
                <a href="<?= url('contacts-import') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition">Import CSV</a>
                <a href="<?= url('groups') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-white border-2 border-white/50 hover:bg-white/10 transition">Groups</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Email</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Company</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Groups</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Notes</th>
                        <th class="px-4 md:px-8 py-3 text-right text-xs font-black text-slate-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $c): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 font-semibold"><?= h($c['email']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($c['company_name'] ?? '—') ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600 text-sm"><?= h($c['group_names'] ?? '—') ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600 text-sm max-w-xs truncate"><?= h($c['notes'] ?? '—') ?></td>
                        <td class="px-4 md:px-8 py-4 text-right">
                            <a href="<?= url('contact-edit', ['id' => $c['id']]) ?>" class="p-2 text-[#02396E] hover:bg-blue-50 rounded">Edit</a>
                            <form method="post" action="<?= url('contacts') ?>" class="inline" onsubmit="return confirm('Remove this contact?');">
                                <input type="hidden" name="action" value="contact-delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contacts)): ?>
                    <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500">No contacts. <a href="<?= url('contact-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a> or <a href="<?= url('contacts-import') ?>" class="text-[#ff8904] font-bold hover:underline">import CSV</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // ---------- Groups list ----------
    if ($page === 'groups'):
        $groups = $pdo->query('SELECT g.*, (SELECT COUNT(*) FROM contact_group_members WHERE group_id = g.id) as member_count FROM contact_groups g ORDER BY g.name')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm md:text-base font-semibold text-white uppercase">Contact groups</h2>
            <a href="<?= url('group-edit') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition">Add group</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Name</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Contacts</th>
                        <th class="px-4 md:px-8 py-3 text-right text-xs font-black text-slate-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $g): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 font-semibold"><?= h($g['name']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600"><?= (int)($g['member_count'] ?? 0) ?></td>
                        <td class="px-4 md:px-8 py-4 text-right">
                            <a href="<?= url('group-edit', ['id' => $g['id']]) ?>" class="p-2 text-[#02396E] hover:bg-blue-50 rounded">Edit</a>
                            <form method="post" action="<?= url('groups') ?>" class="inline" onsubmit="return confirm('Delete this group? Contacts will not be removed.');">
                                <input type="hidden" name="action" value="group-delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($groups)): ?>
                    <tr><td colspan="3" class="px-4 md:px-8 py-12 text-center text-slate-500">No groups. <a href="<?= url('group-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a>, then assign contacts to groups when adding or editing contacts.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // ---------- Group form (add/edit) ----------
    if ($page === 'group-edit') {
        $groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $group = null;
        if ($groupId) {
            $st = $pdo->prepare('SELECT * FROM contact_groups WHERE id = ?');
            $st->execute([$groupId]);
            $group = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    ?>
    <div class="mb-4"><a href="<?= url('groups') ?>" class="text-[#02396E] hover:underline text-sm font-medium">← Contact groups</a></div>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase"><?= $group ? 'Edit' : 'Add' ?> group</h2></div>
        <form method="post" action="<?= url('groups') ?>">
            <input type="hidden" name="action" value="group-save">
            <?php if ($group): ?><input type="hidden" name="id" value="<?= (int)$group['id'] ?>"><?php endif; ?>
            <div class="p-6">
                <label class="block text-sm font-bold text-slate-700 mb-1">Group name *</label>
                <input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($group['name'] ?? '') ?>" placeholder="e.g. Newsletter, VIP">
            </div>
            <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Save</button>
                <a href="<?= url('groups') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</a>
            </div>
        </form>
    </div>
    <?php } ?>

    <?php
    // ---------- Contact form ----------
    if ($page === 'contact-edit'):
        $contactId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $contact = null;
        $contactGroupIds = [];
        if ($contactId) {
            $st = $pdo->prepare('SELECT * FROM marketing_contacts WHERE id=?');
            $st->execute([$contactId]);
            $contact = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($contact) {
                $st2 = $pdo->prepare('SELECT group_id FROM contact_group_members WHERE contact_id=?');
                $st2->execute([$contactId]);
                $contactGroupIds = array_column($st2->fetchAll(PDO::FETCH_ASSOC), 'group_id');
            }
        }
        $allGroups = $pdo->query('SELECT id, name FROM contact_groups ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="mb-4"><a href="<?= url('contacts') ?>" class="text-[#02396E] hover:underline text-sm font-medium">← Contacts</a></div>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase"><?= $contact ? 'Edit' : 'Add' ?> contact</h2></div>
        <form method="post" action="<?= url('contacts') ?>">
            <input type="hidden" name="action" value="contact-save">
            <?php if ($contact): ?><input type="hidden" name="id" value="<?= (int)$contact['id'] ?>"><?php endif; ?>
            <div class="p-6 space-y-4">
                <div><label class="block text-sm font-bold text-slate-700 mb-1">Email *</label><input type="email" name="email" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($contact['email'] ?? '') ?>" placeholder="hr@company.com"></div>
                <div><label class="block text-sm font-bold text-slate-700 mb-1">Company name</label><input type="text" name="company_name" class="w-full rounded-xl border border-slate-200 px-4 py-2.5" value="<?= h($contact['company_name'] ?? '') ?>" placeholder="Acme Corp"></div>
                <div><label class="block text-sm font-bold text-slate-700 mb-1">Notes</label><textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-200 px-4 py-2.5"><?= h($contact['notes'] ?? '') ?></textarea></div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Groups</label>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($allGroups as $gr): ?>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="group_ids[]" value="<?= (int)$gr['id'] ?>" class="rounded border-slate-300 text-[#02396E]" <?= in_array((int)$gr['id'], $contactGroupIds, true) ? 'checked' : '' ?>>
                            <span class="text-sm text-slate-700"><?= h($gr['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                        <?php if (empty($allGroups)): ?><span class="text-slate-500 text-sm">No groups yet. <a href="<?= url('group-edit') ?>" class="text-[#02396E] hover:underline">Create one</a></span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Save</button>
                <a href="<?= url('contacts') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php
    // ---------- Import CSV ----------
    if ($page === 'contacts-import'):
    ?>
    <div class="mb-4"><a href="<?= url('contacts') ?>" class="text-[#02396E] hover:underline text-sm font-medium">← Contacts</a></div>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Import CSV</h2></div>
        <form method="post" enctype="multipart/form-data" action="<?= url('contacts-import') ?>">
            <input type="hidden" name="action" value="contacts-import-csv">
            <div class="p-6">
                <p class="text-slate-600 mb-4">Upload a CSV with columns: <strong>email</strong> (required), optional <strong>company</strong> or <strong>company_name</strong>. First row can be header.</p>
                <input type="file" name="file" accept=".csv,.txt" required class="w-full rounded-xl border border-slate-200 px-4 py-2">
            </div>
            <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl">Import</button>
                <a href="<?= url('contacts') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php
    // ---------- Logs ----------
    if ($page === 'logs'):
        $statusFilter = $_GET['status'] ?? '';
        $campaignFilter = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : 0;
        $openedFilter = $_GET['opened'] ?? '';
        $q = 'SELECT l.*, c.subject as campaign_subject FROM email_logs l LEFT JOIN email_campaigns c ON c.id = l.email_campaign_id WHERE 1=1';
        $params = [];
        if ($statusFilter !== '') { $q .= ' AND l.status = ?'; $params[] = $statusFilter; }
        if ($campaignFilter > 0) { $q .= ' AND l.email_campaign_id = ?'; $params[] = $campaignFilter; }
        if ($openedFilter === '1') { $q .= ' AND l.opened_at IS NOT NULL'; }
        if ($openedFilter === '0') { $q .= ' AND l.opened_at IS NULL'; }
        $q .= ' ORDER BY l.id DESC LIMIT 100';
        $stmt = $pdo->prepare($q);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $campaigns = $pdo->query('SELECT id, subject FROM email_campaigns ORDER BY id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <form method="get" action="<?= url('logs') ?>" class="flex flex-wrap gap-2 mb-4">
        <input type="hidden" name="page" value="logs">
        <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent</option>
            <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
        </select>
        <select name="campaign_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All campaigns</option>
            <?php foreach ($campaigns as $co): ?><option value="<?= (int)$co['id'] ?>" <?= $campaignFilter === (int)$co['id'] ? 'selected' : '' ?>><?= h(mb_substr($co['subject'], 0, 30)) ?></option><?php endforeach; ?>
        </select>
        <select name="opened" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All</option>
            <option value="1" <?= $openedFilter === '1' ? 'selected' : '' ?>>Opened</option>
            <option value="0" <?= $openedFilter === '0' ? 'selected' : '' ?>>Not opened</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-[#02396E] text-white text-sm font-bold rounded-xl">Filter</button>
    </form>
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Logs (<?= count($logs) ?>)</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Recipient</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Campaign</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Status</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Sent at</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Opened</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-6 py-3 font-medium text-slate-900"><?= h($log['recipient_email']) ?></td>
                        <td class="px-4 md:px-6 py-3 text-slate-600 text-sm"><?= h(mb_substr($log['campaign_subject'] ?? '—', 0, 25)) ?></td>
                        <td class="px-4 md:px-6 py-3"><span class="px-2 py-0.5 rounded text-xs font-semibold <?= $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>"><?= h($log['status']) ?></span></td>
                        <td class="px-4 md:px-6 py-3 text-slate-500 text-sm"><?= h($log['sent_at'] ?? '—') ?></td>
                        <td class="px-4 md:px-6 py-3">
                            <?php if (!empty($log['opened_at'])): ?>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Opened</span>
                                <span class="text-slate-500 text-xs block mt-0.5"><?= h($log['opened_at']) ?></span>
                            <?php else: ?>
                                <span class="text-slate-400 text-sm">—</span>
                                <?php if (!empty($log['open_tracking_token']) && $log['status'] === 'sent'): ?>
                                    <a href="<?= h(trackingBaseUrl() . '/track/email-open/' . $log['open_tracking_token']) ?>" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline block mt-0.5">Test open</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="px-4 md:px-6 py-8 text-center text-slate-500">No logs.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

        </div>
    </main>
</div>
</body>
</html>
