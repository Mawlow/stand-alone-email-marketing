# Standalone Email Marketing

A **standalone** copy of the email marketing feature from the Applyna admin. Use it on another computer without touching the main Applyna project.

## Features

- **Dashboard** – Recent campaigns and quick stats (senders, contacts).
- **Compose** – Write subject and HTML/visual body, choose marketing list, send campaign.
- **Senders** – Add/edit SMTP accounts (e.g. Gmail with app password) for sending.
- **Contacts** – Add, edit, delete marketing contacts; import from CSV.
- **Logs** – View sent/failed/pending per campaign with filters.

Data is stored in a **SQLite** database in the `data/` folder. No Laravel, no Applyna code — just PHP and optional Composer for sending.

## Requirements

- **PHP 7.4+** with extensions: `pdo_sqlite`, `json`, `mbstring`
- Optional: **Composer** (for PHPMailer to send real emails via SMTP)

## Quick start (on any computer)

1. **Copy the whole folder** `standalone-email-marketing` to your computer (e.g. desktop or another project directory).

2. **Optional: install Composer and PHPMailer** (needed to send real emails):
   ```bash
   cd standalone-email-marketing
   composer install
   ```

3. **Run the built-in PHP server** (use the router so all pages work):
   ```bash
   php -S localhost:8080 router.php
   ```

4. Open **http://localhost:8080** in your browser (you must include the host and port — e.g. `http://index.php/?page=compose` will not work). Use clean URLs: **http://localhost:8080/compose**, **http://localhost:8080/contacts**, **http://localhost:8080/logs**, or **http://localhost:8080/index.php?page=compose**.

5. **First use:**
   - Add at least one **Sender** (e.g. Gmail: host `smtp.gmail.com`, port `587`, encryption `TLS`, use an [App Password](https://support.google.com/accounts/answer/185833)).
   - Add **Contacts** (manually or **Import CSV** with columns `email` and optionally `company` / `company_name`).
   - Go to **Compose**, enter subject and body, then **Send campaign**.

If you don’t run `composer install`, the app still works: you can manage senders and contacts and queue campaigns, but sending will be skipped and logs will show “No SMTP sender” until PHPMailer is installed.

## Config

- Copy **`.env.example`** to **`.env`** and set your values (MySQL, app name, optional `TRACKING_BASE_URL` for images in emails).
- Database: MySQL (e.g. XAMPP). Create database `email_marketing` and the app creates tables on first load.

## Security note

This app is intended for **local or trusted use**. Sender passwords are stored base64-encoded in SQLite. Do not expose it on the public internet without proper security (HTTPS, auth, etc.).

## Difference from Applyna

- No Laravel, no queues: campaigns are sent **synchronously** when you click “Send campaign”.
- Recipients are **only** the marketing list (no “registered company users” segment).
- No open-tracking pixel or attachment upload in this standalone version.
- Same UI style (Tailwind, Applyna colors) and flow: Compose → Senders → Contacts → Logs.

Nothing in the main Applyna project is changed or removed; this folder is fully independent.
