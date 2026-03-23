# How to Implement the Email Marketing API (One-Page Guide)

This is the **single README** for external sites: how you implement the integration on your side. We only provide the API; you implement the code that calls it (your backend, cron job, or form handler).

---

## 1. What you get from us

- **API key** – A long string. Store it in an environment variable or secrets manager. **Do not** commit it to code. Use it in the header `X-API-Key` (or `Authorization: Bearer <key>`) when calling the API.
- **API link (optional)** – If we set a **link slug** for your key (e.g. `bayanihan`), you get a single URL: `POST {BASE_URL}/api/v1/send/partners/bayanihan`. You call that URL with **no API key header**; only the JSON body (subject, body, recipients, optional sender_id, template_id). Use this when you prefer one URL instead of storing a key.
- **Base URL** – The root URL of our Email Marketing app (e.g. `https://email.yourcompany.com` or `http://localhost:8080`).

We do **not** run your website or your cron jobs. **You** call our API from your server (PHP, Node, .NET, cron script, etc.).

---

## 2. Endpoints (quick reference)

| What you want to do            | Method | URL                                            | Auth      |
|--------------------------------|--------|------------------------------------------------|-----------|
| List header/footer templates    | GET    | `{BASE_URL}/api/v1/design/templates`          | None      |
| List senders (From addresses)  | GET    | `{BASE_URL}/api/v1/senders`                    | API key   |
| List contacts (recipients)     | GET    | `{BASE_URL}/api/v1/contacts`                   | API key   |
| Send a campaign (with key)     | POST   | `{BASE_URL}/api/v1/send`                       | API key   |
| Send a campaign (API link)     | POST   | `{BASE_URL}/api/v1/send/partners/{slug}`       | None      |
| List senders (API link)        | GET    | `{BASE_URL}/api/v1/send/partners/{slug}/senders` | None    |

Replace `{slug}` with the link slug we gave you (e.g. `bayanihan`, `newshomesph`).

**Headers when using API key:**

- `Content-Type: application/json` (for POST)
- `X-API-Key: {your-api-key}` **or** `Authorization: Bearer {your-api-key}`

**When using API link:** no API key header; only `Content-Type: application/json` for POST. The URL itself identifies the partner.

---

## 3. How you implement it

### Option A: Form on your site (user sends a campaign)

1. **Your form** collects: subject, body (HTML), recipients (list of emails), and optionally “header/footer template” and “sender”.
2. **Load templates (no key):**  
   `GET {BASE_URL}/api/v1/design/templates`  
   Use the `templates` array to build a dropdown (use `id` or `name`).
3. **Load senders (with key):**  
   `GET {BASE_URL}/api/v1/senders`  
   Header: `X-API-Key: {key}`  
   Use the `senders` array to build a “From” dropdown (use `id`).
4. **On submit:** Your backend calls  
   `POST {BASE_URL}/api/v1/send`  
   with JSON body (see below). Use the chosen `template_id` (or `template_name`) and optional `sender_id` (or `sender_ids`).

5. **Optional – list contacts (recipients):**  
   `GET {BASE_URL}/api/v1/contacts` with header `X-API-Key: {key}`. Use the `contacts` array (e.g. `email`, `company_name`) to build recipient lists or dropdowns.

### Option B: Cron job (e.g. daily newsletter)

1. Your cron runs a script on your server (PHP, Node, Python, or a shell script with `curl`).
2. The script builds: subject, body HTML, list of recipient emails.
3. **If your API key has default template and senders set** (in our app: API → Set defaults for that key), you only need to send `subject`, `body`, and `recipients`. Our system will use that key’s template and senders automatically.
4. Otherwise, optionally send `template_id` / `template_name` and `sender_id` / `sender_ids` in the body.
5. The script sends one HTTP request:  
   `POST {BASE_URL}/api/v1/send`  
   with headers `Content-Type: application/json` and `X-API-Key: {key}`, and body as below.
6. Our system sends the emails and returns success/failure. Your script can log the response or retry on error.

### Option C: API link (no API key header)

