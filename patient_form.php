<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/code_template.php';
require_once __DIR__ . '/includes/settings.php';

$permissions = field_permissions((int)$user['id']);
$templates = db()->query('SELECT id, name, file_path, default_layout_json, template_type FROM templates WHERE active=1 ORDER BY id ASC')->fetchAll();

// Determine default or selected template
$defId = (int)setting('default_template_id', '0');
$tpl = null;
if ($defId > 0) {
    foreach ($templates as $t) {
        if ((int)$t['id'] === $defId) {
            $tpl = $t;
            break;
        }
    }
}
if (!$tpl && $templates) {
    $tpl = $templates[0];
}

$isCode = is_code_template($tpl);
$layout = $isCode ? [] : get_layout($tpl, (int)$user['id']);

function input_state(array $permissions, string $key): string {
    return empty($permissions[$key]['editable']) ? 'disabled' : '';
}

// Check for Revisit Patient via URL (revisit_id or uhid)
$revisitPatient = null;
$revisitId = (int)($_GET['revisit_id'] ?? 0);
$revisitUhid = trim($_GET['uhid'] ?? '');

if ($revisitId > 0) {
    $st = db()->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
    $st->execute([$revisitId]);
    $revisitPatient = $st->fetch();
} elseif ($revisitUhid !== '') {
    $st = db()->prepare("SELECT * FROM patients WHERE uhid = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$revisitUhid]);
    $revisitPatient = $st->fetch();
}

$isRevisit = ($revisitPatient !== null);
$totalPrevVisits = 0;

if ($isRevisit) {
    $defaultUhid = $revisitPatient['uhid'] ?: get_next_uhid();
    $defaultName = $revisitPatient['name'] ?? '';
    $defaultAge = $revisitPatient['age'] ?? '';
    $defaultSex = $revisitPatient['sex'] ?? '';
    $defaultGuardian = $revisitPatient['guardian'] ?? '';
    $defaultContact = $revisitPatient['contact_number'] ?? '';
    $defaultAddress = $revisitPatient['address'] ?? '';
    $defaultPanel = $revisitPatient['panel'] ?? 'CASH';
    $defaultDept = $revisitPatient['doctor_dept'] ?? '';
    $defaultRoom = $revisitPatient['room_no'] ?? '';
    
    if (!empty($revisitPatient['uhid'])) {
        $stCount = db()->prepare("SELECT COUNT(*) FROM patients WHERE uhid = ?");
        $stCount->execute([$revisitPatient['uhid']]);
        $totalPrevVisits = (int)$stCount->fetchColumn();
    } else {
        $totalPrevVisits = 1;
    }
} else {
    $defaultUhid = get_next_uhid();
    $defaultName = '';
    $defaultAge = '';
    $defaultSex = '';
    $defaultGuardian = '';
    $defaultContact = '';
    $defaultAddress = '';
    $defaultPanel = 'CASH';
    $defaultDept = '';
    $defaultRoom = '';
}

// Auto-generated numbers matching admin prefixes & today's appointment counter
$defaultBill = get_next_bill_no();
$defaultAppNo = get_next_app_no();
$departments = get_doctor_departments();
if (!$defaultDept && !empty($departments)) {
    $defaultDept = $departments[0];
}
?>

<style>
<?= get_motherland_opd_css() ?>
.paper-preview-wrap {
    position: relative;
    width: 100%;
    aspect-ratio: 210/297;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    overflow: hidden;
}
.paper-preview-wrap .motherland-sheet {
    position: absolute;
    top: 0;
    left: 0;
    transform-origin: top left;
    margin: 0;
    box-shadow: none;
}
.input-readonly {
    background: #f8fafc !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    cursor: default;
}
</style>

<!-- Page Header Banner -->
<div class="page-title-banner">
    <div class="page-title-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <line x1="19" y1="8" x2="19" y2="14"></line>
            <line x1="22" y1="11" x2="16" y2="11"></line>
        </svg>
    </div>
    <div class="page-title-text">
        <h2><?= $isRevisit ? 'OPD Revisit Registration' : 'New OPD Registration' ?></h2>
        <p><?= $isRevisit ? 'Registering a follow-up visit for existing patient. Live preview updates automatically.' : 'Enter patient details or search existing records to register a revisit.' ?></p>
    </div>
</div>

<!-- Revisit Active Banner (if loaded via revisit) -->
<?php if ($isRevisit): ?>
    <div class="revisit-alert-banner" id="revisitBanner">
        <div class="revisit-alert-left">
            <div class="revisit-alert-badge-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
            </div>
            <div class="revisit-alert-text">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="revisit-tag-pill">REVISIT PATIENT MODE</span>
                    <span style="font-size: 11.5px; font-weight: 700; color: #047857;"><?= $totalPrevVisits ?> Past <?= $totalPrevVisits === 1 ? 'Visit' : 'Visits' ?> Recorded</span>
                </div>
                <div class="revisit-alert-title"><?= e($revisitPatient['name']) ?> · UHID: <?= e($revisitPatient['uhid']) ?></div>
                <div class="revisit-alert-sub">Existing patient demographic details loaded. Generating new Bill No. & Daily Appointment No. for today's visit.</div>
            </div>
        </div>
        <a href="patient_form.php" class="btn-switch-new-patient" title="Switch back to New Patient Registration">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            <span>New Patient Mode</span>
        </a>
    </div>
<?php endif; ?>

<!-- Quick Search / Revisit Patient Bar -->
<div class="revisit-search-section">
    <div class="revisit-search-header">
        <div class="revisit-search-header-left">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span>Search & Revisit Existing Patient</span>
        </div>
        <span style="font-size: 11.5px; color: var(--text-muted);">Quickly find returning patients by Name, UHID, or Mobile Number</span>
    </div>
    <div class="revisit-search-input-wrap">
        <svg class="revisit-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="revisitSearchInput" class="revisit-search-input" placeholder="Type name, 10-digit phone, or UHID to load returning patient..." autocomplete="off">
        <div id="revisitSearchResults" class="revisit-dropdown-results"></div>
    </div>
</div>

<form method="post" action="patient_save.php" id="patientForm">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="visit_time" id="visit_time" value="<?= date('H:i') ?>">

    <div class="workspace-grid-form">
        <!-- Patient Information Card -->
        <section class="card form-card">
            <div class="card-head">
                <div class="card-head-left">
                    <div class="card-icon-badge mint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div>
                        <h2>Patient Information</h2>
                        <p><?= $isRevisit ? 'Verify or update details for this revisit' : 'Fill in the patient details below' ?></p>
                    </div>
                </div>
            </div>

            <div class="form-grid-styled">
                <?php if ($permissions['uhid']['visible']): ?>
                    <div class="form-field-group">
                        <label for="uhid">UHID <span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">(<?= $isRevisit ? 'Existing Patient' : 'Auto Generated' ?>)</span></label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                                <circle cx="8" cy="10" r="2"></circle>
                                <line x1="13" y1="9" x2="18" y2="9"></line>
                                <line x1="13" y1="12" x2="16" y2="12"></line>
                            </svg>
                            <input name="uhid" id="uhid" value="<?= e($defaultUhid) ?>" readonly class="input-readonly">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['name']['visible']): ?>
                    <div class="form-field-group">
                        <label for="name">Patient name</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <input name="name" id="name" required placeholder="Full name" value="<?= e($defaultName) ?>" <?= input_state($permissions, 'name') ?>>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['age_sex']['visible']): ?>
                    <div class="form-field-group">
                        <label for="age">Age</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <input type="number" name="age" id="age" min="0" max="125" placeholder="Years" value="<?= e($defaultAge) ?>" <?= input_state($permissions, 'age_sex') ?>>
                        </div>
                    </div>

                    <div class="form-field-group">
                        <label for="sex">Sex</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="10" cy="14" r="5"></circle>
                                <line x1="19" y1="5" x2="13.6" y2="10.4"></line>
                                <polyline points="15 5 19 5 19 9"></polyline>
                            </svg>
                            <select name="sex" id="sex" <?= input_state($permissions, 'age_sex') ?>>
                                <option value="">Select</option>
                                <option <?= strcasecmp($defaultSex, 'Male') === 0 ? 'selected' : '' ?>>Male</option>
                                <option <?= strcasecmp($defaultSex, 'Female') === 0 ? 'selected' : '' ?>>Female</option>
                                <option <?= strcasecmp($defaultSex, 'Other') === 0 ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['guardian']['visible']): ?>
                    <div class="form-field-group">
                        <label for="guardian">Guardian</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <input name="guardian" id="guardian" placeholder="S/O, D/O, W/O, Guardian" value="<?= e($defaultGuardian) ?>" <?= input_state($permissions, 'guardian') ?>>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['contact_number']['visible']): ?>
                    <div class="form-field-group">
                        <label for="contact_number">Contact number</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <input name="contact_number" id="contact_number" inputmode="numeric" placeholder="10-digit mobile" value="<?= e($defaultContact) ?>" <?= input_state($permissions, 'contact_number') ?>>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['address']['visible']): ?>
                    <div class="form-field-group span-2">
                        <label for="address">Address</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <textarea name="address" id="address" rows="2" placeholder="Patient address" <?= input_state($permissions, 'address') ?>><?= e($defaultAddress) ?></textarea>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['bill_no']['visible']): ?>
                    <div class="form-field-group">
                        <label for="bill_no">Bill No. <span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">(Auto Generated)</span></label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <input name="bill_no" id="bill_no" value="<?= e($defaultBill) ?>" readonly class="input-readonly">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['date']['visible']): ?>
                    <div class="form-field-group">
                        <label for="visit_date">Date</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <input type="date" name="visit_date" id="visit_date" value="<?= date('Y-m-d') ?>" <?= input_state($permissions, 'date') ?>>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['panel']['visible']): ?>
                    <div class="form-field-group">
                        <label for="panel">Panel</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                            <input name="panel" id="panel" placeholder="CASH / TPA / Panel" value="<?= e($defaultPanel) ?>" <?= input_state($permissions, 'panel') ?>>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['doctor_dept']['visible']): ?>
                    <div class="form-field-group">
                        <label for="doctor_dept">Doctor department</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"></path>
                                <path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"></path>
                                <circle cx="20" cy="10" r="2"></circle>
                            </svg>
                            <select name="doctor_dept" id="doctor_dept" <?= input_state($permissions, 'doctor_dept') ?>>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= e($dept) ?>" <?= $defaultDept === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['room_no']['visible']): ?>
                    <div class="form-field-group">
                        <label for="room_no">Room No.</label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 4v16"></path>
                                <path d="M2 8h18a2 2 0 0 1 2 2v10"></path>
                                <path d="M2 17h20"></path>
                                <path d="M6 8v9"></path>
                            </svg>
                            <input name="room_no" id="room_no" placeholder="Room / Cabin No" value="<?= e($defaultRoom) ?>" <?= input_state($permissions, 'room_no') ?>>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($permissions['app_no']['visible']): ?>
                    <div class="form-field-group">
                        <label for="app_no">Appointment No. <span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">(Daily Auto Count)</span></label>
                        <div class="input-icon-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                            </svg>
                            <input name="app_no" id="app_no" value="<?= e($defaultAppNo) ?>" readonly class="input-readonly">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-field-group span-2">
                    <label for="templateSelect">Template</label>
                    <div class="input-icon-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <select name="template_id" id="templateSelect">
                            <?php foreach ($templates as $t): ?>
                                <option value="<?= $t['id'] ?>" data-file="<?= e($t['file_path']) ?>" data-type="<?= is_code_template($t) ? 'code' : 'image' ?>" <?= (int)$t['id'] === (int)$tpl['id'] ? 'selected' : '' ?>>
                                    <?= e($t['name']) ?><?= is_code_template($t) ? ' (Digital Code Template)' : ' (Image Scan)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions-styled">
                <button class="btn-save-print" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <?= $isRevisit ? 'Save revisit & continue to print' : 'Save patient & continue to print' ?>
                </button>
                <a class="btn-cancel-styled" href="dashboard.php">Cancel</a>
            </div>
        </section>

        <!-- Live Preview Card -->
        <section class="card preview-card-sticky">
            <div class="card-head">
                <div class="card-head-left">
                    <div class="card-icon-badge mint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </div>
                    <div>
                        <h2>Live OPD Preview</h2>
                        <p>Values update as you type. Real-time preview before print.</p>
                    </div>
                </div>
                <?php
                    $canManageTpl = ($user['role'] === 'admin');
                    $tplLinkHref = ($isCode && $canManageTpl) ? 'templates.php' : 'template_editor.php';
                    $tplLinkText = ($isCode && $canManageTpl) ? 'Manage template' : 'Adjust layout';
                ?>
                <a class="link-manage-template" href="<?= $tplLinkHref ?>" id="adjustLayoutLink">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <?= $tplLinkText ?>
                </a>
            </div>

            <?php if ($tpl): ?>
                <div class="paper-preview-wrap paper-preview" id="paperPreview" data-mode="<?= $isCode ? 'code' : 'image' ?>" <?= !$isCode ? 'style="background-image:url(\''.e($tpl['file_path']).'\')"' : '' ?>>
                    <?php if ($isCode): ?>
                        <?= render_motherland_opd([
                            'uhid' => $defaultUhid,
                            'name' => $defaultName,
                            'age' => $defaultAge,
                            'sex' => $defaultSex,
                            'guardian' => $defaultGuardian,
                            'contact_number' => $defaultContact,
                            'address' => $defaultAddress,
                            'bill_no' => $defaultBill,
                            'panel' => $defaultPanel,
                            'app_no' => $defaultAppNo,
                            'visit_date' => date('Y-m-d'),
                            'visit_time' => date('H:i'),
                            'doctor_dept' => $defaultDept,
                            'room_no' => $defaultRoom
                        ], [], true) ?>
                    <?php else: ?>
                        <?php foreach ($layout as $k => $pos):
                            if (empty($permissions[$k]['visible'])) continue; ?>
                            <span class="preview-field" data-field="<?= e($k) ?>" style="left:<?= floatval($pos['x'] ?? 5) ?>%;top:<?= floatval($pos['y'] ?? 5) ?>%;font-size:<?= intval($pos['fontSize'] ?? 12) ?>px;max-width:<?= intval($pos['width'] ?? 220) ?>px"></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty">Upload or activate a template from Admin → Templates.</div>
            <?php endif; ?>
        </section>
    </div>
