# OPD Form Studio - Hostinger Deployment Guide 🚀

This guide explains step-by-step how to deploy **OPD Form Studio** onto **Hostinger Shared Web Hosting / Cloud Hosting**.

---

## 📋 Prerequisites on Hostinger

- Hostinger account with active Web Hosting (Shared / Cloud / WordPress).
- Access to **Hostinger hPanel**.
- A connected domain name (e.g., `yourclinic.com` or subdomain `opd.yourclinic.com`).

---

## 🛠️ Step-by-Step Deployment Process

### Step 1: Create MySQL Database in Hostinger hPanel

1. Log into [Hostinger hPanel](https://hpanel.hostinger.com).
2. Go to **Databases** → **Management** (or **MySQL Databases**).
3. Create a new database:
   - **Database Name**: e.g., `opd_studio` (full name will be `u123456789_opd_studio`)
   - **Database Username**: e.g., `opd_user` (full name will be `u123456789_opd_user`)
   - **Password**: Generate a strong password and **copy it down**.
4. Click **Create**.

---

### Step 2: Import Database Schema

1. Next to your newly created database in hPanel, click **Enter phpMyAdmin**.
2. In phpMyAdmin, click on your database name in the left sidebar.
3. Click the **Import** tab in the top navigation.
4. Click **Choose File** and select `database/schema.sql` from the project repository.
5. Click **Import** (or **Go** at the bottom).
6. All required tables (`users`, `patients`, `templates`, `template_layouts`, `user_field_permissions`, `settings`, `sheet_sync_queue`, `audit_logs`, `notifications`, `notification_reads`) will be created automatically.

---

### Step 3: Upload Files to Hostinger (`public_html`)

You can upload files using **Method A (Git)** or **Method B (File Manager / ZIP)**.

#### Method A: Using Git (Recommended)
1. In hPanel, go to **Advanced** → **Git**.
2. Repository URL: `https://github.com/pkfiziks742004/opd-form-studio.git`
3. Branch: `main`
4. Install Path: `/public_html` (or your subdomain folder).
5. Click **Create** / **Deploy**.

#### Method B: Using File Manager
1. In hPanel, go to **Files** → **File Manager**.
2. Navigate to `public_html/`.
3. Upload the project files (or upload project ZIP and click **Extract**).

---

### Step 4: Configure Production `.env` File

1. Inside `public_html/` on Hostinger File Manager:
2. Find the file `.env.example`.
3. Make a copy of it or create a new file named `.env`.
4. Fill in your actual Hostinger credentials:

```ini
APP_ENV=production
APP_NAME="OPD Form Studio"
APP_URL=https://yourdomain.com
APP_TIMEZONE=Asia/Kolkata

DB_HOST=localhost
DB_PORT=3306
DB_NAME=u123456789_opd_studio
DB_USER=u123456789_opd_user
DB_PASS=your_copied_database_password

SETUP_KEY=my-secure-setup-key-2026
CRON_KEY=my-secure-cron-key-2026
```

5. Save the file.
> ⚠️ **Note**: Make sure `.env` file starts with a dot (`.`). The root `.htaccess` already blocks public visitors from downloading or viewing this file.

---

### Step 5: Check Permissions & Initial Login

1. Ensure the `uploads/` and `uploads/templates/` directories have write permissions:
   - Permissions: `755` (or `775`).
2. Open your website in a browser:
   `https://yourdomain.com/login.php`
3. If you already have an admin user in the database, login with:
   - **Username**: `admin`
   - **Password**: `12345678` (or the one you set during setup)
4. If this is a fresh setup with no users yet:
   - Visit: `https://yourdomain.com/setup.php?key=YOUR_SETUP_KEY`
   - Enter your Administrator Name, Username, and Password.
   - Once setup finishes, delete `setup.php` from your server for security.

---

### Step 6: Setup Hostinger Cron Job (Optional - For Google Sheets Sync)

If you use background Google Sheets synchronization:

1. In hPanel, go to **Advanced** → **Cron Jobs**.
2. Choose **Custom**.
3. Set schedule: Every 5 minutes (`*/5 * * * *`).
4. Command:
   ```bash
   /usr/bin/php /home/u123456789/public_html/cron/sync_sheets.php
   ```
   *(Replace `/home/u123456789/public_html` with your actual hosting home path shown in hPanel).*
   
   OR use URL ping:
   ```bash
   curl -s "https://yourdomain.com/cron/sync_sheets.php?key=YOUR_CRON_KEY" > /dev/null 2>&1
   ```

---

## 🔒 Security Checklist for Hostinger

- [x] `.htaccess` prevents directory listing (`Options -Indexes`).
- [x] Direct access to `.env`, `.git`, `.sql`, and `.log` files is blocked.
- [x] Internal folders (`includes/`, `database/`, `apps-script/`) cannot be directly executed via URL.
- [x] PHP script execution inside `uploads/` is disabled.
- [x] CSRF protection and Prepared PDO Statements active across all endpoints.
- [x] Notifications automatically expire after 24 hours to prevent database bloat.
