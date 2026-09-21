# OPD Form Studio — PHP + MySQL + Google Sheets Backup

A desktop-first responsive OPD reception system designed for Hostinger/shared hosting.

## Included
- Admin + Reception login
- Reception field block/unblock and edit permissions
- Patient registration: UHID, name, age/sex, guardian, phone, address, bill, date, panel, doctor department, room and appointment no.
- Uploaded OPD image templates (JPG/PNG/WEBP)
- Per-user drag/drop field positioning with font size and width controls
- Live patient/template preview
- A4 browser print with the uploaded template as the background
- Patient history and filters: today, last 7 days, last month, last year, custom dates, search
- Last-patient dashboard
- MySQL primary database
- Google Sheets mirror/backup with retry queue
- Audit trail for patient creation
- Supplied OPD image included as a sample template (replace it with a template you are authorized to use in production)

## Requirements
- PHP 8.1+
- MySQL 5.7+/8.x or MariaDB 10.4+
- PHP PDO MySQL extension
- PHP cURL recommended
- HTTPS recommended

## Hostinger installation
1. Create a MySQL database in Hostinger hPanel and note DB host/name/user/password.
2. Upload all project files into your domain/subdomain document root.
3. Copy `.env.example` to `.env` and fill DB credentials, APP_URL and a long random SETUP_KEY.
4. Ensure `uploads/templates` is writable (normally 755; some setups may require 775).
5. Visit `https://YOURDOMAIN/setup.php?key=YOUR_SETUP_KEY`.
6. Create the first admin account.
7. Sign in and immediately delete or rename `setup.php`.
8. In Admin → Users & Permissions create reception users and choose which fields are visible/editable.
9. In Admin → Templates upload a clean A4 image. Open Layout Editor, drag fields to the correct blank locations, adjust font size/width and save.

## Google Sheets backup
1. Create a Google Sheet and copy its spreadsheet ID from the URL.
2. Open Extensions → Apps Script.
3. Paste the contents of `apps-script/Code.gs`.
4. Put your spreadsheet ID into `SPREADSHEET_ID`.
5. Set a long random `SECRET_TOKEN`.
6. Deploy → New deployment → Web app. Execute as yourself. Set access to the option that lets the deployed endpoint receive requests from your server.
7. Copy the deployment URL ending in `/exec`.
8. In OPD Studio → Admin → Backup Settings, paste the Web App URL and the same secret token.
9. Save a test patient. A row should appear in the `Patients` sheet. Retries can be run from Backup Settings.
10. Optional but recommended: add a Hostinger Cron Job every 10–15 minutes that runs `php /full/path/to/cron/sync_sheets.php`. This automatically retries any failed/pending backup. Set `CRON_KEY` in `.env` as a second secret even though CLI cron does not need it.

## Printing notes
- Browser print: A4, scale 100%, margins None, background graphics ON.
- For exact placement, upload a straight A4 scan/image with no perspective distortion.
- The sample image is 960×1280 and the included starter coordinates are tuned approximately to its printed blank fields. Use Layout Editor for final alignment on your printer.

## Privacy / production note
Patient details are personal data. Use HTTPS, strong passwords, least-privilege reception permissions, limited Google Sheet access, regular database backups, and a suitable retention/access policy. This starter is functional software but is not a legal/compliance certification.
