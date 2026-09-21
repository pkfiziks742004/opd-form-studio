<?php
require_once __DIR__ . '/includes/header.php';

$today     = (int)db()->query("SELECT COUNT(*) FROM patients WHERE visit_date = CURDATE()")->fetchColumn();
$yesterday = (int)db()->query("SELECT COUNT(*) FROM patients WHERE visit_date = SUBDATE(CURDATE(), 1)")->fetchColumn();
$week      = (int)db()->query("SELECT COUNT(*) FROM patients WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetchColumn();
$month     = (int)db()->query("SELECT COUNT(*) FROM patients WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
$total     = (int)db()->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$last      = db()->query("SELECT * FROM patients ORDER BY id DESC LIMIT 1")->fetch();
$recent    = db()->query("SELECT * FROM patients ORDER BY id DESC LIMIT 10")->fetchAll();

// Genuine Trend Calculation for Today
if ($yesterday > 0) {
    $diff = $today - $yesterday;
    $pct = round(($diff / $yesterday) * 100);
    $trendLabel = ($pct >= 0 ? "+{$pct}%" : "{$pct}%") . ' vs yesterday';
    $trendClass = ($pct >= 0) ? 'mint' : 'rose';
    $trendIcon = ($pct >= 0) ? '↗' : '↘';
} else {
    $trendLabel = 'Today’s tally';
    $trendClass = 'mint';
    $trendIcon = '•';
}
?>

<!-- Quick Action Shortcuts Bar -->
<div class="quick-actions-bar">
    <a href="patient_form.php" class="btn-quick-action primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Register New Patient</span>
    </a>
    <?php if ($last): ?>
        <button type="button" onclick="openPrintModal(<?= (int)$last['id'] ?>, '<?= e(addslashes($last['name'])) ?>', '<?= e(addslashes($last['uhid'])) ?>')" class="btn-quick-action" style="cursor: pointer; border: none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Print Last Slip (<?= e($last['uhid']) ?>)</span>
        </button>
    <?php endif; ?>
    <a href="patients.php" class="btn-quick-action">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <span>Search Patient Directory</span>
    </a>
    <a href="templates.php" class="btn-quick-action">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        <span>OPD Print Templates</span>
    </a>
</div>

<!-- Stat Cards (4 in a row) -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-badge" style="background:var(--primary-light); color:var(--primary);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-header">
                <span class="stat-card-title">Today's Registrations</span>
                <span class="trend-pill <?= $trendClass ?>"><?= e($trendLabel) ?> <?= $trendIcon ?></span>
            </div>
            <div class="stat-card-number"><?= $today ?></div>
            <div class="stat-card-sub"><?= $today === 1 ? '1 patient admitted today' : "{$today} patients admitted today" ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-badge" style="background:#EFF6FF; color:#2563EB;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-header">
                <span class="stat-card-title">Last 7 Days</span>
                <span class="trend-pill blue">Weekly Volume</span>
            </div>
            <div class="stat-card-number"><?= $week ?></div>
            <div class="stat-card-sub">OPD visits in trailing 7 days</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-badge" style="background:#FAF5FF; color:#7C3AED;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
            </svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-header">
                <span class="stat-card-title">Last Month</span>
                <span class="trend-pill purple">30-Day Activity</span>
            </div>
            <div class="stat-card-number"><?= $month ?></div>
            <div class="stat-card-sub">Total consultations recorded</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-badge" style="background:#FFF7ED; color:#EA580C;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-header">
                <span class="stat-card-title">Total Patients</span>
                <span class="trend-pill orange">Master Registry</span>
            </div>
            <div class="stat-card-number"><?= $total ?></div>
            <div class="stat-card-sub">All registered hospital records</div>
        </div>
    </div>
</div>

<!-- Row 2: Latest Patient & Quick Filters -->
<div class="dashboard-grid-2">
    <!-- Latest Patient Card -->
    <section class="card">
        <div class="card-head">
            <div class="card-head-left">
                <div class="card-icon-badge mint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h2>Latest Patient Record</h2>
                    <p>Most recent registration processed at reception</p>
                </div>
            </div>
            <?php if ($last): ?>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn-table-action view" onclick="openPatientModal(<?= $last['id'] ?>)" title="View Complete Patient Profile & Visit History">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <span>Details</span>
                    </button>
                    <a class="btn-table-action revisit" href="patient_form.php?revisit_id=<?= $last['id'] ?>" title="Register Revisit for this Patient">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                        </svg>
                        <span>Revisit</span>
                    </a>
                    <button type="button" class="btn-print-opd-soft" onclick="openPrintModal(<?= (int)$last['id'] ?>, '<?= e(addslashes($last['name'])) ?>', '<?= e(addslashes($last['uhid'])) ?>')" title="Print OPD Slip" style="cursor: pointer; border: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Print Slip</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($last): 
            $lastSexLower = strtolower($last['sex'] ?? '');
            $avatarClass = ($lastSexLower === 'female') ? 'female' : (($lastSexLower === 'male') ? 'male' : '');
        ?>
            <!-- Patient Header Banner -->
            <div class="patient-header-banner" onclick="openPatientModal(<?= $last['id'] ?>)" style="cursor: pointer;" title="Click to view full patient details & visit history">
                <div class="patient-banner-left">
                    <div class="patient-banner-avatar <?= $avatarClass ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="patient-banner-info">
                        <strong><?= e($last['name']) ?></strong>
                        <span><?= e($last['uhid']) ?></span>
                    </div>
                </div>
                <div class="patient-banner-right">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Visited on</span>
                    <strong><?= e($last['visit_date']) ?></strong>
                    <span class="patient-banner-chevron">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </span>
                </div>
            </div>

            <!-- Detailed Grid -->
            <div class="detail-grid-styled">
                <div class="detail-grid-row">
                    <div class="detail-cell">
                        <svg class="detail-cell-icon cake" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"></path>
                            <path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"></path>
                            <path d="M2 21h20"></path>
                            <line x1="7" y1="8" x2="7" y2="4"></line>
                            <line x1="12" y1="8" x2="12" y2="4"></line>
                            <line x1="17" y1="8" x2="17" y2="4"></line>
                        </svg>
                        <div class="detail-cell-content">
                            <small>Age</small>
                            <?php if (!empty($last['age'])): ?>
                                <strong><?= e($last['age']) ?> yrs</strong>
                            <?php else: ?>
                                <strong class="is-empty">Not specified</strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-cell">
                        <svg class="detail-cell-icon gender" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="10" cy="14" r="5"></circle>
                            <line x1="19" y1="5" x2="13.6" y2="10.4"></line>
                            <polyline points="15 5 19 5 19 9"></polyline>
                        </svg>
                        <div class="detail-cell-content">
                            <small>Gender</small>
                            <?php if (!empty($last['sex'])): ?>
                                <strong><?= e(ucfirst($last['sex'])) ?></strong>
                            <?php else: ?>
                                <strong class="is-empty">Not specified</strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-cell">
                        <svg class="detail-cell-icon user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        <div class="detail-cell-content">
                            <small>Guardian / S/O</small>
                            <?php if (!empty($last['guardian'])): ?>
                                <strong><?= e($last['guardian']) ?></strong>
                            <?php else: ?>
                                <strong class="is-empty">None listed</strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-cell">
                        <svg class="detail-cell-icon phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <div class="detail-cell-content">
                            <small>Contact</small>
                            <?php if (!empty($last['contact_number'])): ?>
                                <strong><?= e($last['contact_number']) ?></strong>
                            <?php else: ?>
                                <strong class="is-empty">Not provided</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="detail-grid-row">
                    <div class="detail-cell">
                        <svg class="detail-cell-icon pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <div class="detail-cell-content">
                            <small>Address</small>
                            <?php if (!empty($last['address'])): ?>
                                <strong><?= e($last['address']) ?></strong>
                            <?php else: ?>
                                <strong class="is-empty">Not specified</strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-cell">
                        <svg class="detail-cell-icon doc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <div class="detail-cell-content">
                            <small>Bill No.</small>
                            <?php if (!empty($last['bill_no'])): ?>
                                <strong style="font-family: monospace;"><?= e($last['bill_no']) ?></strong>
                            <?php else: ?>
                                <strong class="is-empty">None</strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-cell">
                        <svg class="detail-cell-icon dept" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"></path>
                            <path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"></path>
                            <circle cx="20" cy="10" r="2"></circle>
                        </svg>
                        <div class="detail-cell-content">
                            <small>Department</small>
                            <strong><?= e($last['doctor_dept'] ?: 'General OPD') ?></strong>
                        </div>
                    </div>

                    <div class="detail-cell">
                        <svg class="detail-cell-icon bank" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 22 7 12 2"></polygon>
                            <line x1="2" y1="20" x2="22" y2="20"></line>
                            <line x1="6" y1="7" x2="6" y2="20"></line>
                            <line x1="10" y1="7" x2="10" y2="20"></line>
                            <line x1="14" y1="7" x2="14" y2="20"></line>
                            <line x1="18" y1="7" x2="18" y2="20"></line>
                        </svg>
                        <div class="detail-cell-content">
                            <small>Panel / TPA</small>
                            <strong><?= e($last['panel'] ?: 'Self / Cash') ?></strong>
                        </div>
                    </div>
                </div>

                <div class="detail-grid-bottom-bar">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <svg class="detail-cell-icon bed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 4v16"></path>
                            <path d="M2 8h18a2 2 0 0 1 2 2v10"></path>
                            <path d="M2 17h20"></path>
                            <path d="M6 8v9"></path>
                        </svg>
                        <div>
                            <small style="display:block; font-size:10.5px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Room No.</small>
                            <strong style="font-size:13px; font-weight:600; color:var(--text-main);"><?= e($last['room_no'] ?: 'N/A') ?></strong>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <svg class="detail-cell-icon app" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        </svg>
                        <div>
                            <small style="display:block; font-size:10.5px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Appointment / Token</small>
                            <strong style="font-size:13px; font-weight:600; color:var(--text-main);"><?= e($last['app_no'] ?: 'Direct OPD') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state-wrap">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="empty-state-title">No Patients Registered Yet</div>
                <div class="empty-state-desc">Start by registering your first patient to generate an OPD slip.</div>
                <a href="patient_form.php" class="btn btn-primary">
                    + Register First Patient
                </a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Quick Filters & Search Panel -->
    <section class="card">
        <div class="card-head">
            <div class="card-head-left">
                <div class="card-icon-badge blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                </div>
                <div>
                    <h2>Patient Directory & Filters</h2>
                    <p>Quickly access historical OPD records</p>
                </div>
            </div>
        </div>

        <!-- Quick In-Card Search Form -->
        <form action="patients.php" method="GET" class="quick-search-box-wrap">
            <svg class="quick-search-box-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="q" class="quick-search-box-input" placeholder="Search by name, UHID, mobile..." autocomplete="off">
        </form>

        <div class="quick-filter-grid">
            <a class="quick-filter-card green" href="patients.php?period=week">
                <div class="quick-filter-card-left">
                    <svg class="quick-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <div class="quick-filter-text">
                        <strong>Last 7 Days</strong>
                        <span>Weekly admissions</span>
                    </div>
                </div>
                <svg class="quick-filter-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>

            <a class="quick-filter-card blue" href="patients.php?period=month">
                <div class="quick-filter-card-left">
                    <svg class="quick-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <div class="quick-filter-text">
                        <strong>Last 30 Days</strong>
                        <span>Monthly OPD volume</span>
                    </div>
                </div>
                <svg class="quick-filter-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>

            <a class="quick-filter-card purple" href="patients.php?period=year">
                <div class="quick-filter-card-left">
                    <svg class="quick-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <div class="quick-filter-text">
                        <strong>Past Year</strong>
                        <span>Annual records</span>
                    </div>
                </div>
                <svg class="quick-filter-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>

            <a class="quick-filter-card orange" href="patients.php">
                <div class="quick-filter-card-left">
                    <svg class="quick-filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <div class="quick-filter-text">
                        <strong>All Records</strong>
                        <span>Full searchable table</span>
                    </div>
                </div>
                <svg class="quick-filter-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
        </div>

        <div class="tip-card">
            <div class="tip-icon-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="9" y1="18" x2="15" y2="18"></line>
                    <line x1="10" y1="22" x2="14" y2="22"></line>
                    <path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"></path>
                </svg>
            </div>
            <div class="tip-content">
                <strong>Reception Pro Tip</strong>
                <span>Press <kbd style="font-family:inherit; background:#e2e8f0; padding:1px 4px; border-radius:3px; font-size:11px;">Ctrl+K</kbd> anywhere to find repeat patients and register revisits in 1 click.</span>
            </div>
        </div>
    </section>
</div>

<!-- Row 3: Recent Patients Table -->
<section class="card">
    <div class="card-head">
        <div class="card-head-left">
            <div class="card-icon-badge mint">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div>
                <h2>Recent OPD Registrations</h2>
                <p>Latest patient entries registered at reception desk</p>
            </div>
        </div>
        <a class="btn-view-all" href="patients.php">
            View All Records
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
    </div>

    <div class="table-wrap">
        <table class="table-styled">
            <thead>
                <tr>
                    <th style="width: 44px;">#</th>
                    <th>UHID</th>
                    <th>PATIENT NAME</th>
                    <th>AGE</th>
                    <th>GENDER</th>
                    <th>VISIT DATE</th>
                    <th>DEPARTMENT</th>
                    <th>BILL NO.</th>
                    <th style="text-align: right; width: 230px;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state-wrap" style="padding: 24px 16px;">
                                <div class="empty-state-title">No Recent Patient Records</div>
                                <div class="empty-state-desc">Patients registered today or recently will be displayed here.</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent as $idx => $p): 
                        $sexClass = 'other';
                        $sLower = strtolower($p['sex'] ?? '');
                        if ($sLower === 'male') $sexClass = 'male';
                        elseif ($sLower === 'female') $sexClass = 'female';
                    ?>
                        <tr style="cursor: pointer;" onclick="if (!event.target.closest('a, button')) openPatientModal(<?= $p['id'] ?>)">
                            <td style="color: var(--text-muted);"><?= $idx + 1 ?></td>
                            <td>
                                <a href="javascript:void(0)" onclick="openPatientModal(<?= $p['id'] ?>)" style="font-family: monospace; font-weight: 700; color: var(--primary);" title="Click to view details">
                                    <?= e($p['uhid']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="javascript:void(0)" onclick="openPatientModal(<?= $p['id'] ?>)" style="font-weight: 700; color: var(--text-main); text-decoration: none;" title="Click to view details">
                                    <?= e($p['name']) ?>
                                </a>
                            </td>
                            <td style="color: var(--text-muted);">
                                <?= !empty($p['age']) ? e($p['age']) . ' yrs' : '<span style="color:var(--text-light); font-style:italic;">—</span>' ?>
                            </td>
                            <td>
                                <?php if (!empty($p['sex'])): ?>
                                    <span class="gender-pill <?= $sexClass ?>"><?= e(ucfirst($p['sex'])) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-light); font-style:italic;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($p['visit_date']) ?></td>
                            <td><span class="dept-badge"><?= e($p['doctor_dept'] ?: 'General OPD') ?></span></td>
                            <td><span style="font-family: monospace; font-weight: 600;"><?= e($p['bill_no']) ?></span></td>
                            <td style="text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                    <button type="button" class="btn-table-action view" onclick="openPatientModal(<?= $p['id'] ?>)" title="View Patient Details & History">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span>Details</span>
                                    </button>
                                    <a class="btn-table-action revisit" href="patient_form.php?revisit_id=<?= $p['id'] ?>" title="Register Revisit for this Patient">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                                        </svg>
                                        <span>Revisit</span>
                                    </a>
                                    <button type="button" class="btn-table-action print" onclick="openPrintModal(<?= (int)$p['id'] ?>, '<?= e(addslashes($p['name'])) ?>', '<?= e(addslashes($p['uhid'])) ?>')" title="Print OPD Slip" style="cursor: pointer; border: none; font-family: inherit;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                        <span>Print</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
