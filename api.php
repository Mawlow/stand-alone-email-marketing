<?php
/**
 * API for external websites: send email campaigns (subject, body, recipients) via POST /api/v1/send
 * Auth: X-API-Key or Authorization: Bearer <key>
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Bootstrap (same as index.php)
$envPath = __DIR__ . '/.env';
if (!is_file($envPath) && is_file(__DIR__ . '/.env.example')) {
    copy(__DIR__ . '/.env.example', $envPath);
}
$config = [];
if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $key = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) $value = substr($value, 1, -1);
        $parts = explode('_', $key);
        if (count($parts) >= 2 && $parts[0] === 'DB' && $parts[1] === 'MYSQL') {
            $prefix = 'db_mysql';
            $last = strtolower($parts[count($parts) - 1]);
            if (!isset($config[$prefix])) $config[$prefix] = [];
            $config[$prefix][$last] = $value;
        } else {
            $config[strtolower($key)] = $value;
        }
    }
}
$mysql = $config['db_mysql'] ?? null;
if (empty($mysql) || empty($mysql['database'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfiguration. Set DB_MYSQL_* in .env.']);
    exit;
}
$dsn = 'mysql:host=' . ($mysql['host'] ?? '127.0.0.1') . ';port=' . ($mysql['port'] ?? 3306) . ';dbname=' . $mysql['database'] . ';charset=' . ($mysql['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $mysql['username'] ?? 'root', $mysql['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function trackingBaseUrlApi(array $config): string {
    $base = trim((string)($config['tracking_base_url'] ?? ''));
    if ($base !== '') {
        if (!preg_match('#^https?://#i', $base)) $base = 'https://' . $base;
        return rtrim($base, '/');
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $host;
}

function injectTrackingPixelApi(string $body, string $baseUrl, string $token): string {
    $url = $baseUrl . '/track/email-open/' . $token;
    $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $img = '<img src="' . $urlEsc . '" width="1" height="1" alt="" border="0" style="width:1px;height:1px;border:0;display:block;" />';
    $link = '<p style="margin:8px 0 0;font-size:11px;color:#94a3b8;"><a href="' . $urlEsc . '" style="color:#94a3b8;text-decoration:underline;">View in browser</a></p>';
    $block = $img . "\n" . $link;
    if (stripos($body, '</body>') !== false) return preg_replace('/(<\/body>)/i', $block . "\n" . '$1', $body, 1);
    if (stripos($body, '</html>') !== false) return preg_replace('/(<\/html>)/i', $block . "\n" . '$1', $body, 1);
    return $body . "\n" . $block;
}

function normalizeEmailBlockHtmlApi(string $html): string {
    $html = preg_replace('#<head\b[^>]*>.*?</head>#is', '', $html) ?? $html;
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
    $html = preg_replace('#<link\b[^>]*>#is', '', $html) ?? $html;
    $html = preg_replace('#</?(html|body)\b[^>]*>#is', '', $html) ?? $html;
    return trim($html);
}

/** Split combined header+footer by <!-- FOOTER --> when both columns are identical (for template display). */
function splitHeaderFooterApi(string $header, string $footer): array {
    if ($header !== $footer || trim($header) === '') return [$header, $footer];
    if (!preg_match('#\s*<!--\s*FOOTER\s*-->\s*#is', $header, $m, PREG_OFFSET_CAPTURE)) return [$header, $footer];
    $pos = $m[0][1];
    $before = trim(substr($header, 0, $pos));
    $after = trim(substr($header, $pos + strlen($m[0][0])));
    return [$before, $after];
}

