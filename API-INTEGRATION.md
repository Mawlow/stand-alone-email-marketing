# Email Marketing API – Integration Guide

This guide is for **external websites** that want to send email campaigns through the central Email Marketing system.

---

## We only provide the API – you implement the rest

- **We provide:** The API (endpoints, API keys, and this documentation). We do **not** run your website, your cron jobs, or your code.
- **You implement:** Your own email marketing page, cron jobs, or backend that **calls our API**. For example:
  - A cron job on your server that runs daily and sends a newsletter by calling `POST /api/v1/send`.
  - A form on your site that collects subject, template, body, and recipients; your server then calls our API to send the campaign.
  - Any other automation (scripts, queues, etc.) that sends HTTP requests to our endpoints.

You receive an **API key** and **base URL** from us. From then on, **your systems** (your server, your cron, your backend) are the only ones that call our API. We never access or run anything on your side.

---

## Typical flow on your side (your code / cron / backend)

1. **Subject** – User enters the email subject.
2. **Header & footer template** – User selects one of the templates available in our system (same as our “Load template” list). Call `GET /api/v1/design/templates` to show a dropdown.
3. **Body** – User writes or pastes the main email content (HTML).
4. **Recipients** – User enters or selects the list of recipient emails.
5. **Send** – You call `POST /api/v1/send` with `subject`, `body`, `recipients`, and `template_id` (or `template_name`). Our system wraps the body with the chosen template’s header and footer and sends the campaign.

---

## 1. Get your API key and base URL

1. Ask the administrator of the Email Marketing system to create an **API key** for your site (they use the **API** section in the app).
2. You will receive:
   - **API key** – a long string (e.g. `a1b2c3d4e5...`). **Copy it once; it is not shown again.**
   - **Base URL** – the root URL of the Email Marketing app (e.g. `https://email.yourcompany.com` or `http://localhost:8080`).

3. Store the API key securely (e.g. environment variable, secrets manager). **Do not** commit it to source control.

---

## 2. API overview

### 2.1 List senders (for selection)

External sites can **list the sender accounts** configured on the Email Marketing system and let their users choose which sender to use for a campaign.

| Item        | Value |
|------------|--------|
| **Method** | `GET` |
| **URL**    | `{BASE_URL}/api/v1/senders` |
| **Auth**   | Same as send: `X-API-Key` or `Authorization: Bearer <key>` |

**Response (200):**

```json
{
  "senders": [
    { "id": 1, "name": "Support", "email": "support@example.com" },
    { "id": 2, "name": "Newsletter", "email": "news@example.com" }
  ]
}
```

Use this list to build a dropdown (or multi-select) on your site. When sending a campaign, pass the chosen ID(s) in the send request (see below).

### 2.2 List templates (header & footer)

External sites can **list the header/footer templates** (the same ones available in the central app’s “Load template” dropdown). The user picks one so their campaign uses that design.

| Item        | Value |
|------------|--------|
| **Method** | `GET` |
| **URL**    | `{BASE_URL}/api/v1/design/templates` |
| **Auth**   | None required |

**Response (200):**

```json
{
  "templates": [
    { "id": 1, "name": "Bayanihan 2", "header_html": "...", "footer_html": "...", ... },
    { "id": 2, "name": "IT Department", "header_html": "...", "footer_html": "...", ... }
  ]
}
```

Use `id` and `name` to build a dropdown (e.g. “Header & footer: Bayanihan 2”). When sending, pass either `template_id` (e.g. `1`) or `template_name` (e.g. `"Bayanihan 2"`) in the send request.

### 2.3 Send campaign

| Item        | Value |
|------------|--------|
| **Method** | `POST` |
| **URL**    | `{BASE_URL}/api/v1/send` |
| **Auth**   | API key in header (see below) |
| **Body**   | JSON |

### Headers

| Header            | Required | Description |
|-------------------|----------|-------------|
| `Content-Type`    | Yes      | `application/json` |
| `X-API-Key`       | Yes*     | Your API key |
| `Authorization`   | Yes*     | `Bearer {your-api-key}` |

*Use either `X-API-Key` or `Authorization: Bearer ...`.

### Request body (JSON)

| Field          | Type    | Required | Description |
|----------------|---------|----------|-------------|
| `subject`      | string  | Yes      | Email subject line |
| `body`         | string  | Yes      | Email body (HTML). Will be placed between header and footer when a template is used. |
| `recipients`   | array   | Yes      | List of recipient emails (see below) |
| `template_id`  | integer | No       | Use this header/footer template (ID from GET /api/v1/design/templates). Preferred when you have the id. |
| `template_name`| string  | No       | Use the template with this name (e.g. `"Bayanihan 2"`). Use if you don’t have the id. |
| `use_design`   | boolean | No       | If `true` and no `template_id`/`template_name`, body is wrapped with the default design (id=1). Default: `false` |
| `sender_id`    | integer | No       | Use only this sender account (ID from GET /api/v1/senders). If omitted, all active senders are used (round-robin). |
| `sender_ids`   | array   | No       | Use only these sender account IDs, e.g. `[1, 3]`. Round-robin across these. Use with or instead of `sender_id`. |