</form>

<script>
    window.OPD_LAYOUTS = <?= json_encode(array_column($templates, null, 'id')) ?>;

    // Quick Revisit Live Search
    (() => {
        const searchInput = document.getElementById('revisitSearchInput');
        const resultsBox = document.getElementById('revisitSearchResults');
        let debounceTimer = null;

        if (searchInput && resultsBox) {
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const q = searchInput.value.trim();
                if (q.length < 2) {
                    resultsBox.style.display = 'none';
                    resultsBox.innerHTML = '';
                    return;
                }
                debounceTimer = setTimeout(() => {
                    fetch('patient_ajax.php?action=search&q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(data => {
                            if (!data.success || !data.patients || data.patients.length === 0) {
                                resultsBox.innerHTML = '<div style="padding: 14px; text-align: center; color: var(--text-muted); font-size: 13px;">No existing patients found matching "' + q + '".</div>';
                                resultsBox.style.display = 'block';
                                return;
                            }
                            resultsBox.innerHTML = '';
                            data.patients.forEach(p => {
                                const div = document.createElement('div');
                                div.className = 'revisit-result-item';
                                const demographics = [p.age ? p.age + ' Yrs' : '', p.sex].filter(Boolean).join(' / ') || 'Demographics';
                                div.innerHTML = `
                                    <div>
                                        <div class="revisit-result-name">${p.name} <span style="font-size:11.5px; font-weight:normal; color:#64748b;">(${demographics})</span></div>
                                        <div class="revisit-result-meta">UHID: <strong style="font-family:monospace; color:var(--primary-dark);">${p.uhid}</strong> &bull; Phone: <strong>${p.contact_number || '—'}</strong> &bull; Last Visit: ${p.last_visit_date}</div>
                                    </div>
                                    <span class="revisit-result-action">🔁 Select for Revisit (${p.visit_count} ${p.visit_count === 1 ? 'Visit' : 'Visits'})</span>
                                `;
                                div.addEventListener('click', () => {
                                    window.location.href = 'patient_form.php?revisit_id=' + p.id;
                                });
                                resultsBox.appendChild(div);
                            });
                            resultsBox.style.display = 'block';
                        })
                        .catch(err => console.error(err));
                }, 220);
            });

            // Close on click outside
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                    resultsBox.style.display = 'none';
                }
            });
        }
    })();
</script>
<?php $pfJsVer = file_exists(__DIR__ . '/assets/js/patient-form.js') ? filemtime(__DIR__ . '/assets/js/patient-form.js') : '2.2'; ?>
<script src="assets/js/patient-form.js?v=<?= $pfJsVer ?>"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