/** Load a template by id or name; return [header_html, footer_html] (normalized, split if same). */
function loadTemplateForApi(PDO $pdo, ?int $templateId, ?string $templateName): ?array {
    if ($templateId !== null && $templateId > 0) {
        $stmt = $pdo->prepare('SELECT header_html, footer_html FROM email_design_templates WHERE id = ?');
        $stmt->execute([$templateId]);
    } elseif ($templateName !== null && trim($templateName) !== '') {
        $stmt = $pdo->prepare('SELECT header_html, footer_html FROM email_design_templates WHERE name = ?');
        $stmt->execute([trim($templateName)]);
    } else {
        return null;
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $header = normalizeEmailBlockHtmlApi((string)($row['header_html'] ?? ''));
    $footer = normalizeEmailBlockHtmlApi((string)($row['footer_html'] ?? ''));
    list($header, $footer) = splitHeaderFooterApi($header, $footer);
    return [$header, $footer];
}

/** Build header/footer block HTML from design row (for API use_design). Respects header_mode / footer_mode. */
function buildDesignBlockApi(array $row, string $type, array $config): string {
    $text = $type === 'header' ? ($row['header_html'] ?? '') : ($row['footer_html'] ?? '');
    $text = normalizeEmailBlockHtmlApi((string) $text);
    $logoUrl = $type === 'header' ? ($row['header_logo_url'] ?? '') : ($row['footer_logo_url'] ?? '');
    $mode = $type === 'header' ? ($row['header_mode'] ?? 'text_only') : ($row['footer_mode'] ?? 'text_only');
    if (!in_array($mode, ['logo_only', 'text_only', 'logo_and_text'], true)) $mode = 'text_only';
    $base = trackingBaseUrlApi($config);
    $showLogo = ($mode === 'logo_only' || $mode === 'logo_and_text') && $logoUrl !== '';
    $showText = ($mode === 'text_only' || $mode === 'logo_and_text') && trim($text) !== '';
    if (!$showLogo && !$showText) return '';
    $inner = '';
    if ($showLogo) {
        $src = (strpos($logoUrl, 'http') === 0) ? $logoUrl : ($base . '/' . $logoUrl);
        $inner .= '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($showText ? trim(explode("\n", $text)[0]) : 'Logo', ENT_QUOTES, 'UTF-8') . '" style="max-width:180px; height:auto; display:block; margin:0 auto 8px;" />';
    }
    if ($showText) {
        $inner .= '<div style="margin:0; font-size:15px; line-height:1.4;">' . $text . '</div>';
    }
    return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:collapse; margin:0;"><tr><td align="center" style="padding:0; margin:0; text-align:center;">' . $inner . '</td></tr></table>';
}

// API key from header
$apiKey = null;
if (!empty($_SERVER['HTTP_X_API_KEY'])) {
    $apiKey = trim($_SERVER['HTTP_X_API_KEY']);
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/^\s*Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
    $apiKey = trim($m[1]);
}
if ($apiKey === null || $apiKey === '') {
    http_response_code(401);
    echo json_encode(['error' => 'Missing API key. Send X-API-Key or Authorization: Bearer <key>.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, default_template_id, default_sender_ids FROM api_keys WHERE api_key = ?');
$stmt->execute([$apiKey]);
$apiKeyRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$apiKeyRow) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key.']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body.']);
    exit;
}

$subject = trim((string)($input['subject'] ?? ''));
$body = trim((string)($input['body'] ?? ''));
$useDesign = !empty($input['use_design']);
$templateId = isset($input['template_id']) ? (int)$input['template_id'] : null;
$templateName = isset($input['template_name']) ? trim((string)$input['template_name']) : null;
$recipientsInput = $input['recipients'] ?? [];
$senderIdInput = $input['sender_id'] ?? null;
$senderIdsInput = $input['sender_ids'] ?? null;