If we gave you an **API link** (e.g. `POST {BASE_URL}/api/v1/send/partners/bayanihan`):

1. **List senders (no key):**  
   `GET {BASE_URL}/api/v1/send/partners/bayanihan/senders`  
   Returns the same `senders` array. Use it to choose `sender_id` in the body.
2. **Send:**  
   `POST {BASE_URL}/api/v1/send/partners/bayanihan`  
   Headers: only `Content-Type: application/json`. Body: same JSON (subject, body, recipients, optional `template_id`, `sender_id`). No `X-API-Key` header.

Useful when you want one URL and no secret to store; the slug in the URL identifies the partner.

---

## 4. Send request (POST /api/v1/send or POST .../partners/{slug})

**Body (JSON):**

| Field           | Type    | Required | Description |
|-----------------|---------|----------|-------------|
| `subject`       | string  | Yes      | Email subject line |
| `body`          | string  | Yes      | Email body (HTML). Placed between header and footer when you use a template. |
| `recipients`    | array   | Yes      | List of emails: `["a@example.com", "b@example.com"]` or `[{"email":"a@example.com"}]` |
| `template_id`   | integer | No       | Header/footer template (from GET templates). Prefer this when you have the id. |
| `template_name`| string  | No       | Or use template by name, e.g. `"Bayanihan 2"`. |
| `use_design`    | boolean | No       | If `true` and no template given, we use the default design. Default: `false`. |
| `sender_id`     | integer | No       | Use only this sender (from GET senders). |
| `sender_ids`    | array   | No       | Use only these sender ids, e.g. `[1, 2]`. Round-robin. |

**Example body:**

```json
{
  "subject": "Monthly update",
  "body": "<p>Hello,</p><p>Here is your summary.</p>",
  "recipients": ["user1@example.com", "user2@example.com"],
  "template_id": 1
}
```

**Success (HTTP 201):**

```json
{
  "success": true,
  "campaign_id": 123,
  "total_recipients": 10,
  "sent": 10,
  "failed": 0
}
```

**Error (4xx/5xx):**

```json
{
  "error": "Human-readable message"
}
```

Common errors: **401** missing/invalid API key; **400** missing subject/body, invalid recipients, or template not found.

---

## 5. Cron job: minimal request (using API key defaults)

If we have set **default template** and **default senders** for your API key (e.g. for “Bayanihan site”), your cron only needs to send **subject**, **body**, and **recipients**. No `template_id` or `sender_id` in the request.

**Example: shell script for cron (curl)**

```bash
#!/bin/bash
# Run daily: 0 9 * * * /path/to/send-newsletter.sh

BASE_URL="${EMAIL_API_BASE_URL:-https://email.yourcompany.com}"
API_KEY="${EMAIL_API_KEY}"

SUBJECT="Your daily digest"
BODY="<p>Hello,</p><p>Here is your update.</p>"
RECIPIENTS='["user1@example.com","user2@example.com"]'

curl -s -X POST "${BASE_URL}/api/v1/send" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: ${API_KEY}" \
  -d "{\"subject\":\"${SUBJECT}\",\"body\":\"${BODY}\",\"recipients\":${RECIPIENTS}}"
```

Set `EMAIL_API_BASE_URL` and `EMAIL_API_KEY` in your environment or at the top of the script. If subject or body contain quotes, build the JSON with a tool like `jq` or from a file to avoid shell escaping issues. The response is JSON (check for `"success": true` or `"error"`).

**Example: PHP script for cron**

```php
<?php
// Run daily via cron: 0 9 * * * php /path/to/send-newsletter.php

$baseUrl = rtrim(getenv('EMAIL_API_BASE_URL') ?: 'https://email.yourcompany.com', '/');
$apiKey  = getenv('EMAIL_API_KEY');
$subject = 'Your daily digest';
$body    = '<p>Hello,</p><p>Here is your update.</p>';
$recipients = ['user1@example.com', 'user2@example.com'];

$ch = curl_init($baseUrl . '/api/v1/send');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(compact('subject', 'body', 'recipients')),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-API-Key: ' . $apiKey],
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($code === 201 && !empty($data['success'])) {
    exit(0); // success
}
error_log('Email API error: ' . ($data['error'] ?? $response));
exit(1);
```

