<?php
/**
 * API: GET /api/v1/contacts – list marketing contacts for use as recipients by external sites.
 * Auth: X-API-Key or Authorization: Bearer <key>
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET.']);
    exit;
}

// Bootstrap (same as api-senders.php)
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

// API key
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
$stmt = $pdo->prepare('SELECT id, name FROM api_keys WHERE api_key = ?');
$stmt->execute([$apiKey]);
if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key.']);
    exit;
}

$rows = $pdo->query('SELECT id, email, company_name, notes, created_at FROM marketing_contacts ORDER BY email')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['contacts' => $rows]);