**Template vs use_design:** If you send `template_id` or `template_name`, that template’s header and footer are used (same as “Load template” in the app). If you send only `use_design: true`, the default design (single row in Design) is used. Template takes precedence over `use_design` when both are present.

**Recipients** can be:

- An array of email strings: `["a@example.com", "b@example.com"]`
- An array of objects: `[{"email": "a@example.com"}, {"email": "b@example.com", "name": "B"}]` (only `email` is required)

### Success response (201)

```json
{
  "success": true,
  "campaign_id": 123,
  "total_recipients": 10,
  "sent": 10,
  "failed": 0
}
```

### Error responses (4xx / 5xx)

```json
{
  "error": "Human-readable message"
}
```

Common cases:

- **401** – Missing or invalid API key
- **400** – Missing `subject` / `body`, invalid `recipients`, invalid/inactive `sender_id` / `sender_ids`, or **template not found** (when `template_id` / `template_name` is given but no matching template exists)
- **405** – Method not allowed (must be POST)

**Example send request (with template and recipients):**

```json
{
  "subject": "Monthly update",
  "body": "<p>Hello,</p><p>Here is your monthly summary.</p>",
  "recipients": ["user1@example.com", "user2@example.com"],
  "template_id": 1
}
```

Or use the template by name: `"template_name": "Bayanihan 2"` instead of `template_id`. Our system wraps `body` with that template’s header and footer and sends the campaign.

### Selecting senders on your site

1. **Load senders:** Call `GET {BASE_URL}/api/v1/senders` with your API key and cache or show the `senders` array.
2. **Show a dropdown:** e.g. “From (sender):” with options like “Support (support@example.com)”, “Newsletter (news@example.com)” using `id`, `name`, and `email`.
3. **Send with chosen sender:** In `POST /api/v1/send`, include `sender_id: <id>` (or `sender_ids: [id1, id2]` to rotate among several). If you omit both, the central system uses all active senders (round-robin).

**Example (PHP):** fetch senders, then send with the selected one:

```php
// 1) Get senders for dropdown
$ch = curl_init($baseUrl . '/api/v1/senders');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey]]);
$list = json_decode(curl_exec($ch), true);
curl_close($ch);
$senders = $list['senders'] ?? [];  // [ ['id' => 1, 'name' => 'Support', 'email' => '...'], ... ]

// 2) User picks sender_id (e.g. from dropdown), then send
$payload = [
    'subject' => 'Hello',
    'body' => '<p>Hi</p>',
    'recipients' => ['user@example.com'],
    'sender_id' => (int) $selectedSenderId,  // or sender_ids: [1, 2]
];
// ... POST to /api/v1/send with $payload
```

---

## 3. Code examples

Replace `BASE_URL` and `YOUR_API_KEY` with your actual values (or use env vars).

---

### PHP (plain)

```php
<?php
$baseUrl = getenv('EMAIL_API_BASE_URL') ?: 'https://email.yourcompany.com';
$apiKey  = getenv('EMAIL_API_KEY') ?: 'YOUR_API_KEY';

$payload = [
    'subject'    => 'Welcome to our service',
    'body'       => '<p>Hello,</p><p>Thank you for signing up.</p>',
    'recipients' => ['user1@example.com', 'user2@example.com'],
    'use_design' => true,
];

$ch = curl_init($baseUrl . '/api/v1/send');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
    ],
]);

$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($code === 201 && !empty($data['success'])) {
    echo "Sent: " . $data['sent'] . ", Failed: " . $data['failed'];
} else {
    echo "Error: " . ($data['error'] ?? $response);
}
```

---

### PHP Laravel

**1. Config (e.g. in `.env`):**

```env
EMAIL_MARKETING_API_URL=https://email.yourcompany.com
EMAIL_MARKETING_API_KEY=your-api-key-here
```

**2. Service class (e.g. `app/Services/EmailMarketingApiService.php`):**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailMarketingApiService
{
    public function sendCampaign(string $subject, string $body, array $recipients, bool $useDesign = true): array
    {
        $url = rtrim(config('services.email_marketing.url', env('EMAIL_MARKETING_API_URL')), '/') . '/api/v1/send';
        $key = config('services.email_marketing.key', env('EMAIL_MARKETING_API_KEY'));

        $response = Http::withHeaders([
            'X-API-Key' => $key,
        ])->post($url, [
            'subject'    => $subject,
            'body'       => $body,
            'recipients' => $recipients,
            'use_design' => $useDesign,
        ]);

        $data = $response->json();
        if ($response->successful()) {
            return $data;
        }
        Log::warning('Email Marketing API error', ['response' => $data, 'status' => $response->status()]);
        throw new \RuntimeException($data['error'] ?? 'Email Marketing API request failed');
    }
}
```

**3. Register config (in `config/services.php`):**

```php
'email_marketing' => [
    'url' => env('EMAIL_MARKETING_API_URL'),
    'key' => env('EMAIL_MARKETING_API_KEY'),
],
```

**4. Usage in a controller or job:**

```php
use App\Services\EmailMarketingApiService;

