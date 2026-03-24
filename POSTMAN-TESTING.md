# Testing the Email Marketing API with Postman

Use this guide to call the API from **Postman** (or any HTTP client).

---

## Template: Example API key and ready-to-use requests

**Base URL:** `http://localhost:8080`  
**Example API key (use in `X-API-Key` header):**

```
065043d07cd2656d5b7edf058a0c1c69e648861aec934b911573b864b2ba8854
```

### Quick Postman setup

1. **Environment (optional)**  
   - Create a variable `base_url` = `http://localhost:8080`  
   - Create a variable `api_key` = `065043d07cd2656d5b7edf058a0c1c69e648861aec934b911573b864b2ba8854`

2. **Send campaign (POST) – copy this**

   - **Method:** `POST`  
   - **URL:** `http://localhost:8080/api/v1/send`  
   - **Headers:**

     | Key             | Value                                                                 |
     |-----------------|-----------------------------------------------------------------------|
     | Content-Type    | application/json                                                      |
     | X-API-Key       | 065043d07cd2656d5b7edf058a0c1c69e648861aec934b911573b864b2ba8854    |

   - **Body (raw, JSON):**

   ```json
   {
     "subject": "Test",
     "body": "<p>Hello</p>",
     "recipients": ["marlochinito2004@gmail.com"],
     "use_design": true
   }
   ```

3. **List senders (GET)**

   - **Method:** `GET`  
   - **URL:** `http://localhost:8080/api/v1/senders`  
   - **Header:** `X-API-Key`: `065043d07cd2656d5b7edf058a0c1c69e648861aec934b911573b864b2ba8854`

4. **List contacts (GET)**

   - **Method:** `GET`  
   - **URL:** `http://localhost:8080/api/v1/contacts`  
   - **Header:** `X-API-Key`: `065043d07cd2656d5b7edf058a0c1c69e648861aec934b911573b864b2ba8854`

---

## 1. Prerequisites

1. **Start the app** (from the project folder):
   ```bash
   php -S localhost:8080 router.php
   ```

2. **Get an API key** (needed for senders and send):
   - Open **http://localhost:8080** in your browser.
   - Go to **API** in the sidebar.
   - Click **Create key**, enter a name, then **copy the key** (it is shown only once).

3. **Base URL** for all requests: `http://localhost:8080`  
   (Replace with your server URL if different.)

---

## 2. All URLs to test in Postman (quick list)

| # | Method | URL | Auth |
|---|--------|-----|------|
| 1 | GET | `http://localhost:8080/api/v1/design/templates` | None |
| 2 | GET | `http://localhost:8080/api/v1/design` | None |
| 3 | GET | `http://localhost:8080/api/v1/senders` | `X-API-Key` or `Authorization: Bearer <key>` |
| 4 | GET | `http://localhost:8080/api/v1/contacts` | `X-API-Key` or `Authorization: Bearer <key>` |
| 5 | POST | `http://localhost:8080/api/v1/send` | `X-API-Key` + `Content-Type: application/json`; body: JSON |
| 6 | POST | `http://localhost:8080/api/v1/design/templates/delete` | None; body: `{"id": 1}` |
| 7 | GET | `http://localhost:8080/api/v1/send/partners/{slug}/senders` | API link — no key. List senders. |
| 8 | POST | `http://localhost:8080/api/v1/send/partners/{slug}` | API link — no key. Send; body can include sender_id, template_id. |

Replace `{slug}` with your partner slug (e.g. `newshomesph`, `bayanihan`).

---

## 3. Complete Postman tests (copy-paste)

Replace `your-api-key` with your API key and `newshomesph` with your partner slug if different.

---

### Test 1: List templates (no auth)

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/design/templates`
- **Headers:** None
- **Body:** None

---

### Test 2: List current design (no auth)

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/design`
- **Headers:** None
- **Body:** None

---

### Test 3: List senders (API key)

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/senders`
- **Headers:** `X-API-Key`: `your-api-key`
- **Body:** None

---

### Test 4: List contacts (API key)

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/contacts`
- **Headers:** `X-API-Key`: `your-api-key`
- **Body:** None

---

