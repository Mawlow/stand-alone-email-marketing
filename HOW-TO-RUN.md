# How to run the Email Marketing system

## 1. Start XAMPP

- Open **XAMPP Control Panel**
- Start **Apache** (optional, only if you want to use it)
- Start **MySQL**

## 2. Create the database (first time only)

- Open **http://localhost/phpmyadmin** in your browser
- Click **New** to create a database
- Database name: **`email_marketing`**
- Collation: **utf8mb4_unicode_ci**
- Click **Create**

The app will create the tables automatically on first load.  
(Or import `mysql_schema.sql` in phpMyAdmin if you prefer.)

## 3. Configure the app

Use a **`.env`** file (recommended): copy **`.env.example`** to **`.env`** and set:

- **DB_MYSQL_HOST**: `127.0.0.1`
- **DB_MYSQL_DATABASE**: `email_marketing`
- **DB_MYSQL_USERNAME**: `root`
- **DB_MYSQL_PASSWORD**: leave empty, or set if you added a root password

Copy **`.env.example`** to **`.env`** if you don’t have one yet.

## 4. Run the app

### Option A – PHP built-in server (easiest)

In a terminal, go to the project folder and run:

```bash
cd c:\Users\marlo\OneDrive\Desktop\standalone-email-marketing
php -S localhost:8080 router.php
```

Then open in your browser: **http://localhost:8080**

### Option B – XAMPP Apache

1. Copy the project folder into XAMPP’s **htdocs** (e.g. `C:\xampp\htdocs\standalone-email-marketing`)
2. Start **Apache** in XAMPP
3. Open: **http://localhost/standalone-email-marketing**

*(If you use a Virtual Host, use that URL instead.)*

---

**Summary:** Start MySQL in XAMPP → create database `email_marketing` in phpMyAdmin → copy `.env.example` to `.env` and set DB_* → run `php -S localhost:8080 router.php` and visit **http://localhost:8080**.
