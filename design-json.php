<?php
/**
 * GET /api/v1/design – return current email design (header/footer HTML, colors) as JSON.
 * Used by Compose page "Load template" to load header & footer from saved Design.
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

$row = $pdo->query('SELECT header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color FROM email_design WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode([
        'header_html' => '',
        'footer_html' => '',
        'footer_bg_color' => '#f1f5f9',
        'block_text_color' => '#1e293b',
        'header_logo_url' => '',
        'header_mode' => 'text_only',
        'footer_logo_url' => '',
        'footer_mode' => 'text_only',
        'body_outline_color' => '',
    ]);
    exit;
}

// Prefer text_only when HTML content is present (so saved design shows in compose)
$headerMode = trim((string)($row['header_html'] ?? '')) !== '' ? 'text_only' : ($row['header_mode'] ?? 'text_only');
$footerMode = trim((string)($row['footer_html'] ?? '')) !== '' ? 'text_only' : ($row['footer_mode'] ?? 'text_only');
if (!in_array($headerMode, ['logo_only', 'text_only', 'logo_and_text'], true)) $headerMode = 'text_only';
if (!in_array($footerMode, ['logo_only', 'text_only', 'logo_and_text'], true)) $footerMode = 'text_only';

echo json_encode([
    'header_html' => $row['header_html'] ?? '',
    'footer_html' => $row['footer_html'] ?? '',
    'footer_bg_color' => $row['footer_bg_color'] !== '' ? $row['footer_bg_color'] : '#f1f5f9',
    'block_text_color' => !empty($row['block_text_color']) ? $row['block_text_color'] : '#1e293b',
    'header_logo_url' => $row['header_logo_url'] ?? '',
    'header_mode' => $headerMode,
    'footer_logo_url' => $row['footer_logo_url'] ?? '',
    'footer_mode' => $footerMode,
    'body_outline_color' => $row['body_outline_color'] ?? '',
]);
