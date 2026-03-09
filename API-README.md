# Email Marketing API – Integration guide for external sites

This document explains how **your website** (PHP, Laravel, .NET, or any language) can send email campaigns through the **Standalone Email Marketing** system using its API. The standalone app acts as the sender; your site only sends a request with subject, body, and recipient list.

---

## 1. Get your API key and base URL

1. Log in to the **Standalone Email Marketing** app (the one that has Senders, Design, etc.).
2. Open the **API** page in the sidebar.
3. Click **Create key**, enter a name (e.g. your site or company), and copy the key. **Store it securely** — it is shown only once.
4. Note the **base URL** of the standalone app (e.g. `https://email.yourdomain.com` or `http://localhost:8080`).

**Endpoint:** `POST {baseUrl}/api/v1/send`

**Authentication:** Send the key in one of these ways:

- Header: `X-API-Key: your-api-key-here`
- Or: `Authorization: Bearer your-api-key-here`

**Request body (JSON):**

| Field        | Type    | Required | Description |
|-------------|---------|----------|-------------|
| `subject`  | string  | Yes      | Email subject line. |
| `body`     | string  | Yes      | Email body (HTML or plain text). |
| `recipients` | array | Yes      | List of recipient emails. See below. |
| `use_design` | boolean | No     | If `true`, the app wraps your body with its header/footer from Design. Default: `false`. |

**Recipients** can be:

- Array of strings: `["a@example.com", "b@example.com"]`
- Or array of objects: `[{ "email": "a@example.com" }, { "email": "b@example.com", "name": "B" }]`

**Success response (201):**

```json
{
  "success": true,
  "campaign_id": 123,
  "total_recipients": 5,
  "sent": 5,
  "failed": 0
}
```

**Error responses (4xx/5xx):** JSON with an `error` message, e.g. `{ "error": "Invalid API key." }`.

---

## 2. cURL (any environment)

```bash
curl -X POST "https://your-email-app.com/api/v1/send" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "subject": "Hello from our site",
    "body": "<p>This is the email content.</p>",
    "recipients": ["user1@example.com", "user2@example.com"],
    "use_design": true
  }'
```

---

## 3. PHP (plain)

```php
<?php
$baseUrl = 'https://your-email-app.com';  // Standalone app URL
$apiKey  = 'your-api-key-here';

$payload = [
    'subject'    => 'Hello from our site',
    'body'       => '<p>HTML or plain text content.</p>',
    'recipients' => ['user1@example.com', 'user2@example.com'],
    'use_design' => true,
];

$ch = curl_init($baseUrl . '/api/v1/send');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($code === 201 && !empty($data['success'])) {
    echo "Campaign sent. Sent: " . $data['sent'];
} else {
    echo "Error: " . ($data['error'] ?? $response);
}
```

---

## 4. Laravel (PHP)

**Option A – HTTP client (Laravel 7+)**

```php
use Illuminate\Support\Facades\Http;

$baseUrl = config('services.email_marketing.url');   // e.g. https://your-email-app.com
$apiKey  = config('services.email_marketing.api_key');

$response = Http::withHeaders([
    'X-API-Key' => $apiKey,
])->post($baseUrl . '/api/v1/send', [
    'subject'    => 'Hello from our site',
    'body'       => '<p>HTML content.</p>',
    'recipients' => ['user1@example.com', 'user2@example.com'],
    'use_design' => true,
]);

if ($response->successful()) {
    $data = $response->json();
    // $data['campaign_id'], $data['sent'], $data['failed']
} else {
    $error = $response->json('error', $response->body());
    // handle error
}
```

**Config** (`config/services.php`):

```php
'email_marketing' => [
    'url'     => env('EMAIL_MARKETING_API_URL', 'https://your-email-app.com'),
    'api_key' => env('EMAIL_MARKETING_API_KEY'),
],
```

**.env:**

```
EMAIL_MARKETING_API_URL=https://your-email-app.com
EMAIL_MARKETING_API_KEY=your-api-key-here
```

**Option B – Guzzle**

```php
use Illuminate\Support\Facades\Http;
// or
$client = new \GuzzleHttp\Client();
$res = $client->post($baseUrl . '/api/v1/send', [
    'headers' => [
        'Content-Type' => 'application/json',
        'X-API-Key'   => $apiKey,
    ],
    'json' => [
        'subject'    => $subject,
        'body'       => $body,
        'recipients' => $recipients,
        'use_design' => true,
],
]);
$data = json_decode($res->getBody(), true);
```

---

## 5. .NET (C#)

**Using `HttpClient`:**

