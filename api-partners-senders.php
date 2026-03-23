<?php
/**
 * GET /api/v1/send/partners/{slug}/senders
 * List senders for API-link partners (no API key header). So their system can choose sender_id.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET.']);
    exit;
}

$slug = isset($_GET['link_slug']) ? trim((string)$_GET['link_slug']) : '';
if ($slug === '') {
    http_response_code(404);
    echo json_encode(['error' => 'Not found.']);
    exit;
}

$envPath = __DIR__ . '/.env';
if (!is_file($envPath) && is_file(__DIR__ . '/.env.example')) copy(__DIR__ . '/.env.example', $envPath);
$config = [];
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
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

$stmt = $pdo->prepare('SELECT id, default_sender_ids FROM api_keys WHERE link_slug = ? AND link_slug IS NOT NULL');
$stmt->execute([$slug]);
$keyRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$keyRow) {
    http_response_code(404);
    echo json_encode(['error' => 'API link not found for this slug.']);
    exit;
}

$defaultSenderIds = isset($keyRow['default_sender_ids']) && trim((string)$keyRow['default_sender_ids']) !== ''
    ? array_map('intval', array_filter(explode(',', str_replace(' ', '', $keyRow['default_sender_ids']))))
    : [];
if (!empty($defaultSenderIds)) {
    $placeholders = implode(',', array_fill(0, count($defaultSenderIds), '?'));
    $stmt = $pdo->prepare("SELECT id, name, email FROM sender_accounts WHERE is_active = 1 AND id IN ($placeholders) ORDER BY name, id");
    $stmt->execute($defaultSenderIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $rows = $pdo->query('SELECT id, name, email FROM sender_accounts WHERE is_active = 1 ORDER BY name, id')->fetchAll(PDO::FETCH_ASSOC);
}
echo json_encode(['senders' => $rows]);
