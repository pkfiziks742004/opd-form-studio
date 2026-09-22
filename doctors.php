<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/doctor_dept.php';

// Both Admin and Reception can access and manage doctors & departments
$departments = get_all_departments(false);
$doctors = get_all_doctors(false);

$activeTab = $_GET['tab'] ?? 'doctors';
$filterDept = trim($_GET['dept'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

// Handle Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_doctor') {
            $docId = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $deptName = trim($_POST['department_name'] ?? '');
            $roomNo = trim($_POST['room_no'] ?? '');
            $qualification = trim($_POST['qualification'] ?? '');
            $opdTimings = trim($_POST['opd_timings'] ?? '');
            $active = isset($_POST['active']) ? 1 : 0;

            if ($name === '') {
                throw new InvalidArgumentException('Doctor name is required.');
            }
            if ($deptName === '') {
                throw new InvalidArgumentException('Department selection is required.');
            }

            save_doctor($name, $deptName, $roomNo, $qualification, $opdTimings, $docId, $active);
            flash('success', $docId > 0 ? "Doctor '{$name}' updated successfully." : "Doctor '{$name}' added successfully.");
            header('Location: doctors.php?tab=doctors');
            exit;
        }

        if ($action === 'toggle_doctor') {
            $docId = (int)($_POST['id'] ?? 0);
            if ($docId > 0) {
                $st = db()->prepare("UPDATE doctors SET active = 1 - active WHERE id = ?");
                $st->execute([$docId]);
                flash('success', 'Doctor status updated.');
            }
            header('Location: doctors.php?tab=doctors');
            exit;
        }

        if ($action === 'delete_doctor') {
            $docId = (int)($_POST['id'] ?? 0);
            if ($docId > 0) {
                $st = db()->prepare("DELETE FROM doctors WHERE id = ?");
                $st->execute([$docId]);
                flash('success', 'Doctor removed successfully.');
            }
            header('Location: doctors.php?tab=doctors');
            exit;
        }

        if ($action === 'save_department') {
            $deptId = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $active = isset($_POST['active']) ? 1 : 0;

            if ($name === '') {
                throw new InvalidArgumentException('Department name is required.');
            }

            save_department($name, $code, $desc, $deptId, $active);
            flash('success', $deptId > 0 ? "Department '{$name}' updated successfully." : "Department '{$name}' added successfully.");
            header('Location: doctors.php?tab=departments');
            exit;
        }

        if ($action === 'toggle_department') {
            $deptId = (int)($_POST['id'] ?? 0);
            if ($deptId > 0) {
                $st = db()->prepare("UPDATE departments SET active = 1 - active WHERE id = ?");
                $st->execute([$deptId]);
                flash('success', 'Department status updated.');
            }
            header('Location: doctors.php?tab=departments');
            exit;
        }

        if ($action === 'delete_department') {
            $deptId = (int)($_POST['id'] ?? 0);
            if ($deptId > 0) {
                $st = db()->prepare("DELETE FROM departments WHERE id = ?");
                $st->execute([$deptId]);
                flash('success', 'Department removed successfully.');
            }
            header('Location: doctors.php?tab=departments');
            exit;
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        header('Location: doctors.php?tab=' . urlencode($activeTab));
        exit;
    }
}

// Filter Doctors if requested
$filteredDoctors = $doctors;
if ($filterDept !== '') {
    $filteredDoctors = array_filter($filteredDoctors, fn($d) => strcasecmp($d['department_name'], $filterDept) === 0);
}
if ($searchQuery !== '') {
    $filteredDoctors = array_filter($filteredDoctors, fn($d) => 
        stripos($d['name'], $searchQuery) !== false || 
        stripos($d['department_name'], $searchQuery) !== false || 
        stripos($d['room_no'] ?? '', $searchQuery) !== false ||
        stripos($d['qualification'] ?? '', $searchQuery) !== false
    );
}
?>

<style>
.doc-management-shell {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Header & Tabs */
.doc-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    background: #ffffff;
    padding: 16px 20px;
    border-radius: 4px;
    border: 1px solid var(--card-border);
    box-shadow: var(--shadow-subtle);
}

.doc-nav-tabs {
    display: flex;
    gap: 8px;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 4px;
}

.doc-nav-tab {
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    border-radius: 3px;
    transition: all 0.16s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.doc-nav-tab:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
}