// Apply API-key defaults when request does not specify template or senders
if ($templateId <= 0 && ($templateName === null || $templateName === '')) {
    $keyTemplateId = isset($apiKeyRow['default_template_id']) ? (int)$apiKeyRow['default_template_id'] : 0;
    if ($keyTemplateId > 0) {
        $templateId = $keyTemplateId;
    } else {
        // If no explicit default template and caller did not request use_design,
        // try to auto-pick a template whose name matches the API key name
        if (!$useDesign) {
            $keyName = trim((string)($apiKeyRow['name'] ?? ''));
            $matchTerm = $keyName !== '' ? strtolower(trim(preg_replace('/\.(com|net|ph|org|io|co\.uk)$/i', '', $keyName))) : '';
            if ($matchTerm !== '' && strlen($matchTerm) >= 2) {
                $pattern = '%' . $matchTerm . '%';
                $stmt = $pdo->prepare('SELECT id FROM email_design_templates WHERE LOWER(name) LIKE ? ORDER BY id LIMIT 1');
                $stmt->execute([$pattern]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['id'])) {
                    $templateId = (int)$row['id'];
                }
            }
        }
    }
}
if ($senderIdsInput === null && $senderIdInput === null) {
    $keySenderIds = isset($apiKeyRow['default_sender_ids']) ? trim((string)$apiKeyRow['default_sender_ids']) : '';
    if ($keySenderIds !== '') {
        $senderIdsInput = array_map('intval', array_filter(explode(',', str_replace(' ', '', $keySenderIds)), fn($id) => $id > 0));
        if (empty($senderIdsInput)) {
            $senderIdsInput = null;
        }
    }
}

if ($subject === '' || $body === '') {
    http_response_code(400);
    echo json_encode(['error' => 'subject and body are required.']);
    exit;
}

$recipients = [];
foreach ((array)$recipientsInput as $r) {
    if (is_string($r)) {
        $email = trim($r);
    } elseif (is_array($r) && !empty($r['email'])) {
        $email = trim((string)$r['email']);
    } else {
        continue;
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $recipients[] = ['email' => $email];
    }
}

if (empty($recipients)) {
    http_response_code(400);
    echo json_encode(['error' => 'At least one valid recipient email in recipients array.']);
    exit;
}

// Resolve which sender account IDs to use (optional: sender_id or sender_ids)
$senders = [];
if ($senderIdsInput !== null || $senderIdInput !== null) {
    $requestedIds = [];
    if ($senderIdInput !== null) {
        $requestedIds[] = (int) $senderIdInput;
    }
    if (is_array($senderIdsInput)) {
        foreach ($senderIdsInput as $id) {
            $requestedIds[] = (int) $id;
        }
    } elseif ($senderIdsInput !== null && $senderIdsInput !== '') {
        $requestedIds[] = (int) $senderIdsInput;
    }
    $requestedIds = array_values(array_unique(array_filter($requestedIds, fn($id) => $id > 0)));
    if (empty($requestedIds)) {
        http_response_code(400);
        echo json_encode(['error' => 'sender_id or sender_ids must be non-empty positive integer(s). Use GET /api/v1/senders to list available senders.']);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($requestedIds), '?'));
    $stmt = $pdo->prepare("SELECT id FROM sender_accounts WHERE is_active = 1 AND id IN ($placeholders) ORDER BY id");
    $stmt->execute($requestedIds);
    $senders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $foundIds = array_map(fn($r) => (int)$r['id'], $senders);
    $missing = array_diff($requestedIds, $foundIds);
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or inactive sender ID(s): ' . implode(', ', $missing) . '. Use GET /api/v1/senders to list available senders.']);
        exit;
    }
}
$bodyWrapper = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:600px; margin:0 auto; border-collapse:collapse;"><tr><td style="padding:16px 20px; font-family:Arial, Helvetica, sans-serif;">' . $body . '</td></tr></table>';

if ($templateId !== null && $templateId > 0 || $templateName !== null && $templateName !== '') {
    $templateBlocks = loadTemplateForApi($pdo, $templateId > 0 ? $templateId : null, $templateName);
    if ($templateBlocks) {
        list($headerBlock, $footerBlock) = $templateBlocks;
        $body = $headerBlock . $bodyWrapper . $footerBlock;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Template not found. Use GET /api/v1/design/templates to list templates by id and name.']);
        exit;
    }
} elseif ($useDesign) {
    $designRow = $pdo->query('SELECT header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color FROM email_design WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    if ($designRow) {
        $bodyOutline = $designRow['body_outline_color'] ?? '';
        $headerBlock = buildDesignBlockApi($designRow, 'header', $config);
        $footerBlock = buildDesignBlockApi($designRow, 'footer', $config);
        $middle = $body;
        if ($bodyOutline !== '') {
            $middle = '<div style="border:2px solid ' . htmlspecialchars($bodyOutline) . '; border-radius:8px; padding:20px 24px; margin:0; background:#fff;">' . $body . '</div>';
        } else {
            $middle = $bodyWrapper;
        }
        $body = $headerBlock . $middle . $footerBlock;
    }
}

