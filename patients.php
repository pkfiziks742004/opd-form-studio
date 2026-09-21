<?php
require_once __DIR__ . '/includes/header.php';

$period = $_GET['period'] ?? '';
$q = trim($_GET['q'] ?? '');
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = [];
$params = [];

if ($period === 'today') {
    $where[] = 'p.visit_date = CURDATE()';
} elseif ($period === 'week') {
    $where[] = 'p.visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)';
} elseif ($period === 'month') {
    $where[] = 'p.visit_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
} elseif ($period === 'year') {
    $where[] = 'p.visit_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
}

if ($from) {
    $where[] = 'p.visit_date >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'p.visit_date <= ?';
    $params[] = $to;
}
if ($q) {
    $where[] = '(p.name LIKE ? OR p.uhid LIKE ? OR p.bill_no LIKE ? OR p.contact_number LIKE ?)';
    for ($i = 0; $i < 4; $i++) {
        $params[] = '%' . $q . '%';
    }
}

$sql = 'SELECT p.*, u.name AS created_by_name FROM patients p LEFT JOIN users u ON u.id = p.created_by' 
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') 
     . ' ORDER BY p.id DESC LIMIT 500';

$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
$totalCount = count($rows);
?>

<!-- Page Header Banner -->
<div class="page-title-banner">
    <div class="page-title-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
    </div>
    <div class="page-title-text" style="flex: 1;">
        <h2>Patient Directory & OPD History</h2>
        <p>Search, filter, view patient details, or register a revisit with one click.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="patient_form.php" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px; font-weight: 700; box-shadow: 0 4px 14px rgba(13, 165, 116, 0.28);">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>New Registration</span>
        </a>
    </div>
</div>