### Test 5: List senders (API link – no key)

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/send/partners/newshomesph/senders`
- **Headers:** None
- **Body:** None

---

### Test 6: Send campaign (API key)

- **Method:** `POST`
- **URL:** `http://localhost:8080/api/v1/send`
- **Headers:** `Content-Type`: `application/json`, `X-API-Key`: `your-api-key`
- **Body (raw → JSON):**

```json
{
  "subject": "Test",
  "body": "<p>Hello</p>",
  "recipients": ["marlochinito2004@gmail.com"],
  "use_design": true
}
```

---

### Test 7: Send campaign (API key) with chosen sender and template

- **Method:** `POST`
- **URL:** `http://localhost:8080/api/v1/send`
- **Headers:** `Content-Type`: `application/json`, `X-API-Key`: `your-api-key`
- **Body (raw → JSON):**

```json
{
  "subject": "Test",
  "body": "<p>Hello</p>",
  "recipients": ["marlochinito2004@gmail.com"],
  "sender_id": 4,
  "template_id": 1
}
```

---

### Test 8: Send campaign (API link – no key)

- **Method:** `POST`
- **URL:** `http://localhost:8080/api/v1/send/partners/newshomesph`
- **Headers:** `Content-Type`: `application/json`
- **Body (raw → JSON):**

```json
{
  "subject": "Test",
  "body": "<p>Hello</p>",
  "recipients": ["marlochinito2004@gmail.com"]
}
```

---

### Test 9: Send campaign (API link) with chosen sender and template

- **Method:** `POST`
- **URL:** `http://localhost:8080/api/v1/send/partners/newshomesph`
- **Headers:** `Content-Type`: `application/json`
- **Body (raw → JSON):**

```json
{
  "subject": "Test",
  "body": "<p>Hello</p>",
  "recipients": ["marlochinito2004@gmail.com"],
  "sender_id": 4,
  "template_id": 1
}
```

---

### Test 10: Delete a template

- **Method:** `POST`
- **URL:** `http://localhost:8080/api/v1/design/templates/delete`
- **Headers:** `Content-Type`: `application/json`
- **Body (raw → JSON):**

```json
{
  "id": 1
}
```

---

## 4. Endpoints (details)

### 4.1 List templates (no API key)

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/design/templates`
- **Headers:** None required.
- **Body:** None.

**Postman:** New Request → GET → paste URL → Send. You should get JSON with a `templates` array.

---

### 4.2 List senders (requires API key)

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/senders`
- **Headers:** `X-API-Key`: `your-api-key-here` or `Authorization`: `Bearer your-api-key-here`
- **Body:** None.

---

### 4.3 List contacts (requires API key)

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/contacts`
- **Headers:** `X-API-Key` or `Authorization: Bearer <key>`
- **Body:** None.

---

### 4.4 Send a campaign (requires API key)

- **Method:** `POST`
- **URL:** `http://localhost:8080/api/v1/send`
- **Headers:** `Content-Type`: `application/json`, `X-API-Key`: `your-api-key`
- **Body:** See Test 6 or Test 7 above.

**Optional body fields:** `template_id`, `template_name`, `sender_id`, `sender_ids`, `use_design`.

**Success (201):** `{"success": true, "campaign_id": 1, "total_recipients": 1, "sent": 1, "failed": 0}`

---

### 4.5 GET current design

- **Method:** `GET`
- **URL:** `http://localhost:8080/api/v1/design`
- **Headers:** None.
- **Body:** None.

---

## 5. Quick checklist

| Step | Action |
|------|--------|
| 1 | Run `php -S localhost:8080 router.php`. |
| 2 | Create an API key in the app (API page → Create key). |
| 3 | GET `http://localhost:8080/api/v1/design/templates` (no key). |
| 4 | GET `http://localhost:8080/api/v1/senders` with header `X-API-Key: <key>`. |
| 5 | GET `http://localhost:8080/api/v1/contacts` with header `X-API-Key: <key>`. |
| 6 | GET `http://localhost:8080/api/v1/send/partners/newshomesph/senders` (API link, no key). |
| 7 | POST `http://localhost:8080/api/v1/send` or POST `http://localhost:8080/api/v1/send/partners/newshomesph` with JSON body (subject, body, recipients, optional sender_id, template_id). |

For more integration details (PHP, Node, cURL, etc.), see **API-README.md** and **HOW-TO-IMPLEMENT.md**.