.doc-nav-tab.active {
    background: #ffffff;
    color: var(--primary);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.doc-tab-count {
    font-size: 11px;
    background: #e2e8f0;
    color: #475569;
    padding: 1px 7px;
    border-radius: 10px;
}

.doc-nav-tab.active .doc-tab-count {
    background: var(--primary-light);
    color: var(--primary-dark);
}

.doc-actions-top {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Filter Bar */
.doc-filter-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    background: #ffffff;
    padding: 12px 18px;
    border-radius: 4px;
    border: 1px solid var(--card-border);
}

.doc-search-box {
    position: relative;
    flex: 1;
    min-width: 220px;
}

.doc-search-box svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 15px;
    height: 15px;
    color: #94a3b8;
    pointer-events: none;
}

.doc-search-input {
    width: 100%;
    padding: 8px 12px 8px 34px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 13px;
    outline: none;
    transition: border-color 0.15s ease;
}

.doc-search-input:focus {
    border-color: var(--primary);
}

.doc-select-filter {
    padding: 8px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 13px;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    cursor: pointer;
}

/* Doctors Grid / Cards */
.doctors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}

.doctor-card {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: 4px;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: var(--shadow-subtle);
    transition: all 0.18s ease;
    position: relative;
}

.doctor-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}

.doctor-card.inactive {
    opacity: 0.65;
    background: #f8fafc;
}

.doctor-card-top {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 12px;
}

.doctor-avatar {
    width: 44px;
    height: 44px;
    border-radius: 4px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(8, 127, 108, 0.25);
}

.doctor-info-head {
    flex: 1;
    min-width: 0;
}

.doctor-name {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.dept-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 3px;
    background: #e6f8f3;
    color: var(--primary-dark);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.doctor-details-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 10px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 12px;
    font-size: 12.5px;
    color: #475569;
}

.doc-detail-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.doc-detail-row svg {
    width: 14px;
    height: 14px;
    color: #64748b;
    flex-shrink: 0;
}

