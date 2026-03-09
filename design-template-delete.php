<?php
/**
 * POST /api/v1/design/templates/delete - delete a named design template by id.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
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

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$templateId = (int)($input['id'] ?? $_POST['id'] ?? 0);
if ($templateId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Template id is required.']);
    exit;
}

$stmt = $pdo->prepare('DELETE FROM email_design_templates WHERE id = ?');
$stmt->execute([$templateId]);

if ($stmt->rowCount() < 1) {
    http_response_code(404);
    echo json_encode(['error' => 'Template not found.']);
    exit;
}

echo json_encode(['ok' => true]);
