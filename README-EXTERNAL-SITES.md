# What to Do After Receiving the Email Marketing API

This guide is for **your website** after you have received an **API key** and **base URL** from the Email Marketing system.

**Important:** We only provide the API. **You** implement the code that calls it—for example from your own email marketing page, a cron job, or your backend. We do not run your site or your cron; we only respond to HTTP requests to our API (send campaign, list templates, list senders). Your server (or cron) calls our API; we send the emails and return the result.

---

## 1. Store your credentials securely

You should have received:

| What       | Example                    | Where to store it                          |
|-----------|----------------------------|--------------------------------------------|
| **API key** | Long string (e.g. `a1b2c3...`) | Environment variable or secrets manager. **Do not** put it in code or commit it to git. |
| **Base URL** | `https://email.yourcompany.com` or `http://localhost:8080` | Config or env (e.g. `EMAIL_API_BASE_URL`). |

If you did not receive these, contact the administrator of the Email Marketing system.

---

## 2. Build your “Send campaign” page

Your page should collect the following and then call our API.

### Step 1 – Subject

- Add a **Subject** field (required).
- Send it in the API as `subject`.

### Step 2 – Header & footer template

- Call **GET** `{BASE_URL}/api/v1/design/templates` (no API key needed).
- You will get a list of templates, e.g. `[{ "id": 1, "name": "Bayanihan 2" }, ...]`.
- Show a **dropdown** (e.g. “Header & footer template”) using `name` for the label and `id` (or `name`) for the value.
- When sending the campaign, pass either:
  - `template_id`: the chosen `id`, or  
  - `template_name`: the chosen `name`.

### Step 3 – Body content

- Add a **Body** field (required): rich text or HTML where the user writes the main email content.
- This is the middle part of the email. Our system will place it between the header and footer of the selected template.
- Send it in the API as `body` (HTML string).

### Step 4 – Recipients

- Add a way to enter or select **recipients** (email addresses).
- Accept one or more emails (e.g. list, textarea, or select from contacts).
- Send them in the API as `recipients`: an array of strings, e.g. `["a@example.com", "b@example.com"]`.

### Step 5 – Send the campaign

- When the user clicks “Send” (or “Schedule” if you add that later), call our API with the collected data.

**Request:**

- **Method:** `POST`
- **URL:** `{BASE_URL}/api/v1/send`
- **Headers:**
  - `Content-Type: application/json`
  - `X-API-Key: {your-api-key}` (or `Authorization: Bearer {your-api-key}`)
- **Body (JSON):**

```json
{
  "subject": "Your campaign subject",
  "body": "<p>Your HTML content here.</p>",
  "recipients": ["user1@example.com", "user2@example.com"],
  "template_id": 1
}
```

Use `template_id` (number) or `template_name` (string, e.g. `"Bayanihan 2"`) depending on what you stored from the templates list.

**On success (HTTP 201)** you get something like:

```json
{
  "success": true,
  "campaign_id": 123,
  "total_recipients": 10,
  "sent": 10,
  "failed": 0
}
```

**On error (4xx/5xx)** you get:

```json
{
  "error": "Human-readable message"
}
```

Show that message to the user or log it.

---

## 3. Optional: choose which sender(s) send the campaign

Your site **can** let the user choose which “From” address (sender) is used:

- Call **GET** `{BASE_URL}/api/v1/senders` **with** your API key in the header.
- You get a list of senders, e.g. `[{ "id": 1, "name": "Support", "email": "support@example.com" }, ...]`.
- Add a dropdown or multi-select (e.g. “Send from:”) and send:
  - **One sender:** `sender_id: 1` in the send request.
  - **Several senders (round-robin):** `sender_ids: [1, 2]` in the send request.
- If you omit both, our system uses **all** active senders (round-robin).

---

## 4. Quick reference

| What you want to do        | Method | URL                                      | Auth      |
|----------------------------|--------|------------------------------------------|-----------|
| List header/footer templates | GET    | `{BASE_URL}/api/v1/design/templates`     | None      |
| List senders (From addresses) | GET    | `{BASE_URL}/api/v1/senders`               | API key   |
| Send campaign              | POST   | `{BASE_URL}/api/v1/send`                 | API key   |

---

## 5. Need more detail?

For full request/response formats, error codes, and examples in PHP, Laravel, .NET, Node, and Python, see **API-INTEGRATION.md** in this project (or ask the administrator for the integration guide).

---

## Summary checklist

- [ ] Store API key and base URL securely (env/config).
- [ ] Add Subject field.
- [ ] Call GET `/api/v1/design/templates` and add a template dropdown.
- [ ] Add Body field (HTML content).
- [ ] Add Recipients (list of emails).
- [ ] On Send, call POST `/api/v1/send` with `subject`, `body`, `recipients`, and `template_id` (or `template_name`).
- [ ] (Optional) Call GET `/api/v1/senders` and let the user select which sender(s) send the campaign; send `sender_id` or `sender_ids` in the send request.