```csharp
using System.Net.Http;
using System.Text;
using System.Text.Json;

var baseUrl = "https://your-email-app.com";
var apiKey  = "your-api-key-here";

var payload = new {
    subject    = "Hello from our site",
    body       = "<p>HTML or plain text content.</p>",
    recipients = new[] { "user1@example.com", "user2@example.com" },
    use_design = true
};

using var client = new HttpClient();
client.DefaultRequestHeaders.Add("X-API-Key", apiKey);

var json = JsonSerializer.Serialize(payload);
var content = new StringContent(json, Encoding.UTF8, "application/json");

var response = await client.PostAsync($"{baseUrl}/api/v1/send", content);

if (response.StatusCode == System.Net.HttpStatusCode.Created)
{
    var responseBody = await response.Content.ReadAsStringAsync();
    var data = JsonSerializer.Deserialize<JsonElement>(responseBody);
    var sent = data.GetProperty("sent").GetInt32();
    // use campaign_id, total_recipients, failed, etc.
}
else
{
    var errorBody = await response.Content.ReadAsStringAsync();
    var error = JsonSerializer.Deserialize<JsonElement>(errorBody);
    var message = error.GetProperty("error").GetString();
    // handle error
}
```

**Using `IHttpClientFactory` (ASP.NET Core):**

Register in `Program.cs` or `Startup.cs`:

```csharp
builder.Services.AddHttpClient("EmailMarketing", client =>
{
    client.BaseAddress = new Uri(builder.Configuration["EmailMarketing:BaseUrl"]!);
    client.DefaultRequestHeaders.Add("X-API-Key", builder.Configuration["EmailMarketing:ApiKey"]);
});
```

Then inject `IHttpClientFactory` and call:

```csharp
var httpClient = _httpClientFactory.CreateClient("EmailMarketing");
var payload = new { subject, body, recipients, use_design = true };
var content = new StringContent(JsonSerializer.Serialize(payload), Encoding.UTF8, "application/json");
var response = await httpClient.PostAsync("api/v1/send", content);
```

---

## 6. JavaScript / Node.js

**Browser (fetch):**

```javascript
const baseUrl = 'https://your-email-app.com';
const apiKey  = 'your-api-key-here';

const response = await fetch(`${baseUrl}/api/v1/send`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': apiKey,
  },
  body: JSON.stringify({
    subject: 'Hello from our site',
    body: '<p>HTML content.</p>',
    recipients: ['user1@example.com', 'user2@example.com'],
    use_design: true,
  }),
});

const data = await response.json();
if (response.ok) {
  console.log('Sent:', data.sent);
} else {
  console.error('Error:', data.error);
}
```

**Node.js (no extra deps):**

```javascript
const https = require('https');

const baseUrl = 'https://your-email-app.com';
const apiKey  = 'your-api-key-here';
const url    = new URL('/api/v1/send', baseUrl);

const payload = JSON.stringify({
  subject: 'Hello from our site',
  body: '<p>HTML content.</p>',
  recipients: ['user1@example.com', 'user2@example.com'],
  use_design: true,
});

const options = {
  hostname: url.hostname,
  port: url.port || 443,
  path: url.pathname,
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': apiKey,
    'Content-Length': Buffer.byteLength(payload),
  },
};

const req = require('https').request(options, (res) => {
  let body = '';
  res.on('data', (ch) => body += ch);
  res.on('end', () => {
    const data = JSON.parse(body);
    if (res.statusCode === 201) console.log('Sent:', data.sent);
    else console.error('Error:', data.error);
  });
});
req.on('error', (e) => console.error(e));
req.write(payload);
req.end();
```

---

## 7. Python

```python
import urllib.request
import json

base_url = "https://your-email-app.com"
api_key  = "your-api-key-here"

payload = {
    "subject": "Hello from our site",
    "body": "<p>HTML content.</p>",
    "recipients": ["user1@example.com", "user2@example.com"],
    "use_design": True,
}

data = json.dumps(payload).encode("utf-8")
req = urllib.request.Request(
    f"{base_url}/api/v1/send",
    data=data,
    headers={
        "Content-Type": "application/json",
        "X-API-Key": api_key,
    },
    method="POST",
)

with urllib.request.urlopen(req) as resp:
    result = json.load(resp)
    print("Sent:", result["sent"])
```

---

## 8. Checklist for your site

1. Get an API key from the Standalone Email Marketing app (API page).
2. Store the key securely (env var or secrets), not in source code.
3. Use the correct base URL (with `https` in production).
4. Send `Content-Type: application/json` and either `X-API-Key` or `Authorization: Bearer <key>`.
5. Send `subject`, `body`, and `recipients`; set `use_design` to `true` if you want the app’s header/footer.
6. Handle non-201 responses and the `error` field for user feedback or logging.

---

## 9. Security notes

- Keep the API key secret; treat it like a password.
- Prefer HTTPS for the base URL in production.
- Validate and sanitize subject/body/recipients on your side before calling the API.
- The standalone app sends emails using its own Sender accounts and (if `use_design` is true) its Design; your site only triggers the send.
