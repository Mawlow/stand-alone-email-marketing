# API test page (optional)

This folder is a **small test page** to verify that the API endpoints respond correctly. It is **not** how real integration works.

- **Real integration:** The other site implements their own code (e.g. cron job, their backend) and **they** call our API from their server. We only provide the API; we do not run their code.
- **This test page:** Only useful to quickly check that our API is reachable and that templates, senders, and send work. It runs on the same server as the API for convenience.

## How to open it

1. Start the app (e.g. `php -S localhost:8080 router.php`).
2. In your browser go to: **http://localhost:8080/api-test/**  
   (or your server URL + `/api-test/`).

## How to use it

1. **API base URL** – Pre-filled with the current host. Change it only if you are calling another server.
2. **API key** – Paste the API key from the main app (API page → Create API key). Required for “Load senders” and “Send”.
3. **Load templates** – Fetches header/footer templates (no key needed). Fills the “Header & footer template” dropdown.
4. **Load senders** – Fetches sender accounts (needs API key). Fills the “Sender” dropdown.
5. **Subject / Body / Recipients** – Edit as needed. Recipients: one email per line.
6. **Send test campaign** – Sends the request to `POST /api/v1/send` and shows the result (success or error).

Use this page to confirm that external sites can list templates, list senders, and send a campaign using your API.