No `template_id` or `sender_id` in the payload: our system uses the defaults configured for your API key.

---

## 6. Code examples (how they implemented it)

Replace `BASE_URL` and `API_KEY` with your values (e.g. from env).

### PHP (form handler or cron script)

```php
<?php
$baseUrl = rtrim(getenv('EMAIL_API_BASE_URL') ?: 'https://email.yourcompany.com', '/');
$apiKey  = getenv('EMAIL_API_KEY') ?: 'YOUR_API_KEY';

// Optional: load templates (no key) for a dropdown
$templates = json_decode(file_get_contents($baseUrl . '/api/v1/design/templates'), true)['templates'] ?? [];

// Optional: load senders (with key) for a dropdown
$ctx = stream_context_create([
    'http' => [
        'header' => "X-API-Key: $apiKey\r\n"
    ]
]);
$senders = json_decode(file_get_contents($baseUrl . '/api/v1/senders', false, $ctx), true)['senders'] ?? [];

// Send campaign
$payload = [
    'subject'   => 'Your subject',
    'body'      => '<p>Your HTML content.</p>',
    'recipients'=> ['user@example.com'],
    'template_id' => 1,        // or 'template_name' => 'Bayanihan 2'
    'sender_id'  => 1,        // optional
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

### Node.js (form handler or cron script)

```javascript
const BASE_URL = (process.env.EMAIL_API_BASE_URL || 'https://email.yourcompany.com').replace(/\/$/, '');
const API_KEY = process.env.EMAIL_API_KEY || 'YOUR_API_KEY';

// Optional: load templates (no key)
const templatesRes = await fetch(`${BASE_URL}/api/v1/design/templates`);
const { templates } = await templatesRes.json();

// Optional: load senders (with key)
const sendersRes = await fetch(`${BASE_URL}/api/v1/senders`, {
  headers: { 'X-API-Key': API_KEY },
});
const { senders } = await sendersRes.json();

// Send campaign
const payload = {
  subject: 'Your subject',
  body: '<p>Your HTML content.</p>',
  recipients: ['user@example.com'],
  template_id: 1,
  sender_id: 1,  // optional
};

const res = await fetch(`${BASE_URL}/api/v1/send`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': API_KEY,
  },
  body: JSON.stringify(payload),
});

const data = await res.json();
if (res.ok && data.success) {
  console.log('Sent:', data.sent, 'Failed:', data.failed);
} else {
  console.error('Error:', data.error || data);
}
```

### Cron example (bash + curl)

```bash
#!/bin/bash
BASE_URL="https://email.yourcompany.com"
API_KEY="your-api-key"

curl -s -X POST "$BASE_URL/api/v1/send" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d '{
    "subject": "Daily digest",
    "body": "<p>Here is your daily update.</p>",
    "recipients": ["user@example.com"],
    "template_id": 1
  }'
```

---

## 7. Checklist (how they implemented it)

- [ ] Store API key and base URL in env/config (never in code).
- [ ] **List templates:** GET `/api/v1/design/templates` (no key) → use `id` / `name` for a dropdown or config.
- [ ] **List senders (optional):** GET `/api/v1/senders` with `X-API-Key` (or GET `/api/v1/send/partners/{slug}/senders` if using API link, no key) → use `id` for “From” dropdown or config.
- [ ] **List contacts (optional):** GET `/api/v1/contacts` with `X-API-Key` → use for recipient lists.
- [ ] **Send campaign:** POST `/api/v1/send` with `X-API-Key`, or POST `/api/v1/send/partners/{slug}` with no key; body: `subject`, `body`, `recipients`, and optionally `template_id` or `template_name`, `sender_id` or `sender_ids`.
- [ ] Handle 201 + JSON success and 4xx/5xx + `error` message in your code or cron.

This single file is the implementation guide: we provide the API; you implement the calls (form, cron, or script) as above.
