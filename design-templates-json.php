<?php
/**
 * GET /api/v1/design/templates – return all named design templates (for Load template dropdown).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET.']);
    exit;
}

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
        }
    }
}
$mysql = $config['db_mysql'] ?? null;
if (empty($mysql) || empty($mysql['database'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfiguration.']);
    exit;
}
$dsn = 'mysql:host=' . ($mysql['host'] ?? '127.0.0.1') . ';port=' . ($mysql['port'] ?? 3306) . ';dbname=' . $mysql['database'] . ';charset=' . ($mysql['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $mysql['username'] ?? 'root', $mysql['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Optional: when X-API-Key or Bearer is sent and that key has default_template_id, return only that template
$filterTemplateId = null;
$apiKey = null;
if (!empty($_SERVER['HTTP_X_API_KEY'])) {
    $apiKey = trim($_SERVER['HTTP_X_API_KEY']);
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/^\s*Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
    $apiKey = trim($m[1]);
}
if ($apiKey !== null && $apiKey !== '') {
    $stmt = $pdo->prepare('SELECT default_template_id FROM api_keys WHERE api_key = ?');
    $stmt->execute([$apiKey]);
    $keyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($keyRow && isset($keyRow['default_template_id']) && (int)$keyRow['default_template_id'] > 0) {
        $filterTemplateId = (int)$keyRow['default_template_id'];
    }
}

/** Split combined header+footer by <!-- FOOTER --> for display when DB has same in both columns. */
$splitHeaderFooterForDisplay = function (string $header, string $footer): array {
    if ($header !== $footer || trim($header) === '') return [$header, $footer];
    if (!preg_match('#\s*<!--\s*FOOTER\s*-->\s*#is', $header, $m, PREG_OFFSET_CAPTURE)) return [$header, $footer];
    $pos = $m[0][1];
    $before = trim(substr($header, 0, $pos));
    $after = trim(substr($header, $pos + strlen($m[0][0])));
    return [$before, $after];
};

$sql = 'SELECT id, name, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color FROM email_design_templates';
if ($filterTemplateId !== null) {
    $stmt = $pdo->prepare($sql . ' WHERE id = ?');
    $stmt->execute([$filterTemplateId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $rows = $pdo->query($sql . ' ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
}
$templates = [];
foreach ($rows as $r) {
    $headerHtml = $r['header_html'] ?? '';
    $footerHtml = $r['footer_html'] ?? '';
    list($headerHtml, $footerHtml) = $splitHeaderFooterForDisplay($headerHtml, $footerHtml);
    $headerMode = trim((string)$headerHtml) !== '' ? 'text_only' : ($r['header_mode'] ?? 'text_only');
    $footerMode = trim((string)$footerHtml) !== '' ? 'text_only' : ($r['footer_mode'] ?? 'text_only');
    if (!in_array($headerMode, ['logo_only', 'text_only', 'logo_and_text'], true)) $headerMode = 'text_only';
    if (!in_array($footerMode, ['logo_only', 'text_only', 'logo_and_text'], true)) $footerMode = 'text_only';
    $templates[] = [
        'id' => (int)$r['id'],
        'name' => $r['name'],
        'header_html' => $headerHtml,
        'footer_html' => $footerHtml,
        'footer_bg_color' => $r['footer_bg_color'] !== '' ? $r['footer_bg_color'] : '#f1f5f9',
        'block_text_color' => !empty($r['block_text_color']) ? $r['block_text_color'] : '#1e293b',
        'header_logo_url' => $r['header_logo_url'] ?? '',
        'header_mode' => $headerMode,
        'footer_logo_url' => $r['footer_logo_url'] ?? '',
        'footer_mode' => $footerMode,
        'body_outline_color' => $r['body_outline_color'] ?? '',
    ];
}
echo json_encode(['templates' => $templates]);