<section class="card">
    <!-- Quick Filter Tabs (Pills) for Rapid Reception Workflow -->
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
            <a href="patients.php" class="btn <?= empty($period) && empty($from) && empty($to) ? 'btn-primary' : 'btn-soft' ?>" style="padding: 5px 12px; font-size: 12px; border-radius: 4px; text-decoration: none;">
                All Patients
            </a>
            <a href="patients.php?period=today" class="btn <?= $period === 'today' ? 'btn-primary' : 'btn-soft' ?>" style="padding: 5px 12px; font-size: 12px; border-radius: 4px; text-decoration: none;">
                Today
            </a>
            <a href="patients.php?period=week" class="btn <?= $period === 'week' ? 'btn-primary' : 'btn-soft' ?>" style="padding: 5px 12px; font-size: 12px; border-radius: 4px; text-decoration: none;">
                Last 7 Days
            </a>
            <a href="patients.php?period=month" class="btn <?= $period === 'month' ? 'btn-primary' : 'btn-soft' ?>" style="padding: 5px 12px; font-size: 12px; border-radius: 4px; text-decoration: none;">
                This Month
            </a>
        </div>
        <div style="font-size: 12px; color: var(--text-muted);">
            Quickly filter registrations by date or use detailed search below
        </div>
    </div>

    <!-- Filter Bar -->
    <form class="filter-bar" method="get" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 16px;">
        <div style="flex: 1; min-width: 220px; position: relative;">
            <input name="q" value="<?= e($q) ?>" placeholder="Search name, UHID, phone, or bill..." style="width: 100%; height: 36px; padding-left: 34px; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: #ffffff;">
            <svg style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: #64748b; pointer-events: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </div>
        
        <select name="period" style="height: 36px; border-radius: 4px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 13px; background: #fff; outline: none;">
            <option value="">Any Period</option>
            <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Last 7 days</option>
            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Last month</option>
            <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Last year</option>
        </select>

        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 12px; color: var(--text-muted);">From:</span>
            <input type="date" name="from" value="<?= e($from) ?>" style="height: 36px; border-radius: 4px; border: 1px solid #cbd5e1; padding: 0 8px; font-size: 12.5px; background: #fff; outline: none;">
            <span style="font-size: 12px; color: var(--text-muted);">To:</span>
            <input type="date" name="to" value="<?= e($to) ?>" style="height: 36px; border-radius: 4px; border: 1px solid #cbd5e1; padding: 0 8px; font-size: 12.5px; background: #fff; outline: none;">
        </div>

        <button type="submit" class="btn btn-primary" style="height: 36px; padding: 0 14px; font-weight: 700; border-radius: 4px;">Filter</button>
        <?php if ($q || $period || $from || $to): ?>
            <a class="btn btn-soft" href="patients.php" style="height: 36px; display: inline-flex; align-items: center; padding: 0 12px; border-radius: 4px;">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Results Meta Bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; font-size: 12.5px; color: var(--text-muted);">
        <div>
            Showing <strong><?= $totalCount ?></strong> <?= $totalCount === 1 ? 'patient record' : 'patient records' ?>
            <?php if ($q): ?> for search "<strong><?= e($q) ?></strong>"<?php endif; ?>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--primary);"></span>
            <span>Tip: Click on any patient to view details &amp; history</span>
        </div>
    </div>

    <!-- Patients Table -->
    <div class="table-wrap">
        <table class="table-styled">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>UHID</th>
                    <th>PATIENT NAME</th>
                    <th>CONTACT</th>
                    <th>VISIT DATE</th>
                    <th>DEPARTMENT</th>
                    <th>BILL NO.</th>
                    <th>PANEL</th>
                    <th style="text-align: right; width: 230px;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9" class="empty" style="text-align: center; padding: 36px 16px;">
                            <div style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">No patient records found</div>
                            <span style="color: var(--text-muted); font-size: 13px;">Try adjusting your search terms or date filters.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $idx => $p): 
                        $ageSex = trim(($p['age'] ? $p['age'] . ' Y' : '') . ' ' . ($p['sex'] ?: ''));
                    ?>
                        <tr style="cursor: pointer;" onclick="if (!event.target.closest('a, button')) openPatientModal(<?= $p['id'] ?>)">
                            <td style="color: var(--text-muted);"><?= $idx + 1 ?></td>
                            <td>
                                <a href="javascript:void(0)" onclick="openPatientModal(<?= $p['id'] ?>)" style="font-family: monospace; font-weight: 700; color: var(--primary-dark);" title="Click to view details">
                                    <?= e($p['uhid']) ?>
                                </a>
                            </td>
                            <td>
                                <div>
                                    <a href="javascript:void(0)" onclick="openPatientModal(<?= $p['id'] ?>)" style="font-weight: 800; color: var(--text-main); font-size: 13.5px;" title="Click to view patient details">
                                        <?= e($p['name']) ?>
                                    </a>
                                    <?php if ($ageSex): ?>
                                        <small style="display: block; color: var(--text-muted); font-size: 11.5px; margin-top: 1px;">
                                            <?= e($ageSex) ?>
                                            <?php if ($p['guardian']): ?> &bull; S/W/D of <?= e($p['guardian']) ?><?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= e($p['contact_number'] ?: '—') ?></td>
                            <td>
                                <strong><?= e(date('d M Y', strtotime($p['visit_date']))) ?></strong>
                                <?php if ($p['visit_time']): ?>
                                    <small style="display: block; color: var(--text-muted); font-size: 11px;"><?= e(date('h:i A', strtotime($p['visit_time']))) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="dept-badge"><?= e($p['doctor_dept'] ?: 'General') ?></span></td>
                            <td><span style="font-family: monospace; font-weight: 600; color: var(--text-main);"><?= e($p['bill_no']) ?></span></td>
                            <td><span class="panel-tag"><?= e($p['panel'] ?: 'CASH') ?></span></td>
                            <td style="text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                    <!-- View Details Button -->
                                    <button type="button" class="btn-table-action view" onclick="openPatientModal(<?= $p['id'] ?>)" title="View Full Patient Details & Visit History">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span>Details</span>
                                    </button>

                                    <!-- Revisit Patient Button -->
                                    <a class="btn-table-action revisit" href="patient_form.php?revisit_id=<?= $p['id'] ?>" title="Register New OPD Visit for this Patient">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                                        </svg>
                                        <span>Revisit</span>
                                    </a>

                                    <!-- Print Slip Button -->
                                    <a class="btn-table-action print" href="print_opd.php?id=<?= $p['id'] ?>" target="_blank" title="Print OPD Slip">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                        <span>Print</span>
                                    </a>
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
