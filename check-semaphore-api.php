<?php
/**
 * Verify Semaphore API key and show account info. Run: php check-semaphore-api.php
 * Reads SEMAPHORE_API_KEY from .env (or pass as first arg: php check-semaphore-api.php YOUR_KEY)
 */
$config = [];
if (is_file(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
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
$apiKey = $argv[1] ?? ($config['semaphore_api_key'] ?? '');
if ($apiKey === '') {
    echo "Usage: php check-semaphore-api.php [API_KEY]\nOr set SEMAPHORE_API_KEY in .env\n";
    exit(1);
}
$url = 'https://api.semaphore.co/api/v4/account?apikey=' . urlencode($apiKey);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $code\n";
if ($err) {
    echo "cURL Error: $err\n";
}
echo "Response: " . ($response ?: '(empty)') . "\n";

if ($response) {
    $data = json_decode($response, true);
    if (is_array($data)) {
        echo "\n--- Parsed ---\n";
        echo "Account ID: " . ($data['account_id'] ?? 'N/A') . "\n";
        echo "Account Name: " . ($data['account_name'] ?? 'N/A') . "\n";
        echo "Status: " . ($data['status'] ?? 'N/A') . "\n";
        echo "Credit Balance: " . ($data['credit_balance'] ?? 'N/A') . "\n";
    }
}
