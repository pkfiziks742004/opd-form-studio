<?php
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$uhid = trim($_GET['uhid'] ?? '');

$patient = null;
if ($id > 0) {
    $st = db()->prepare("SELECT p.*, u.name AS created_by_name FROM patients p LEFT JOIN users u ON u.id = p.created_by WHERE p.id = ? LIMIT 1");
    $st->execute([$id]);
    $patient = $st->fetch();
} elseif ($uhid !== '') {
    $st = db()->prepare("SELECT p.*, u.name AS created_by_name FROM patients p LEFT JOIN users u ON u.id = p.created_by WHERE p.uhid = ? ORDER BY p.id DESC LIMIT 1");
    $st->execute([$uhid]);
    $patient = $st->fetch();
}

if (!$patient) {
    flash('error', 'Patient record not found.');
    header('Location: patients.php');
    exit;
}

// Fetch all visits for this patient's UHID
$visits = [];
if (!empty($patient['uhid'])) {
    $vSt = db()->prepare("
        SELECT p.*, u.name AS created_by_name 
        FROM patients p 
        LEFT JOIN users u ON u.id = p.created_by 
        WHERE p.uhid = ? 
        ORDER BY p.visit_date DESC, p.id DESC
    ");
    $vSt->execute([$patient['uhid']]);
    $visits = $vSt->fetchAll();
} else {
    $visits = [$patient];
}

$totalVisits = count($visits);
$cleanName = stripslashes(trim($patient['name']));
$cleanGuardian = stripslashes(trim($patient['guardian'] ?? ''));
$cleanAddress = stripslashes(trim($patient['address'] ?? ''));

$ageSexParts = [];
if ($patient['age']) $ageSexParts[] = $patient['age'] . ' Yrs';
if ($patient['sex']) $ageSexParts[] = $patient['sex'];
$ageSexStr = !empty($ageSexParts) ? implode(' · ', $ageSexParts) : 'Demographics';
$sexLower = strtolower($patient['sex'] ?? '');

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
?>

<!-- Breadcrumb & Top Action Bar -->
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <a href="patients.php" class="btn btn-soft" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span>Back to Directory</span>
        </a>
        <div style="font-size: 13.5px; color: #64748b;">
            <a href="dashboard.php" style="color: #64748b; text-decoration: none;">Dashboard</a>
            <span style="margin: 0 6px; color: #cbd5e1;">/</span>
            <a href="patients.php" style="color: #64748b; text-decoration: none;">Patients</a>
            <span style="margin: 0 6px; color: #cbd5e1;">/</span>
            <strong style="color: #0f172a;"><?= e($cleanName) ?></strong>
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
        <a href="patient_form.php?revisit_id=<?= $patient['id'] ?>" class="btn-profile-head revisit" style="padding: 10px 18px; font-size: 13px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
            </svg>
            <span>+ Revisit Patient (New OPD Slip)</span>
        </a>
        <button type="button" onclick="openPrintModal(<?= (int)$patient['id'] ?>, '<?= e(addslashes($patient['name'])) ?>', '<?= e(addslashes($patient['uhid'])) ?>')" class="btn-profile-head print" style="padding: 10px 16px; font-size: 13px; cursor: pointer; border: none; font-family: inherit;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Print Latest Slip</span>
        </button>
    </div>
</div>

<!-- Profile Hero Banner Card -->
<div class="card" style="padding: 20px 24px; margin-bottom: 22px; background: linear-gradient(180deg, #f0fdf9 0%, #ffffff 100%); border: 1px solid #d1fae5; border-radius: 5px;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div class="opd-modal-avatar <?= $sexLower ?>" style="width: 52px; height: 52px; border-radius: 4px; font-size: 24px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 28px; height: 28px;">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #06382d; letter-spacing: -0.02em;"><?= e($cleanName) ?></h1>
                    <span class="profile-pill-badge gender"><?= e($ageSexStr) ?></span>
                    <span class="profile-pill-badge uhid">UHID: <?= e($patient['uhid'] ?: '—') ?></span>
                    <span class="profile-pill-badge visits"><?= $totalVisits ?> <?= $totalVisits === 1 ? 'Visit Recorded' : 'Visits Recorded' ?></span>
                    <span class="profile-pill-badge panel"><?= e($patient['panel'] ?: 'CASH') ?></span>
                </div>
                <div class="opd-modal-sub-bar" style="margin-top: 5px; font-size: 12.5px;">
                    <span class="sub-bar-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <span>Phone: <strong style="color: #0f172a;"><?= e($patient['contact_number'] ?: 'Not Provided') ?></strong></span>
                    </span>
                    <span style="color: #cbd5e1;">•</span>
                    <span class="sub-bar-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <span>Guardian: <strong style="color: #0f172a;"><?= e($cleanGuardian ? ('S/O / W/O ' . $cleanGuardian) : 'Not Provided') ?></strong></span>
                    </span>
                    <?php if ($cleanAddress): ?>
                        <span style="color: #cbd5e1;">•</span>
                        <span class="sub-bar-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Address: <strong style="color: #0f172a;"><?= e($cleanAddress) ?></strong></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <div style="background: #ffffff; border: 1px dashed #86efac; border-radius: 4px; padding: 8px 14px; display: flex; align-items: center; gap: 12px;">
                <div>
                    <small style="display: block; font-size: 9.5px; font-weight: 800; text-transform: uppercase; color: #047857;">LIFETIME UHID</small>
                    <strong style="font-family: monospace; font-size: 13.5px; color: #064e3b;"><?= e($patient['uhid'] ?: '—') ?></strong>
                </div>
                <button type="button" class="btn-copy-uhid" onclick="copyPageUhid('<?= e($patient['uhid'] ?: '') ?>', this)" title="Copy UHID">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span>Copy</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main 2-Column Patient Profile & Clinical Timeline -->
<div class="patient-profile-grid">
    <!-- Left Column: Patient Demographics & Details -->
    <div class="profile-info-card">
        <div class="profile-card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Patient Information</span>
        </div>

        <div class="profile-data-list">
            <div class="profile-data-row">
                <div class="profile-row-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="profile-row-content">
                    <span class="profile-row-lbl">Full Patient Name</span>
                    <span class="profile-row-val"><?= e($cleanName) ?></span>
                </div>
            </div>

            <div class="profile-data-row">
                <div class="profile-row-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="profile-row-content">
                    <span class="profile-row-lbl">Guardian / Relation</span>
                    <span class="profile-row-val"><?= e($cleanGuardian ? ('S/O / W/O ' . $cleanGuardian) : 'Not Specified') ?></span>
                </div>
            </div>

            <div class="profile-data-row">
                <div class="profile-row-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="profile-row-content">
                    <span class="profile-row-lbl">Age & Gender</span>
                    <span class="profile-row-val"><?= e($ageSexStr) ?></span>
                </div>
            </div>

            <div class="profile-data-row">
                <div class="profile-row-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div class="profile-row-content">
                    <span class="profile-row-lbl">Contact Number</span>
                    <span class="profile-row-val">
                        <?php if ($patient['contact_number']): ?>
                            <a href="tel:<?= urlencode($patient['contact_number']) ?>"><?= e($patient['contact_number']) ?></a>
                        <?php else: ?>
                            Not Provided
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="profile-data-row">
                <div class="profile-row-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
                <div class="profile-row-content">
                    <span class="profile-row-lbl">Residential Address</span>
                    <span class="profile-row-val"><?= e($cleanAddress ?: 'Not Specified') ?></span>
                </div>
            </div>

            <div class="profile-data-row">
                <div class="profile-row-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 22 7 12 2"/><line x1="2" y1="20" x2="22" y2="20"/><line x1="6" y1="7" x2="6" y2="20"/><line x1="10" y1="7" x2="10" y2="20"/><line x1="14" y1="7" x2="14" y2="20"/><line x1="18" y1="7" x2="18" y2="20"/></svg>
                </div>
                <div class="profile-row-content">
                    <span class="profile-row-lbl">Panel / Billing Category</span>
                    <span class="profile-row-val"><?= e($patient['panel'] ?: 'CASH') ?></span>
                </div>
            </div>

            <div class="profile-data-row">
                <div class="profile-row-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="profile-row-content">
                    <span class="profile-row-lbl">Initial Registration Date</span>
                    <span class="profile-row-val"><?= e(date('d M Y', strtotime(end($visits)['visit_date']))) ?></span>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; text-align: center;">
            <a href="patient_form.php?revisit_id=<?= $patient['id'] ?>" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Book Follow-up / Revisit</span>
            </a>
        </div>
    </div>

    <!-- Right Column: Clinical Visit Timeline ("Kab Kab Visit Kiya") -->
    <div class="timeline-container-card">
        <div class="timeline-head">
            <h4>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>OPD Visit Timeline (Kab Kab Visit Kiya)</span>
            </h4>
            <span class="timeline-head-badge"><?= $totalVisits ?> <?= $totalVisits === 1 ? 'Consultation Recorded' : 'Consultations Recorded' ?></span>
        </div>

        <div class="timeline-tree">
            <?php foreach ($visits as $idx => $v): 
                $visitNum = $totalVisits - $idx;
                $isLatest = ($idx === 0);
                $vDate = $v['visit_date'];
                $relativeDate = '';
                if ($vDate === $today) {
                    $relativeDate = 'Today';
                } elseif ($vDate === $yesterday) {
                    $relativeDate = 'Yesterday';
                } else {
                    $daysAgo = (int)round((strtotime($today) - strtotime($vDate)) / 86400);
                    if ($daysAgo > 0 && $daysAgo < 30) {
                        $relativeDate = $daysAgo . ' days ago';
                    }
                }
            ?>
                <div class="timeline-item">
                    <div class="timeline-dot <?= $isLatest ? 'latest' : '' ?>" title="Visit #<?= $visitNum ?>">
                        <?php if ($isLatest): ?>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        <?php else: ?>
                            <?= $visitNum ?>
                        <?php endif; ?>
                    </div>

                    <div class="visit-timeline-card <?= $isLatest ? 'latest-card' : '' ?>">
                        <div class="visit-card-top">
                            <div class="visit-card-top-left">
                                <span class="visit-tag-badge <?= $isLatest ? 'latest' : 'past' ?>">
                                    <?= $isLatest ? "Latest Visit · Visit #$visitNum" : "Visit #$visitNum" ?>
                                </span>
                                <span class="visit-date-main"><?= e(date('d M Y', strtotime($v['visit_date']))) ?></span>
                                <span class="visit-time-main"><?= $v['visit_time'] ? e(date('h:i A', strtotime($v['visit_time']))) : '—' ?></span>
                                <?php if ($relativeDate): ?>
                                    <span class="visit-relative-badge"><?= e($relativeDate) ?></span>
                                <?php endif; ?>
                            </div>

                            <button type="button" onclick="openPrintModal(<?= (int)$v['id'] ?>, '<?= e(addslashes($patient['name'])) ?>', '<?= e(addslashes($patient['uhid'])) ?>')" class="btn-visit-print" title="Print this visit slip" style="cursor: pointer; border: none; font-family: inherit;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                    <rect x="6" y="14" width="12" height="8"></rect>
                                </svg>
                                <span>Print Slip</span>
                            </button>
                        </div>

                        <div class="visit-chips-grid">
                            <div class="visit-chip">
                                <span class="visit-chip-lbl">DEPARTMENT</span>
                                <span class="visit-chip-val" style="color: #047857;"><?= e($v['doctor_dept'] ?: 'General') ?></span>
                            </div>
                            <div class="visit-chip">
                                <span class="visit-chip-lbl">CABIN / ROOM</span>
                                <span class="visit-chip-val"><?= e($v['room_no'] ?: '—') ?></span>
                            </div>
                            <div class="visit-chip">
                                <span class="visit-chip-lbl">DAILY TOKEN (APP NO.)</span>
                                <span class="visit-chip-val" style="color: #1e40af;">#<?= e($v['app_no'] ?: '—') ?></span>
                            </div>
                            <div class="visit-chip">
                                <span class="visit-chip-lbl">BILL NO. / INVOICE</span>
                                <span class="visit-chip-val" style="font-family: monospace;"><?= e($v['bill_no'] ?: '—') ?></span>
                            </div>
                        </div>

                        <div class="visit-card-foot">
                            <span>Panel: <strong><?= e($v['panel'] ?: 'CASH') ?></strong></span>
                            <span>Attended by <strong><?= e($v['created_by_name'] ?: 'Staff') ?></strong></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Revisit Callout Box -->
        <div class="timeline-revisit-banner">
            <div class="timeline-revisit-text">
                <div class="timeline-revisit-title">Need to register another follow-up visit for <?= e($cleanName) ?>?</div>
                <div class="timeline-revisit-sub">Keeps existing lifetime UHID and demographic data, while issuing a fresh OPD slip.</div>
            </div>
            <a href="patient_form.php?revisit_id=<?= $patient['id'] ?>" class="btn-timeline-revisit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Revisit Patient</span>
            </a>
        </div>
    </div>
</div>

<script>
function copyPageUhid(uhid, btn) {
    if (!uhid) return;
    navigator.clipboard.writeText(uhid).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> <span>Copied!</span>`;
        btn.style.background = '#047857';
        btn.style.color = '#ffffff';
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