// When no senders chosen: use key default_sender_ids, or filter by API key name (e.g. "bayanihan.com" → only senders matching "bayanihan"), or all active
if (empty($senders)) {
    $keyName = trim((string)($apiKeyRow['name'] ?? ''));
    $matchTerm = $keyName !== '' ? strtolower(trim(preg_replace('/\.(com|net|ph|org|io|co\.uk)$/i', '', $keyName))) : '';
    if ($matchTerm !== '' && strlen($matchTerm) >= 2) {
        $pattern = '%' . $matchTerm . '%';
        $stmt = $pdo->prepare('SELECT id FROM sender_accounts WHERE is_active = 1 AND (LOWER(name) LIKE ? OR LOWER(email) LIKE ?) ORDER BY id');
        $stmt->execute([$pattern, $pattern]);
        $senders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if (empty($senders)) {
        $senders = $pdo->query('SELECT id FROM sender_accounts WHERE is_active=1 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    }
}
$pdo->prepare('INSERT INTO email_campaigns (subject, body, recipient_filter, rotate_senders, status, total_recipients) VALUES (?,?,?,?,?,?)')
    ->execute([$subject, $body, 'api', 1, 'sending', count($recipients)]);
$campaignId = (int)$pdo->lastInsertId();

$insertLog = $pdo->prepare('INSERT INTO email_logs (email_campaign_id, sender_account_id, recipient_email, status, open_tracking_token) VALUES (?,?,?,?,?)');
$senderCount = count($senders);
foreach ($recipients as $i => $r) {
    $senderId = $senderCount > 0 ? (int)$senders[$i % $senderCount]['id'] : null;
    $token = bin2hex(random_bytes(16));
    $insertLog->execute([$campaignId, $senderId, $r['email'], 'pending', $token]);
}

$trackingBase = trackingBaseUrlApi($config);
$usePhpMailer = file_exists(__DIR__ . '/vendor/autoload.php');
if ($usePhpMailer) require_once __DIR__ . '/vendor/autoload.php';

$logsStmt = $pdo->prepare('SELECT l.id, l.recipient_email, l.open_tracking_token, s.email as from_email, s.password, s.host, s.port, s.encryption, s.name as from_name FROM email_logs l LEFT JOIN sender_accounts s ON s.id = l.sender_account_id WHERE l.email_campaign_id = ? AND l.status = ?');
$logsStmt->execute([$campaignId, 'pending']);
$sent = 0;
$failed = 0;

while ($log = $logsStmt->fetch(PDO::FETCH_ASSOC)) {
    if ($usePhpMailer && !empty($log['from_email']) && !empty($log['host'])) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $bodyWithPixel = !empty($log['open_tracking_token']) ? injectTrackingPixelApi($body, $trackingBase, $log['open_tracking_token']) : $body;
            $mail->Body = $bodyWithPixel;
            $mail->setFrom($log['from_email'], $log['from_name'] ?? '');
            $mail->addAddress($log['recipient_email']);
            $mail->isSMTP();
            $mail->Host = $log['host'];
            $mail->Port = (int)$log['port'];
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
        $msg = empty($log['from_email']) ? 'No sender account.' : 'Install PHPMailer (composer install).';
        $pdo->prepare('UPDATE email_logs SET status=?, error_message=? WHERE id=?')->execute(['failed', $msg, $log['id']]);
        $failed++;
    }
}

$pdo->prepare('UPDATE email_campaigns SET status=?, sent_count=?, failed_count=?, completed_at=? WHERE id=?')
    ->execute(['completed', $sent, $failed, date('Y-m-d H:i:s'), $campaignId]);

http_response_code(201);
echo json_encode([
    'success' => true,
    'campaign_id' => $campaignId,
    'total_recipients' => count($recipients),
    'sent' => $sent,
    'failed' => $failed,
]);
