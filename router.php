<?php
/**
 * Router for PHP built-in server. Run: php -S localhost:8080 router.php
 * Routes /, /compose, /senders, /contacts, /logs to index.php?page=...
 */
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';
parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
$_GET = array_merge($_GET, $query);

// API: GET /api/v1/senders (list senders for selection)
if ($path === '/api/v1/senders' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    require __DIR__ . '/api-senders.php';
    return true;
}
// API: GET /api/v1/design (current header/footer design as JSON)
if ($path === '/api/v1/design' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    require __DIR__ . '/design-json.php';
    return true;
}
// API: GET /api/v1/design/templates (list named templates for Load template dropdown)
if ($path === '/api/v1/design/templates' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    require __DIR__ . '/design-templates-json.php';
    return true;
}
// API: POST /api/v1/design/templates/delete (delete a named template from Load template dropdown)
if ($path === '/api/v1/design/templates/delete' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/design-template-delete.php';
    return true;
}
// API: POST /api/v1/send (external sites send campaigns)
if ($path === '/api/v1/send' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/api.php';
    return true;
}

// API test site (separate page to test the API)
if ($path === '/api-test' || $path === '/api-test/') {
    require __DIR__ . '/api-test/index.php';
    return true;
}

// Open-tracking pixel: /track/email-open/{token}
if (preg_match('#^/track/email-open/([a-zA-Z0-9]+)$#', $path, $m)) {
    $_GET['action'] = 'track-open';
    $_GET['token'] = $m[1];
    require __DIR__ . '/index.php';
    return true;
}

$pageMap = [
    '/' => 'index',
    '/compose' => 'compose',
    '/senders' => 'senders',
    '/sender-edit' => 'sender-edit',
    '/contacts' => 'contacts',
    '/contact-edit' => 'contact-edit',
    '/contacts-import' => 'contacts-import',
    '/groups' => 'groups',
    '/group-edit' => 'group-edit',
    '/design' => 'design',
    '/api' => 'api',
    '/logs' => 'logs',
];

if (isset($pageMap[$path])) {
    $_GET['page'] = $pageMap[$path];
}

if ($path === '/index.php' || isset($pageMap[$path])) {
    require __DIR__ . '/index.php';
    return true;
}

if (preg_match('/\.(css|js|ico|png|jpg|jpeg|gif|svg)$/', $path) && is_file(__DIR__ . $path)) {
    return false;
}

// Serve uploaded logos from data/uploads (safe filename only)
if (preg_match('#^/uploads/([a-zA-Z0-9_\-]+\.(png|jpe?g|gif|webp|svg))$#', $path, $m)) {
    $file = __DIR__ . '/data/uploads/' . $m[1];
    if (is_file($file)) {
        $types = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
        header('Content-Type: ' . ($types[strtolower($m[2])] ?? 'application/octet-stream'));
        readfile($file);
        return true;
    }
}

require __DIR__ . '/index.php';
return true;
