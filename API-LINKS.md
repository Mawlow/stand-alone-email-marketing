# API links – where to get them and full list

---

## Base URL

- **Local:** `http://localhost:8080`
- **Your server:** `https://your-domain.com` (or whatever you use)

Replace `{BASE_URL}` below with that.

---

## How to get the API link (in the app)

1. Open your app in the browser (e.g. **http://localhost:8080**).
2. Click **API** in the left sidebar.
3. Scroll to **Your API keys**.
4. Each row has an **API link** column:
   - If you set a **link slug** (in Set defaults → API link slug), the full link is shown there, e.g.  
     `http://localhost:8080/api/v1/send/partners/bayanihan`
   - If no slug is set, the column shows **—** (that partner uses the API key instead).
5. The **general** send endpoint (for API key use) is shown at the bottom under **Endpoint:**  
   `POST {BASE_URL}/api/v1/send`

---

## General API links (all endpoints)

| Use | Method | API link |
|-----|--------|----------|
| **Send campaign (with API key)** | POST | `{BASE_URL}/api/v1/send` |
| **Send campaign (API link – no key)** | POST | `{BASE_URL}/api/v1/send/partners/{slug}` |
| **List senders (API link – no key)** | GET | `{BASE_URL}/api/v1/send/partners/{slug}/senders` |
| List templates | GET | `{BASE_URL}/api/v1/design/templates` |
| List senders (with API key) | GET | `{BASE_URL}/api/v1/senders` |
| List contacts | GET | `{BASE_URL}/api/v1/contacts` |
| Current design | GET | `{BASE_URL}/api/v1/design` |
| Delete template | POST | `{BASE_URL}/api/v1/design/templates/delete` |

**Examples with local base:**

- Send (API key): `POST http://localhost:8080/api/v1/send`
- Send (API link for bayanihan): `POST http://localhost:8080/api/v1/send/partners/bayanihan`
- List templates: `GET http://localhost:8080/api/v1/design/templates`
- List senders: `GET http://localhost:8080/api/v1/senders`

---

## Quick copy-paste (local)

```
POST   http://localhost:8080/api/v1/send
POST   http://localhost:8080/api/v1/send/partners/{slug}
GET    http://localhost:8080/api/v1/design/templates
GET    http://localhost:8080/api/v1/senders
GET    http://localhost:8080/api/v1/contacts
GET    http://localhost:8080/api/v1/design
POST   http://localhost:8080/api/v1/design/templates/delete
```

Replace `{slug}` with the partner’s link slug (e.g. `bayanihan`, `newshomesph`).

---

## Let their system choose the sender (or template)

When sending via the API link, add **sender_id** and/or **template_id** in the JSON body so their system decides which sender/template to use.

1. **Get sender IDs (API link, no key):**  
   **GET** `http://localhost:8080/api/v1/send/partners/newshomesph/senders`  
   → Returns `{"senders": [{"id": 1, "name": "ApplyNa", "email": "..."}, {"id": 5, "name": "Bayanihan.com", ...}]}`

2. **Send with chosen sender:**  
   **POST** `http://localhost:8080/api/v1/send/partners/newshomesph`  
   Body (example – use sender id 2 to send from a different account):
   ```json
   {
     "subject": "Test",
     "body": "<p>Hello</p>",
     "recipients": ["you@example.com"],
     "sender_id": 2
   }
   ```
   Omit `sender_id` to use your default (e.g. Bayanihan). Use `template_id` or `template_name` to choose the template (get IDs from **GET** `{BASE_URL}/api/v1/design/templates`).
