<?php
/**
 * POST /api/v1/compose/ai-generate
 * Body: { "prompt": "e.g. professional welcome email for new subscribers" }
 * Returns: { "content": "<p>...</p>" } or { "error": "..." }
 * Requires OPENAI_API_KEY in .env
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$prompt = isset($input['prompt']) && is_string($input['prompt']) ? trim($input['prompt']) : '';
if ($prompt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or empty prompt.']);
    exit;
}

$envPath = __DIR__ . '/.env';
if (!is_file($envPath) && is_file(__DIR__ . '/.env.example')) {
    copy(__DIR__ . '/.env.example', $envPath);
}
$config = [];
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $key = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) $value = substr($value, 1, -1);
        $config[strtolower($key)] = $value;
    }
}
$apiKey = $config['openai_api_key'] ?? '';
if ($apiKey === '') {
    http_response_code(503);
    echo json_encode(['error' => 'OpenAI API key not configured. Add OPENAI_API_KEY to .env']);
    exit;
}

$systemPrompt = 'You are an expert email copywriter. Generate only the email body content as HTML suitable for insertion into an email. Use simple HTML tags like <p>, <strong>, <a>, <ul>, <li>. No <html> or <body>. Return only the HTML, no markdown or explanation.';
$userPrompt = $prompt;

$payload = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt],
    ],
    'max_tokens' => 1024,
];

$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . $apiKey . "\r\n",
        'content' => json_encode($payload),
        'ignore_errors' => true,
    ],
]);
$response = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $ctx);
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not reach OpenAI API.']);
    exit;
}

$data = json_decode($response, true);
$content = null;
if (isset($data['choices'][0]['message']['content'])) {
    $content = trim((string) $data['choices'][0]['message']['content']);
    $content = preg_replace('/^```html\s*|\s*```$/i', '', $content);
    $content = trim($content);
    if ($content !== '' && !preg_match('/^<[a-z]/i', $content)) {
        $content = '<p>' . nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . '</p>';
    }
}
if ($content === null || $content === '') {
    $err = $data['error']['message'] ?? $data['error']['code'] ?? 'Unknown error';
    http_response_code(502);
    echo json_encode(['error' => 'OpenAI: ' . (is_string($err) ? $err : json_encode($err))]);
    exit;
}

echo json_encode(['content' => $content]);
