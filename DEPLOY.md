# AiServe ESG OS — Hostinger Deployment Guide

## Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- Apache with mod_rewrite enabled
- Hostinger Business Hosting or VPS

## Step 1: Create MySQL Database (Hostinger hPanel)
1. Go to **hPanel → Databases → MySQL Databases**
2. Create a new database, e.g. `u123456789_esgos`
3. Create a database user and password
4. Add the user to the database with **All Privileges**
5. Note down: hostname (usually `localhost`), database name, username, password

## Step 2: Update Config
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_esgos');
define('DB_USER', 'u123456789_esgos');
define('DB_PASS', 'your_strong_password');
```

Edit `config/app.php`:
```php
define('APP_URL', 'https://yourdomain.com');  // No trailing slash
```

## Step 3: Import Database Schema
1. Go to **hPanel → phpMyAdmin**
2. Select your database
3. Click **Import** tab
4. Upload `db/schema.sql`
5. Click **Go**

## Step 4: Upload Files
Upload ALL files to `public_html/` (or a subdirectory like `public_html/esg/`)

**If installed in subdirectory**, update `.htaccess`:
```apache
RewriteBase /esg/
```

And update `config/app.php`:
```php
define('APP_URL', 'https://yourdomain.com/esg');
```

## Step 5: Set File Permissions
```bash
chmod 755 public_html/
chmod 644 public_html/*.php
chmod 644 public_html/.htaccess
```

## Step 6: Test
1. Visit `https://yourdomain.com/register`
2. Register as **Consultant** (to manage multiple companies)
3. Complete the onboarding wizard
4. Start entering ESG data

## Folder Structure
```
public_html/
├── index.php          ← Main router
├── .htaccess          ← URL rewriting
├── config/            ← Configuration (protected)
├── src/               ← PHP classes (protected)
├── includes/          ← Layout files (protected)
├── pages/             ← Page templates
├── assets/            ← CSS, JS, images (public)
└── db/                ← SQL schema (protected)
```

## Security Notes
- `.htaccess` blocks direct access to `config/`, `src/`, `includes/`, `db/`
- Never expose `config/database.php` publicly
- Use HTTPS in production (`APP_URL` should start with `https://`)
- Change `display_errors` to `Off` in production

## Support
ESG Framework Data: Bursa Malaysia Sustainability Reporting Guide 2nd Edition + GRI Standards 2021