$service = app(EmailMarketingApiService::class);
$result = $service->sendCampaign(
    'Monthly newsletter',
    '<h1>Hello</h1><p>Here is your update.</p>',
    ['customer1@example.com', 'customer2@example.com'],
    true
);
// $result = ['success' => true, 'campaign_id' => 123, 'sent' => 2, 'failed' => 0, ...]
```

---

### .NET (C#)

**Using `HttpClient`:**

```csharp
using System.Net.Http.Json;
using System.Text.Json;

var baseUrl = Environment.GetEnvironmentVariable("EMAIL_API_BASE_URL") ?? "https://email.yourcompany.com";
var apiKey = Environment.GetEnvironmentVariable("EMAIL_API_KEY") ?? "YOUR_API_KEY";

var payload = new {
    subject = "Welcome",
    body = "<p>Hello from our app.</p>",
    recipients = new[] { "user1@example.com", "user2@example.com" },
    use_design = true
};

using var client = new HttpClient();
client.DefaultRequestHeaders.Add("X-API-Key", apiKey);

var response = await client.PostAsJsonAsync($"{baseUrl.TrimEnd('/')}/api/v1/send", payload);

if (response.IsSuccessStatusCode)
{
    var result = await response.Content.ReadFromJsonAsync<JsonElement>();
    Console.WriteLine($"Sent: {result.GetProperty("sent")}, Failed: {result.GetProperty("failed")}");
}
else
{
    var error = await response.Content.ReadFromJsonAsync<JsonElement>();
    Console.WriteLine($"Error: {error.GetProperty("error").GetString()}");
}
```

**With `HttpRequestMessage` and explicit JSON:**

```csharp
var request = new HttpRequestMessage(HttpMethod.Post, $"{baseUrl.TrimEnd('/')}/api/v1/send");
request.Headers.Add("X-API-Key", apiKey);
request.Content = new StringContent(
    JsonSerializer.Serialize(payload),
    System.Text.Encoding.UTF8,
    "application/json"
);
var response = await client.SendAsync(request);
```

---

### Node.js (JavaScript / TypeScript)

```javascript
const BASE_URL = process.env.EMAIL_API_BASE_URL || 'https://email.yourcompany.com';
const API_KEY = process.env.EMAIL_API_KEY || 'YOUR_API_KEY';

async function sendCampaign(subject, body, recipients, useDesign = true) {
  const res = await fetch(`${BASE_URL.replace(/\/$/, '')}/api/v1/send`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': API_KEY,
    },
    body: JSON.stringify({
      subject,
      body,
      recipients,
      use_design: useDesign,
    }),
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || res.statusText);
  return data;
}

// Usage
sendCampaign(
  'Welcome',
  '<p>Hello!</p>',
  ['a@example.com', 'b@example.com'],
  true
).then((r) => console.log('Sent:', r.sent, 'Failed:', r.failed))
 .catch((e) => console.error(e));
```

---

### Python

```python
import os
import json
import urllib.request

BASE_URL = os.environ.get('EMAIL_API_BASE_URL', 'https://email.yourcompany.com').rstrip('/')
API_KEY = os.environ.get('EMAIL_API_KEY', 'YOUR_API_KEY')

def send_campaign(subject: str, body: str, recipients: list, use_design: bool = True) -> dict:
    payload = {
        'subject': subject,
        'body': body,
        'recipients': recipients,
        'use_design': use_design,
    }
    data = json.dumps(payload).encode('utf-8')
    req = urllib.request.Request(
        f'{BASE_URL}/api/v1/send',
        data=data,
        method='POST',
        headers={
            'Content-Type': 'application/json',
            'X-API-Key': API_KEY,
        },
    )
    with urllib.request.urlopen(req) as resp:
        return json.load(resp)

# Usage
result = send_campaign(
    'Welcome',
    '<p>Hello!</p>',
    ['user1@example.com', 'user2@example.com'],
    use_design=True,
)
print(f"Sent: {result['sent']}, Failed: {result['failed']}")
```

**With `requests`:**

```python
import requests

response = requests.post(
    f'{BASE_URL}/api/v1/send',
    json={
        'subject': 'Welcome',
        'body': '<p>Hello!</p>',
        'recipients': ['a@example.com', 'b@example.com'],
        'use_design': True,
    },
    headers={'X-API-Key': API_KEY},
)
response.raise_for_status()
print(response.json())
```

---

## 4. Checklist for your site

- [ ] Get API key and base URL from the Email Marketing administrator.
- [ ] Store API key in environment or secrets (never in code).
- [ ] Send `POST` to `{BASE_URL}/api/v1/send` with `Content-Type: application/json` and `X-API-Key` (or `Authorization: Bearer ...`).
- [ ] Send JSON with `subject`, `body`, and `recipients`; set `use_design` if you want the central header/footer.
- [ ] Handle 201 (success) and 4xx/5xx (error) and parse the JSON `error` field when present.

---

## 5. Support

For API key issues or base URL, contact the administrator of the Email Marketing system. Campaigns appear in that system’s **Dashboard** and **Logs**; open tracking (if enabled) is also handled by the central system.
