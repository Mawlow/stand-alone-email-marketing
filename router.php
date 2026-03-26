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
// API: GET /api/v1/contacts (list marketing contacts for use as recipients)
if ($path === '/api/v1/contacts' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    require __DIR__ . '/api-contacts.php';
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
// API: POST /api/v1/compose/ai-generate (OpenAI-generated email body; requires OPENAI_API_KEY in .env)
if ($path === '/api/v1/compose/ai-generate' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/api-ai-generate.php';
    return true;
}
// API link: GET /api/v1/send/partners/{slug}/senders — list senders (so their system can choose sender_id)
if (preg_match('#^/api/v1/send/partners/([a-zA-Z0-9_-]+)/senders$#', $path, $m)) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        $_GET['link_slug'] = $m[1];
        require __DIR__ . '/api-partners-senders.php';
        return true;
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(['error' => 'Use GET to list senders. To send a campaign, use POST to /api/v1/send/partners/' . $m[1] . ' (without /senders).']);
    return true;
}
// API link: GET /api/v1/send/partners/{slug}/templates — list templates for this partner (respects default_template_id)
if (preg_match('#^/api/v1/send/partners/([a-zA-Z0-9_-]+)/templates$#', $path, $m)) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        $_GET['link_slug'] = $m[1];
        require __DIR__ . '/api-partners-templates.php';
        return true;
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(['error' => 'Use GET to list templates.']);
    return true;
}
// API link: POST /api/v1/send/partners/{slug} — partner uses this URL only (no API key header)
if (preg_match('#^/api/v1/send/partners/([a-zA-Z0-9_-]+)$#', $path, $m)) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $_GET['link_slug'] = $m[1];
        require __DIR__ . '/api-send-by-link.php';
        return true;
    }
    // GET or other method: return JSON error so user knows they hit the API but used wrong method
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST to send a campaign.']);
    return true;
}
// API: POST /api/v1/send (external sites send campaigns; use X-API-Key header)
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
    '/' => 'landing',
    '/dashboard' => 'index',
    '/login' => 'login',
    '/register' => 'register',
    '/logout' => 'logout',
    '/compose' => 'compose',
    '/senders' => 'senders',
    '/sender-edit' => 'sender-edit',
    '/contacts' => 'contacts',
    '/contact-edit' => 'contact-edit',
    '/contacts-import' => 'contacts-import',
    '/groups' => 'groups',
    '/group-edit' => 'group-edit',
    '/design' => 'design',
    '/template-html' => 'template-html',
    '/api' => 'api',
    '/api-settings' => 'api',
    '/registrations' => 'registrations',
    '/users' => 'users',
    '/logs' => 'logs',
    '/sms-logs' => 'sms-logs',
    '/sms' => 'sms',
    '/whatsapp' => 'whatsapp',
    '/admin' => 'admin',
];

if (isset($pageMap[$path])) {
    $_GET['page'] = $pageMap[$path];
}

if ($path === '/index.php' || isset($pageMap[$path])) {
    require __DIR__ . '/index.php';
    return true;
}

if (preg_match('/\.(css|js|ico|png|jpg|jpeg|gif|svg|mp4)$/', $path) && is_file(__DIR__ . $path)) {
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
