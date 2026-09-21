<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_admin();
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/sheets.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['save_numbering'])) {
        $uhidPrefix = strtoupper(trim($_POST['uhid_prefix'] ?? 'MLH'));
        $billPrefix = strtoupper(trim($_POST['bill_prefix'] ?? 'MOB'));
        $uhidStart = max(0, (int)($_POST['uhid_start_number'] ?? 0));
        $billStart = max(0, (int)($_POST['bill_start_number'] ?? 0));

        set_setting('uhid_prefix', $uhidPrefix ?: 'MLH');
        set_setting('bill_prefix', $billPrefix ?: 'MOB');
        set_setting('uhid_start_number', (string)$uhidStart);
        set_setting('bill_start_number', (string)$billStart);

        flash('success', 'OPD numbering and prefix settings saved successfully.');
        header('Location: settings.php');
        exit;
    }

    if (isset($_POST['add_department'])) {
        $dept = trim($_POST['department_name'] ?? '');
        if ($dept !== '') {
            $depts = get_doctor_departments();
            if (!in_array($dept, $depts, true)) {
                $depts[] = $dept;
                save_doctor_departments($depts);
                flash('success', "Doctor department '{$dept}' added successfully.");
            } else {
                flash('error', "Department '{$dept}' already exists.");
            }
        }
        header('Location: settings.php');
        exit;
    }

    if (isset($_POST['delete_department'])) {
        $delDept = trim($_POST['delete_department'] ?? '');
        $depts = get_doctor_departments();
        $filtered = array_values(array_filter($depts, fn($d) => $d !== $delDept));
        save_doctor_departments($filtered);
        flash('success', "Doctor department '{$delDept}' removed.");
        header('Location: settings.php');
        exit;
    }

    if (isset($_POST['reset_departments'])) {
        set_setting('doctor_departments', '');
        flash('success', 'Doctor departments reset to standard list.');
        header('Location: settings.php');
        exit;
    }

    if (isset($_POST['retry_all'])) {
        $ids = db()->query("SELECT patient_id FROM sheet_sync_queue WHERE status<>'synced' ORDER BY id LIMIT 100")->fetchAll(PDO::FETCH_COLUMN);
        $ok = 0;
        foreach ($ids as $id) {
            if (attempt_sheet_sync((int)$id)) $ok++;
        }
        flash('success', "Sync retried: $ok of " . count($ids) . ' successful.');
        header('Location: settings.php');
        exit;
    }

    if (isset($_POST['save_sheets'])) {
        set_setting('google_sheets_web_app_url', trim($_POST['google_sheets_web_app_url'] ?? ''));
        set_setting('google_sheets_token', trim($_POST['google_sheets_token'] ?? ''));
        flash('success', 'Google Sheets backup settings saved.');
        header('Location: settings.php');
        exit;
    }
}

$uhidPrefix = get_uhid_prefix();
$billPrefix = get_bill_prefix();
$uhidStart = setting('uhid_start_number', '0');
$billStart = setting('bill_start_number', '0');

$departments = get_doctor_departments();

$url = setting('google_sheets_web_app_url');
$token = setting('google_sheets_token');
$stats = db()->query("SELECT status, COUNT(*) c FROM sheet_sync_queue GROUP BY status")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-title-banner">
    <div class="page-title-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
            <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
        </svg>
    </div>
    <div class="page-title-text">
        <h2>Admin Settings & System Controls</h2>
        <p>Configure OPD Numbering (UHID / Bill Prefixes), Doctor Departments, and Google Sheets Backup.</p>
    </div>
</div>