.doc-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.status-badge-active {
    font-size: 11px;
    font-weight: 700;
    color: #15803d;
    background: #dcfce7;
    padding: 2px 8px;
    border-radius: 3px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.status-badge-inactive {
    font-size: 11px;
    font-weight: 700;
    color: #b91c1c;
    background: #fee2e2;
    padding: 2px 8px;
    border-radius: 3px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.doc-btn-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-doc-edit, .btn-doc-del, .btn-doc-toggle {
    border: none;
    background: #f1f5f9;
    color: #334155;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.btn-doc-edit:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.btn-doc-del:hover {
    background: #fee2e2;
    color: #dc2626;
}

/* Departments Table */
.depts-table-card {
    background: #ffffff;
    border: 1px solid var(--card-border);
    border-radius: 4px;
    overflow: hidden;
    box-shadow: var(--shadow-subtle);
}

.depts-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 13px;
}

.depts-table th {
    background: #f8fafc;
    padding: 12px 18px;
    font-weight: 700;
    color: #475569;
    border-bottom: 1px solid var(--card-border);
}

.depts-table td {
    padding: 12px 18px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
}

.depts-table tr:hover td {
    background: #fbfcfe;
}

/* Modals */
.doc-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.doc-modal-box {
    background: #ffffff;
    width: 100%;
    max-width: 520px;
    border-radius: 4px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    border: 1px solid #cbd5e1;
    animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalPop {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.doc-modal-header {
    background: linear-gradient(135deg, #073F38 0%, #087F6C 100%);
    color: #ffffff;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.doc-modal-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
}

.btn-doc-modal-close {
    background: transparent;
    border: none;
    color: #ffffff;
    font-size: 20px;
    cursor: pointer;
    line-height: 1;
}

.doc-modal-body {
    padding: 20px;
}

.doc-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 14px;
}

.doc-form-group label {
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
}

.doc-form-input, .doc-form-select {
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 13px;
    outline: none;
    width: 100%;
}

.doc-form-input:focus, .doc-form-select:focus {
    border-color: var(--primary);
}

.doc-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.doc-modal-footer {
    padding: 12px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<div class="doc-management-shell">
    <!-- Top Action & Navigation Bar -->
    <div class="doc-header-row">
        <div class="doc-nav-tabs">
            <a href="doctors.php?tab=doctors" class="doc-nav-tab <?= $activeTab === 'doctors' ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <polyline points="16 11 18 13 22 9"></polyline>
                </svg>
                <span>Doctors</span>
                <span class="doc-tab-count"><?= count($doctors) ?></span>
            </a>
            <a href="doctors.php?tab=departments" class="doc-nav-tab <?= $activeTab === 'departments' ? 'active' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                    <path d="M3 9h18"></path>
                    <path d="M9 21V9"></path>
                </svg>
                <span>Departments</span>
                <span class="doc-tab-count"><?= count($departments) ?></span>
            </a>
        </div>

        <div class="doc-actions-top">
            <?php if ($activeTab === 'doctors'): ?>
                <button type="button" class="btn btn-primary" onclick="openDoctorModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Add New Doctor</span>
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-primary" onclick="openDeptModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Add Department</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab 1: Doctors Management -->
    <?php if ($activeTab === 'doctors'): ?>
        <!-- Search and Filter -->
        <form method="get" action="doctors.php" class="doc-filter-bar">
            <input type="hidden" name="tab" value="doctors">
            <div class="doc-search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="q" value="<?= e($searchQuery) ?>" placeholder="Search doctor by name, cabin, specialty..." class="doc-search-input">
            </div>

            <select name="dept" class="doc-select-filter" onchange="this.form.submit()">
                <option value="">All Departments (<?= count($departments) ?>)</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= e($dept['name']) ?>" <?= $filterDept === $dept['name'] ? 'selected' : '' ?>>
                        <?= e($dept['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($searchQuery !== '' || $filterDept !== ''): ?>
                <a href="doctors.php?tab=doctors" class="btn btn-secondary" style="padding: 8px 12px; font-size: 12px;">Clear Filters</a>
            <?php endif; ?>
        </form>

        <!-- Doctors Grid -->
        <?php if (empty($filteredDoctors)): ?>
            <div class="card empty" style="padding: 40px; text-align: center; background: #ffffff; border-radius: 4px;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" style="margin: 0 auto 12px; display: block;">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                <h3 style="margin: 0 0 6px; font-size: 16px; color: #1e293b;">No Doctors Found</h3>
                <p style="margin: 0 0 16px; color: #64748b; font-size: 13px;">No medical staff matching your current filter criteria.</p>
                <button type="button" class="btn btn-primary" onclick="openDoctorModal()">+ Add First Doctor</button>
            </div>
        <?php else: ?>
            <div class="doctors-grid">
                <?php foreach ($filteredDoctors as $doc): ?>
                    <div class="doctor-card <?= empty($doc['active']) ? 'inactive' : '' ?>">
                        <div>
                            <div class="doctor-card-top">
                                <div class="doctor-avatar">
                                    <?= mb_substr(preg_replace('/^Dr\.?\s*/i', '', $doc['name']), 0, 1) ?>
                                </div>
                                <div class="doctor-info-head">
                                    <div class="doctor-name">
                                        <span><?= e($doc['name']) ?></span>
                                    </div>
                                    <span class="dept-pill">
                                        <?= e($doc['department_name']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="doctor-details-list">
                                <div class="doc-detail-row">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 4v16"></path><path d="M2 8h18a2 2 0 0 1 2 2v10"></path><path d="M2 17h20"></path><path d="M6 8v9"></path>
                                    </svg>
                                    <span>Room / Cabin: <strong><?= e($doc['room_no'] ?: 'Not assigned') ?></strong></span>
                                </div>
                                <?php if (!empty($doc['qualification'])): ?>
                                    <div class="doc-detail-row">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                                        </svg>
                                        <span title="<?= e($doc['qualification']) ?>" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?= e($doc['qualification']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($doc['opd_timings'])): ?>
                                    <div class="doc-detail-row">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span><?= e($doc['opd_timings']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="doc-card-footer">
                            <div>
                                <?php if (!empty($doc['active'])): ?>
                                    <span class="status-badge-active">● Active</span>
                                <?php else: ?>
                                    <span class="status-badge-inactive">○ Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="doc-btn-group">
                                <button type="button" class="btn-doc-edit" onclick='editDoctor(<?= json_encode($doc, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>Edit</button>
                                <form method="post" action="doctors.php" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_doctor">
                                    <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
                                    <button type="submit" class="btn-doc-toggle" title="Toggle active status">
                                        <?= !empty($doc['active']) ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <form method="post" action="doctors.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this doctor?');">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_doctor">
                                    <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
                                    <button type="submit" class="btn-doc-del">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- Tab 2: Departments Management -->
    <?php else: ?>
        <div class="depts-table-card">
            <table class="depts-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Department Name</th>
                        <th>Short Code</th>
                        <th>Description</th>
                        <th style="text-align: center;">Assigned Doctors</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">No departments found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($departments as $idx => $d): ?>
                            <tr>
                                <td style="color: #94a3b8; font-weight: 700;"><?= $idx + 1 ?></td>
                                <td>
                                    <strong style="color: #0f172a; font-size: 14px;"><?= e($d['name']) ?></strong>
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-size: 11.5px; background: #f1f5f9; padding: 2px 7px; border-radius: 3px; font-weight: 700; color: #475569;">
                                        <?= e($d['code'] ?: '—') ?>
                                    </span>
                                </td>
                                <td style="color: #64748b; font-size: 12px;"><?= e($d['description'] ?: '—') ?></td>
                                <td style="text-align: center;">
                                    <a href="doctors.php?tab=doctors&dept=<?= urlencode($d['name']) ?>" class="dept-pill" style="text-decoration:none;">
                                        <?= (int)$d['doctor_count'] ?> <?= ((int)$d['doctor_count'] === 1 ? 'Doctor' : 'Doctors') ?>
                                    </a>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!empty($d['active'])): ?>
                                        <span class="status-badge-active">● Active</span>
                                    <?php else: ?>
                                        <span class="status-badge-inactive">○ Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="doc-btn-group" style="justify-content: flex-end;">
                                        <button type="button" class="btn-doc-edit" onclick='editDept(<?= json_encode($d, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>Edit</button>
                                        <form method="post" action="doctors.php" style="display:inline;">
                                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="toggle_department">
                                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                            <button type="submit" class="btn-doc-toggle">
                                                <?= !empty($d['active']) ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                        <form method="post" action="doctors.php" style="display:inline;" onsubmit="return confirm('Deleting this department will unassign its doctors. Continue?');">
                                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete_department">
                                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                            <button type="submit" class="btn-doc-del">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================================================= -->
<!-- Modal: Add / Edit Doctor                                                 -->
<!-- ========================================================================= -->
<div id="doctorModal" class="doc-modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
    <div class="doc-modal-box">
        <div class="doc-modal-header">
            <h3 id="docModalTitle">Add New Doctor</h3>
            <button type="button" class="btn-doc-modal-close" onclick="closeDoctorModal()">&times;</button>
        </div>
        <form method="post" action="doctors.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save_doctor">
            <input type="hidden" name="id" id="docFormId" value="0">

            <div class="doc-modal-body">
                <div class="doc-form-group">
                    <label for="docFormName">Doctor Full Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" id="docFormName" class="doc-form-input" placeholder="e.g. Dr. Ramesh Sharma" required>
                </div>

                <div class="doc-form-row">
                    <div class="doc-form-group">
                        <label for="docFormDept">Department <span style="color:#ef4444;">*</span></label>
                        <select name="department_name" id="docFormDept" class="doc-form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= e($d['name']) ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="doc-form-group">
                        <label for="docFormRoom">Room / Cabin No.</label>
                        <input type="text" name="room_no" id="docFormRoom" class="doc-form-input" placeholder="e.g. 102, Cabin B">
                    </div>
                </div>

                <div class="doc-form-group">
                    <label for="docFormQual">Qualifications / Specialization</label>
                    <input type="text" name="qualification" id="docFormQual" class="doc-form-input" placeholder="e.g. MBBS, MS, Fellowship">
                </div>

                <div class="doc-form-group">
                    <label for="docFormTimings">OPD Timings / Consultation Hours</label>
                    <input type="text" name="opd_timings" id="docFormTimings" class="doc-form-input" placeholder="e.g. 10:00 AM - 02:00 PM (Mon-Sat)">
                </div>

                <div style="margin-top: 10px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600; color:#334155;">
                        <input type="checkbox" name="active" id="docFormActive" value="1" checked>
                        <span>Active & Available for OPD Registration</span>
                    </label>
                </div>
            </div>

            <div class="doc-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDoctorModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="docFormSubmitBtn">Save Doctor</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Add / Edit Department                                             -->
<!-- ========================================================================= -->
<div id="deptModal" class="doc-modal-backdrop" style="display: none;" role="dialog" aria-modal="true">
    <div class="doc-modal-box" style="max-width: 440px;">
        <div class="doc-modal-header">
            <h3 id="deptModalTitle">Add Department</h3>
            <button type="button" class="btn-doc-modal-close" onclick="closeDeptModal()">&times;</button>
        </div>
        <form method="post" action="doctors.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="save_department">
            <input type="hidden" name="id" id="deptFormId" value="0">

            <div class="doc-modal-body">
                <div class="doc-form-group">
                    <label for="deptFormName">Department Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" id="deptFormName" class="doc-form-input" placeholder="e.g. Cardiology, Orthopedics" required>
                </div>

                <div class="doc-form-group">
                    <label for="deptFormCode">Short Code (Optional)</label>
                    <input type="text" name="code" id="deptFormCode" class="doc-form-input" placeholder="e.g. CARDIO, ORTHO">
                </div>

                <div class="doc-form-group">
                    <label for="deptFormDesc">Description (Optional)</label>
                    <input type="text" name="description" id="deptFormDesc" class="doc-form-input" placeholder="Brief note or floor info">
                </div>

                <div style="margin-top: 10px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600; color:#334155;">
                        <input type="checkbox" name="active" id="deptFormActive" value="1" checked>
                        <span>Active Department</span>
                    </label>
                </div>
            </div>

            <div class="doc-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeptModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="deptFormSubmitBtn">Save Department</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDoctorModal() {
    document.getElementById('docModalTitle').textContent = 'Add New Doctor';
    document.getElementById('docFormId').value = '0';
    document.getElementById('docFormName').value = '';
    document.getElementById('docFormDept').value = '';
    document.getElementById('docFormRoom').value = '';
    document.getElementById('docFormQual').value = '';
    document.getElementById('docFormTimings').value = '';
    document.getElementById('docFormActive').checked = true;
    document.getElementById('docFormSubmitBtn').textContent = 'Save Doctor';
    document.getElementById('doctorModal').style.display = 'flex';
    document.getElementById('docFormName').focus();
}

function editDoctor(doc) {
    document.getElementById('docModalTitle').textContent = 'Edit Doctor Details';
    document.getElementById('docFormId').value = doc.id;
    document.getElementById('docFormName').value = doc.name || '';
    document.getElementById('docFormDept').value = doc.department_name || '';
    document.getElementById('docFormRoom').value = doc.room_no || '';
    document.getElementById('docFormQual').value = doc.qualification || '';
    document.getElementById('docFormTimings').value = doc.opd_timings || '';
    document.getElementById('docFormActive').checked = (parseInt(doc.active, 10) === 1);
    document.getElementById('docFormSubmitBtn').textContent = 'Update Doctor';
    document.getElementById('doctorModal').style.display = 'flex';
    document.getElementById('docFormName').focus();
}

function closeDoctorModal() {
    document.getElementById('doctorModal').style.display = 'none';
}

function openDeptModal() {
    document.getElementById('deptModalTitle').textContent = 'Add Department';
    document.getElementById('deptFormId').value = '0';
    document.getElementById('deptFormName').value = '';
    document.getElementById('deptFormCode').value = '';
    document.getElementById('deptFormDesc').value = '';
    document.getElementById('deptFormActive').checked = true;
    document.getElementById('deptFormSubmitBtn').textContent = 'Save Department';
    document.getElementById('deptModal').style.display = 'flex';
    document.getElementById('deptFormName').focus();
}

function editDept(d) {
    document.getElementById('deptModalTitle').textContent = 'Edit Department';
    document.getElementById('deptFormId').value = d.id;
    document.getElementById('deptFormName').value = d.name || '';
    document.getElementById('deptFormCode').value = d.code || '';
    document.getElementById('deptFormDesc').value = d.description || '';
    document.getElementById('deptFormActive').checked = (parseInt(d.active, 10) === 1);
    document.getElementById('deptFormSubmitBtn').textContent = 'Update Department';
    document.getElementById('deptModal').style.display = 'flex';
    document.getElementById('deptFormName').focus();
}

function closeDeptModal() {
    document.getElementById('deptModal').style.display = 'none';
}

// Close modals on clicking backdrop
window.addEventListener('click', function(e) {
    const docModal = document.getElementById('doctorModal');
    const deptModal = document.getElementById('deptModal');
    if (e.target === docModal) closeDoctorModal();
    if (e.target === deptModal) closeDeptModal();
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
