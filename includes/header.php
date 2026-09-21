<?php
require_once __DIR__ . '/auth.php';
$user = require_login();
$flashes = pull_flashes();
$current = basename($_SERVER['PHP_SELF']);
$pageTitles = [
    'dashboard.php' => ['Dashboard', "Overview of today's OPD activity & patient visits"],
    'patient_form.php' => ['New OPD Registration', 'Register patient & generate printable slip'],
    'patients.php' => ['Patient Directory', 'Search, filter and manage patient history'],
    'template_editor.php' => ['Layout Editor', 'Configure print alignment & slip fields'],
    'templates.php' => ['OPD Templates', 'Manage print slip layouts & paper settings'],
    'users.php' => ['Users & Permissions', 'Manage hospital staff and reception logins'],
    'settings.php' => ['Clinic Settings', 'Hospital details, print parameters & preferences'],
];
$currentTitleInfo = $pageTitles[$current] ?? ['OPD Form Studio', 'Hospital Management System'];
$topTitle = $currentTitleInfo[0];
$topSubtitle = $currentTitleInfo[1];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="user-role" content="<?= e($user['role']) ?>">
    <title><?= e($topTitle) ?> — <?= e(envv('APP_NAME', 'OPD Form Studio')) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <aside class="sidebar" id="appSidebar">
        <a class="brand brand-link" href="dashboard.php" title="PLUS CODE — OPD Studio">
            <div class="brand-logo-card">
                <img src="assets/pluscode-logo.png" alt="PLUS CODE — WE DESIGN | WE DEVELOP | WE DELIVER" class="brand-logo-img">
            </div>
        </a>

        <nav>
            <a class="<?= $current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Dashboard
            </a>

            <a class="<?= in_array($current, ['patient_form.php']) ? 'active' : '' ?>" href="patient_form.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <line x1="19" y1="8" x2="19" y2="14"></line>
                    <line x1="22" y1="11" x2="16" y2="11"></line>
                </svg>
                New OPD
            </a>

            <a class="<?= $current === 'patients.php' ? 'active' : '' ?>" href="patients.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Patients
            </a>

            <a class="<?= $current === 'template_editor.php' ? 'active' : '' ?>" href="template_editor.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                    <path d="M3 9h18"></path>
                    <path d="M9 21V9"></path>
                </svg>
                Layout Editor
            </a>

            <?php if ($user['role'] === 'admin'): ?>
                <div class="nav-section">ADMINISTRATION</div>

                <a class="<?= $current === 'templates.php' ? 'active' : '' ?>" href="templates.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    Templates
                </a>

                <a class="<?= $current === 'users.php' ? 'active' : '' ?>" href="users.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <polyline points="16 11 18 13 22 9"></polyline>
                    </svg>
                    Users & Permissions
                </a>

                <a class="<?= $current === 'settings.php' ? 'active' : '' ?>" href="settings.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    Settings
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile-chip">
                <div class="user-avatar-circle">
                    <?= e(strtoupper(substr($user['name'], 0, 1))) ?>
                </div>
                <div>
                    <strong><?= e($user['name']) ?></strong>
                    <small><?= e(ucfirst($user['role'])) ?></small>
                </div>
            </div>
            <a class="sign-out-link" href="logout.php" title="Sign out">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Sign out
            </a>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="btn-sidebar-toggle" id="btnSidebarToggle" aria-label="Toggle navigation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div class="topbar-titles">
                    <h1><?= e($topTitle) ?></h1>
                    <p><?= e($topSubtitle) ?></p>
                </div>
            </div>

            <div class="topbar-right">
                <!-- Live Patient Search -->
                <div class="topbar-search-wrap" id="topbarSearchWrap">
                    <svg class="topbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="topbarSearchInput" class="topbar-search-input" placeholder="Search patient, UHID..." autocomplete="off">
                    <kbd class="topbar-search-kbd">Ctrl+K</kbd>
                    <div class="topbar-search-dropdown" id="topbarSearchDropdown"></div>
                </div>

                <!-- Clinic Status -->
                <div class="clinic-status-badge" title="Reception Desk is Online and operational">
                    <span class="status-pulse-dot"></span>
                    <span>Reception Active</span>
                </div>

                <!-- Notification Icon & Dropdown -->
                <div class="topbar-notif-wrap" id="topbarNotifWrap">
                    <button type="button" class="topbar-icon-btn" id="topbarNotifBtn" title="Notifications & Announcements" aria-expanded="false" aria-haspopup="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="notification-badge" id="topbarNotifBadge" style="display:none;">0</span>
                    </button>

                    <!-- Dropdown Panel -->
                    <div class="notif-dropdown-panel" id="notifDropdownPanel">
                        <div class="notif-dropdown-header">
                            <div class="notif-header-title">
                                <div class="notif-title-row">
                                    <h3>Announcements</h3>
                                    <span class="notif-pill-count" id="notifUnreadPill">0 new</span>
                                </div>
                                <span class="notif-header-sub">Broadcasts • Auto-expires in 24h</span>
                            </div>
                            <div class="notif-header-actions">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <button type="button" class="btn-compose-notice" id="btnOpenComposeNotice" title="Broadcast notice to reception">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                        <span>Send</span>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn-mark-all-read" id="btnMarkAllRead" title="Mark all notifications as read">
                                    Mark read
                                </button>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <button type="button" class="btn-clear-all-notifs" id="btnClearAllNotifs" title="Delete all announcements">
                                        Clear all
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="notif-dropdown-body" id="notifDropdownList">
                            <div class="notif-loading-state">
                                <div class="notif-spinner"></div>
                                <span>Loading notifications...</span>
                            </div>
                        </div>

                        <div class="notif-dropdown-footer">
                            <span class="notif-role-indicator">
                                <span class="notif-live-dot"></span>
                                Connected as <strong><?= e(ucfirst($user['role'])) ?></strong>
                            </span>
                            <button type="button" class="btn-refresh-notifs" id="btnRefreshNotifs" title="Refresh now">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                <span>Sync</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- New Patient CTA Button -->
                <a class="btn-new-patient" href="patient_form.php">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>New OPD</span>
                </a>
            </div>
        </header>

        <section class="content">
            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