<div class="dashboard-grid-2">
    <!-- Left Column: OPD Numbering & Doctor Departments -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- 1. OPD Numbering & Prefixes Card -->
        <section class="card">
            <div class="card-head">
                <div class="card-head-left">
                    <div class="card-icon-badge mint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <div>
                        <h2>OPD Numbering & Prefixes</h2>
                        <p>Customize UHID & Bill No codes. Auto-generated on registration.</p>
                    </div>
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="save_numbering" value="1">

                <div class="form-grid-styled" style="margin-bottom: 16px;">
                    <div class="form-field-group">
                        <label for="uhid_prefix">UHID Prefix</label>
                        <input name="uhid_prefix" id="uhid_prefix" value="<?= e($uhidPrefix) ?>" placeholder="e.g. MLH" required style="text-transform: uppercase; font-weight: 700;">
                        <small style="color: var(--text-muted); font-size: 11.5px; margin-top: 3px;">
                            Live format: <strong style="color: var(--primary);"><?= e(get_next_uhid()) ?></strong>
                        </small>
                    </div>

                    <div class="form-field-group">
                        <label for="uhid_start_number">UHID Minimum / Starting Number</label>
                        <input type="number" name="uhid_start_number" id="uhid_start_number" value="<?= e($uhidStart) ?>" min="0" placeholder="0">
                        <small style="color: var(--text-muted); font-size: 11.5px; margin-top: 3px;">
                            Optional offset (e.g. 8922)
                        </small>
                    </div>

                    <div class="form-field-group">
                        <label for="bill_prefix">Bill No Prefix</label>
                        <input name="bill_prefix" id="bill_prefix" value="<?= e($billPrefix) ?>" placeholder="e.g. MOB" required style="text-transform: uppercase; font-weight: 700;">
                        <small style="color: var(--text-muted); font-size: 11.5px; margin-top: 3px;">
                            Live format: <strong style="color: var(--primary);"><?= e(get_next_bill_no()) ?></strong>
                        </small>
                    </div>

                    <div class="form-field-group">
                        <label for="bill_start_number">Bill No Minimum / Starting Number</label>
                        <input type="number" name="bill_start_number" id="bill_start_number" value="<?= e($billStart) ?>" min="0" placeholder="0">
                        <small style="color: var(--text-muted); font-size: 11.5px; margin-top: 3px;">
                            Optional offset (e.g. 52078)
                        </small>
                    </div>
                </div>

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px 14px; margin-bottom: 16px; font-size: 12.5px; color: #166534;">
                    <strong>ℹ️ Daily Appointment Number:</strong> Appointment No automatically counts patients per day starting from <code>1</code> for each new date.
                </div>

                <button class="btn btn-primary">Save numbering settings</button>
            </form>
        </section>

        <!-- 2. Doctor Departments Management Card -->
        <section class="card">
            <div class="card-head">
                <div class="card-head-left">
                    <div class="card-icon-badge blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"></path>
                            <path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"></path>
                            <circle cx="20" cy="10" r="2"></circle>
                        </svg>
                    </div>
                    <div>
                        <h2>Doctor Departments</h2>
                        <p>Add and manage departments available in patient registration.</p>
                    </div>
                </div>
            </div>

            <!-- Add New Department Form -->
            <form method="post" style="display: flex; gap: 10px; margin-bottom: 18px;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="add_department" value="1">
                <input name="department_name" placeholder="Enter department name (e.g. IVF, Pediatrics)..." required style="flex: 1; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; outline: none;">
                <button class="btn btn-primary" style="white-space: nowrap;">+ Add Department</button>
            </form>

            <!-- Department Chips List -->
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px;">
                <?php foreach ($departments as $dept): ?>
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 12px; font-size: 13px; font-weight: 600; color: #1e293b;">
                        <span><?= e($dept) ?></span>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Remove <?= e(addslashes($dept)) ?> department?');">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="delete_department" value="<?= e($dept) ?>">
                            <button type="submit" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0 2px; font-size: 14px; font-weight: 700; line-height: 1;" title="Delete department">×</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" onsubmit="return confirm('Reset all departments to standard default list?');">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="reset_departments" value="1">
                <button type="submit" class="btn btn-soft" style="font-size: 12px; padding: 6px 12px;">Reset to Defaults</button>
            </form>
        </section>
    </div>

    <!-- Right Column: Google Sheets Backup Settings -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <section class="card">
            <div class="card-head">
                <div class="card-head-left">
                    <div class="card-icon-badge purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
                        </svg>
                    </div>
                    <div>
                        <h2>Google Sheets Backup</h2>
                        <p>Automatic live mirror for all registered patients.</p>
                    </div>
                </div>
            </div>

            <form method="post" style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="save_sheets" value="1">

                <div class="form-field-group">
                    <label for="google_sheets_web_app_url">Apps Script Web App URL</label>
                    <input type="url" name="google_sheets_web_app_url" id="google_sheets_web_app_url" value="<?= e($url) ?>" placeholder="https://script.google.com/macros/s/.../exec">
                </div>

                <div class="form-field-group">
                    <label for="google_sheets_token">Secret Token</label>
                    <input name="google_sheets_token" id="google_sheets_token" value="<?= e($token) ?>" autocomplete="off" placeholder="Your secret token">
                </div>

                <button class="btn btn-primary">Save Backup Settings</button>
            </form>

            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 18px 0;">

            <h3 style="font-size: 14px; font-weight: 700; margin: 0 0 12px;">Sync Queue Status</h3>
            <div class="quick-filter-grid" style="margin-bottom: 14px;">
                <?php foreach ($stats as $s): ?>
                    <div class="quick-filter-card <?= $s['status'] === 'synced' ? 'green' : ($s['status'] === 'failed' ? 'orange' : 'blue') ?>">
                        <div class="quick-filter-text">
                            <strong><?= e($s['c']) ?></strong>
                            <span><?= e(ucfirst($s['status'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($stats)): ?>
                    <div style="color: var(--text-muted); font-size: 13px;">Queue is currently empty.</div>
                <?php endif; ?>
            </div>

            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <button class="btn btn-soft" name="retry_all" value="1" style="width: 100%;">Retry failed / pending now</button>
            </form>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

