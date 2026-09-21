// OPD Form Studio - Global Application Scripts

// 1. Data Auto-Generators (UHID, Bill No)
document.addEventListener('click', e => {
    const b = e.target.closest('[data-generate]');
    if (!b) return;
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
    const type = b.dataset.generate;
    if (type === 'uhid') {
        const el = document.getElementById('uhid');
        if (el) {
            el.value = `UHID-${stamp}`;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
    if (type === 'bill') {
        const el = document.getElementById('bill_no');
        if (el) {
            el.value = `BILL-${stamp}`;
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
});

// 2. Mobile Responsive Sidebar Drawer
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('btnSidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-open');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', () => {
            document.body.classList.remove('sidebar-open');
        });
    }

    // 3. Global Topbar Live Patient Search
    const searchInput = document.getElementById('topbarSearchInput');
    const searchDropdown = document.getElementById('topbarSearchDropdown');
    const searchWrap = document.getElementById('topbarSearchWrap');

    if (searchInput && searchDropdown) {
        let debounceTimer = null;

        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const query = searchInput.value.trim();

            if (query.length < 2) {
                searchDropdown.style.display = 'none';
                searchDropdown.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`patient_ajax.php?action=search&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        const patients = Array.isArray(data) ? data : (data?.patients || []);
                        if (!patients || patients.length === 0) {
                            searchDropdown.innerHTML = `
                                <div style="padding: 12px 16px; font-size: 12.5px; color: var(--text-muted); text-align: center;">
                                    No patients matching "<strong>${query.replace(/</g, '&lt;')}</strong>"
                                </div>
                            `;
                            searchDropdown.style.display = 'block';
                            return;
                        }

                        let html = '';
                        patients.slice(0, 6).forEach(p => {
                            html += `
                                <div class="topbar-search-item" data-patient-id="${p.id}">
                                    <div>
                                        <div style="font-weight: 700; font-size: 13px; color: var(--text-main);">${p.name || 'Unknown'}</div>
                                        <div style="font-size: 11px; color: var(--text-muted); display: flex; gap: 8px; margin-top: 2px;">
                                            <span>UHID: <strong style="font-family: monospace; color: var(--primary);">${p.uhid || '—'}</strong></span>
                                            ${p.contact_number ? `<span>Phone: ${p.contact_number}</span>` : ''}
                                        </div>
                                    </div>
                                    <span style="font-size: 11px; font-weight: 600; color: var(--primary); background: var(--primary-light); padding: 2px 7px; border-radius: 4px;">
                                        View
                                    </span>
                                </div>
                            `;
                        });

                        html += `
                            <a href="patients.php?q=${encodeURIComponent(query)}" style="display: block; padding: 9px 14px; background: #F8FAFC; border-top: 1px solid var(--card-border); font-size: 12px; font-weight: 600; color: var(--primary); text-align: center; text-decoration: none;">
                                View all results for "${query.replace(/</g, '&lt;')}" →
                            </a>
                        `;

                        searchDropdown.innerHTML = html;
                        searchDropdown.style.display = 'block';
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                    });
            }, 200);
        });

        searchDropdown.addEventListener('click', e => {
            const item = e.target.closest('.topbar-search-item');
            if (!item) return;
            const patientId = item.dataset.patientId;
            searchDropdown.style.display = 'none';
            searchInput.value = '';
            if (typeof openPatientModal === 'function') {
                openPatientModal(patientId);
            } else {
                window.location.href = `patients.php?q=${encodeURIComponent(patientId)}`;
            }
        });

        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = `patients.php?q=${encodeURIComponent(query)}`;
                }
            } else if (e.key === 'Escape') {
                searchDropdown.style.display = 'none';
            }
        });

        // Close on click outside
        document.addEventListener('click', e => {
            if (searchWrap && !searchWrap.contains(e.target)) {
                searchDropdown.style.display = 'none';
            }
        });

        // Global Keyboard Shortcut: Ctrl+K or Cmd+K to focus search
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }

    // 4. Notifications & Admin-to-Reception Broadcast System
    initNotificationSystem();
});

function initNotificationSystem() {
    const notifBtn = document.getElementById('topbarNotifBtn');
    const notifBadge = document.getElementById('topbarNotifBadge');
    const dropdown = document.getElementById('notifDropdownPanel');
    const dropdownList = document.getElementById('notifDropdownList');
    const unreadPill = document.getElementById('notifUnreadPill');
    const markAllBtn = document.getElementById('btnMarkAllRead');
    const clearAllBtn = document.getElementById('btnClearAllNotifs');
    const refreshBtn = document.getElementById('btnRefreshNotifs');

    const userRoleMeta = document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '';
    const isAdmin = (userRoleMeta === 'admin');

    // Admin Compose Modal Elements
    const openComposeBtn = document.getElementById('btnOpenComposeNotice');
    const composeModal = document.getElementById('adminNoticeModal');
    const closeComposeBtn = document.getElementById('btnCloseComposeNotice');
    const cancelComposeBtn = document.getElementById('btnCancelComposeNotice');
    const composeForm = document.getElementById('composeNoticeForm');
    const messageInput = document.getElementById('noticeMessage');
    const charCounter = document.getElementById('noticeCharCount');
    const submitBtn = document.getElementById('btnSubmitNotice');
    const submitBtnText = document.getElementById('btnSubmitNoticeText');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let isFetching = false;
    let cachedNotifications = [];

    // Fetch Notifications from Server
    function fetchNotifications(showLoading = false) {
        if (isFetching) return;
        isFetching = true;

        if (showLoading && dropdownList) {
            dropdownList.innerHTML = `
                <div class="notif-loading-state">
                    <div class="notif-spinner"></div>
                    <span>Loading announcements...</span>
                </div>
            `;
        }

        fetch('api/notifications.php?action=fetch')
            .then(res => res.json())
            .then(data => {
                isFetching = false;
                if (!data || !data.success) return;

                const unread = data.unread_count || 0;
                cachedNotifications = data.notifications || [];

                // Update badge in topbar
                if (notifBadge) {
                    if (unread > 0) {
                        notifBadge.textContent = unread > 99 ? '99+' : unread;
                        notifBadge.style.display = 'flex';
                    } else {
                        notifBadge.style.display = 'none';
                    }
                }

                // Update unread pill in dropdown header
                if (unreadPill) {
                    unreadPill.textContent = unread > 0 ? `${unread} new` : 'All caught up';
                    unreadPill.style.background = unread > 0 ? '#EFF6FF' : '#F1F5F9';
                    unreadPill.style.color = unread > 0 ? '#2563EB' : '#64748B';
                    unreadPill.style.borderColor = unread > 0 ? '#DBEAFE' : '#E2E8F0';
                }

                // Render list
                renderNotificationList(cachedNotifications, data.is_admin || isAdmin);
            })
            .catch(err => {
                isFetching = false;
                console.error('Failed to load notifications:', err);
                if (showLoading && dropdownList) {
                    dropdownList.innerHTML = `
                        <div class="notif-empty-state">
                            <p style="color: #EF4444;">Unable to sync notices. Check connection.</p>
                        </div>
                    `;
                }
            });
    }

    // Render HTML items
    function renderNotificationList(items, canDelete = false) {
        if (!dropdownList) return;

        if (!items || items.length === 0) {
            dropdownList.innerHTML = `
                <div class="notif-empty-state">
                    <div class="notif-empty-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                    </div>
                    <p>No active announcements right now.</p>
                    <span style="font-size: 11px; color: #94A3B8; margin-top: 4px; display: block;">Announcements automatically expire after 24 hours.</span>
                </div>
            `;
            return;
        }

        let html = '';
        items.forEach(item => {
            const isUnread = !item.is_read;
            const type = item.type || 'info';
            const badgeLabel = type === 'urgent' ? 'Urgent' : (type === 'alert' ? 'Alert' : 'Notice');

            // Icon SVG based on type
            let iconSvg = '';
            if (type === 'urgent') {
                iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
            } else if (type === 'alert') {
                iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 22 20 2 20 12 2"></polygon><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
            } else {
                iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
            }

            const cleanTitle = (item.title || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const cleanMsg = (item.message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const cleanSender = (item.sender_name || 'Admin').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const showDelete = (canDelete || item.can_delete);

            html += `
                <div class="notif-item ${type} ${isUnread ? 'unread' : ''}" data-id="${item.id}" data-unread="${isUnread ? '1' : '0'}">
                    <div class="notif-type-icon ${type}">
                        ${iconSvg}
                    </div>
                    <div class="notif-content-wrap">
                        <div class="notif-item-top">
                            <span class="notif-item-title">${cleanTitle}</span>
                            <div class="notif-top-right">
                                <span class="notif-item-badge ${type}">${badgeLabel}</span>
                                ${showDelete ? `
                                    <button type="button" class="btn-delete-notif" data-id="${item.id}" title="Delete announcement (Admin)">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                        <div class="notif-item-msg">${cleanMsg}</div>
                        <div class="notif-item-meta">
                            <span class="notif-sender-pill">From: ${cleanSender}</span>
                            <span>•</span>
                            <span>${item.time_ago || 'Recent'}</span>
                            ${isUnread ? '<span style="margin-left: auto; color: #087F6C; font-weight: 700; font-size: 10.5px;">Click to read</span>' : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        dropdownList.innerHTML = html;
    }

    // Toggle Dropdown Panel
    if (notifBtn && dropdown) {
        notifBtn.addEventListener('click', e => {
            e.stopPropagation();
            const isOpen = dropdown.classList.contains('show');
            if (isOpen) {
                dropdown.classList.remove('show');
                notifBtn.setAttribute('aria-expanded', 'false');
            } else {
                dropdown.classList.add('show');
                notifBtn.setAttribute('aria-expanded', 'true');
                fetchNotifications(false);
            }
        });

        // Click outside closes dropdown
        document.addEventListener('click', e => {
            if (!dropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                dropdown.classList.remove('show');
                notifBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Dropdown items click (Mark as read OR Delete)
    if (dropdownList) {
        dropdownList.addEventListener('click', e => {
            // Check if user clicked Delete button
            const deleteBtn = e.target.closest('.btn-delete-notif');
            if (deleteBtn) {
                e.stopPropagation();
                e.preventDefault();
                const notifId = deleteBtn.dataset.id;
                if (!notifId) return;

                if (!confirm('Are you sure you want to delete this announcement?')) {
                    return;
                }

                const notifItem = deleteBtn.closest('.notif-item');
                if (notifItem) {
                    notifItem.style.opacity = '0.35';
                    notifItem.style.pointerEvents = 'none';
                }

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', notifId);
                formData.append('csrf', csrfToken);

                fetch('api/notifications.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        showNotifToast('Announcement deleted successfully', 'success');
                        fetchNotifications(false);
                    } else {
                        alert(data.error || 'Failed to delete notification.');
                        if (notifItem) {
                            notifItem.style.opacity = '1';
                            notifItem.style.pointerEvents = 'auto';
                        }
                    }
                })
                .catch(err => {
                    console.error('Delete notice error:', err);
                    if (notifItem) {
                        notifItem.style.opacity = '1';
                        notifItem.style.pointerEvents = 'auto';
                    }
                });
                return;
            }

            // Click individual unread notification to mark as read
            const item = e.target.closest('.notif-item');
            if (!item) return;

            const id = item.dataset.id;
            const isUnread = item.dataset.unread === '1';

            if (isUnread && id) {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('id', id);
                formData.append('csrf', csrfToken);

                // Optimistically mark visually read
                item.classList.remove('unread');
                item.dataset.unread = '0';
                const markText = item.querySelector('.notif-item-meta span:last-child');
                if (markText && markText.textContent.includes('Click to read')) {
                    markText.remove();
                }

                fetch('api/notifications.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(() => {
                    fetchNotifications(false);
                });
            }
        });
    }

    // Mark all as read button
    if (markAllBtn) {
        markAllBtn.addEventListener('click', () => {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('id', 'all');
            formData.append('csrf', csrfToken);

            fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    fetchNotifications(false);
                }
            })
            .catch(err => console.error(err));
        });
    }

    // Admin Clear All button
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            if (!confirm('Are you sure you want to delete ALL announcements? This cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', 'all');
            formData.append('csrf', csrfToken);

            fetch('api/notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    showNotifToast('All announcements cleared', 'success');
                    fetchNotifications(false);
                } else {
                    alert(data.error || 'Failed to clear announcements.');
                }
            })
            .catch(err => {
                console.error('Clear all error:', err);
            });
        });
    }

    // Refresh button
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            fetchNotifications(true);
        });
    }

    // Admin Compose Notice Modal logic
    if (openComposeBtn && composeModal) {
        openComposeBtn.addEventListener('click', () => {
            if (dropdown) dropdown.classList.remove('show');
            composeModal.classList.add('show');
            composeModal.setAttribute('aria-hidden', 'false');
            const titleInput = document.getElementById('noticeTitle');
            if (titleInput) {
                setTimeout(() => titleInput.focus(), 50);
            }
        });

        const closeModal = () => {
            composeModal.classList.remove('show');
            composeModal.setAttribute('aria-hidden', 'true');
        };

        if (closeComposeBtn) closeComposeBtn.addEventListener('click', closeModal);
        if (cancelComposeBtn) cancelComposeBtn.addEventListener('click', closeModal);

        composeModal.addEventListener('click', e => {
            if (e.target === composeModal) closeModal();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && composeModal.classList.contains('show')) {
                closeModal();
            }
        });

        // Textarea char count
        if (messageInput && charCounter) {
            messageInput.addEventListener('input', () => {
                charCounter.textContent = messageInput.value.length;
            });
        }

        // Form Submission
        if (composeForm) {
            composeForm.addEventListener('submit', e => {
                e.preventDefault();

                const title = document.getElementById('noticeTitle')?.value.trim();
                const msg = messageInput?.value.trim();
                if (!title || !msg) {
                    alert('Please enter both title and message.');
                    return;
                }

                if (submitBtn) submitBtn.disabled = true;
                if (submitBtnText) submitBtnText.textContent = 'Broadcasting...';

                const formData = new FormData(composeForm);

                fetch('api/notifications.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (submitBtn) submitBtn.disabled = false;
                    if (submitBtnText) submitBtnText.textContent = 'Send to Reception';

                    if (data && data.success) {
                        closeModal();
                        composeForm.reset();
                        if (charCounter) charCounter.textContent = '0';

                        // Show quick success toast or feedback
                        showNotifToast('Notice sent to Reception Desk successfully!', 'success');

                        // Immediately fetch updated list
                        fetchNotifications(false);
                    } else {
                        alert(data.error || 'Failed to send notification.');
                    }
                })
                .catch(err => {
                    if (submitBtn) submitBtn.disabled = false;
                    if (submitBtnText) submitBtnText.textContent = 'Send to Reception';
                    console.error('Broadcast failed:', err);
                    alert('Network error while sending notification.');
                });
            });
        }
    }

    // Simple toast banner helper
    function showNotifToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: ${type === 'success' ? '#087F6C' : '#EF4444'};
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: modalPop 0.2s ease;
        `;
        toast.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span>${msg}</span>
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3200);
    }

    // Initial load
    fetchNotifications(false);

    // Periodic auto-polling every 30 seconds
    setInterval(() => {
        fetchNotifications(false);
    }, 30000);
}

// =========================================================================
// ULTRA-PREMIUM OPD PRINT MODAL CONTROLLER
// =========================================================================
let currentPrintModal = {
    patientId: 0,
    patientName: '',
    uhid: '',
    templateId: 0,
    pages: 1
};

window.openPrintModal = function(patientId, patientName = '', uhid = '', templateId = 0, pages = 1) {
    const backdrop = document.getElementById('printModalBackdrop');
    const frame = document.getElementById('printModalFrame');
    const loader = document.getElementById('printModalLoader');
    const badge = document.getElementById('printModalPatientBadge');
    const subText = document.getElementById('printModalSubText');
    const btnPage1 = document.getElementById('btnPageTab1');
    const btnPage2 = document.getElementById('btnPageTab2');
    const tplSelect = document.getElementById('printModalTemplateSelect');
    const btnText = document.getElementById('btnPrintModalBtnText');

    if (!backdrop || !frame) {
        window.location.href = `print_opd.php?id=${patientId}`;
        return;
    }

    currentPrintModal.patientId = parseInt(patientId, 10) || 0;
    currentPrintModal.patientName = patientName || '';
    currentPrintModal.uhid = uhid || '';
    currentPrintModal.templateId = parseInt(templateId, 10) || 0;
    currentPrintModal.pages = parseInt(pages, 10) || 1;

    if (badge) {
        badge.textContent = patientName ? `${patientName} (${uhid || 'OPD'})` : (uhid || 'Patient Slip');
    }
    if (subText) {
        subText.textContent = patientName ? `OPD Consultation Slip · UHID: ${uhid || '—'}` : 'Motherland Hospital OPD Consultation Sheet';
    }

    if (btnPage1 && btnPage2) {
        btnPage1.classList.toggle('active', currentPrintModal.pages === 1);
        btnPage2.classList.toggle('active', currentPrintModal.pages === 2);
    }

    if (btnText) {
        btnText.textContent = currentPrintModal.pages === 2 ? 'Print Slip (2 Pages)' : 'Print Slip';
    }

    if (tplSelect && currentPrintModal.templateId > 0) {
        tplSelect.value = String(currentPrintModal.templateId);
    }

    let url = `print_opd.php?id=${currentPrintModal.patientId}&modal=1&pages=${currentPrintModal.pages}`;
    if (currentPrintModal.templateId > 0) {
        url += `&template_id=${currentPrintModal.templateId}`;
    }

    if (loader) loader.classList.add('active');
    frame.onload = function() {
        if (loader) loader.classList.remove('active');
    };
    frame.src = url;

    backdrop.style.display = 'flex';
    requestAnimationFrame(() => {
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
};

window.closePrintModal = function() {
    const backdrop = document.getElementById('printModalBackdrop');
    const frame = document.getElementById('printModalFrame');
    if (!backdrop) return;

    backdrop.classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => {
        backdrop.style.display = 'none';
        if (frame) frame.src = 'about:blank';
    }, 220);
};

document.addEventListener('DOMContentLoaded', () => {
    initPrintModal();
});

function initPrintModal() {
    const backdrop = document.getElementById('printModalBackdrop');
    const closeBtn = document.getElementById('btnClosePrintModal');
    const printTrigger = document.getElementById('btnPrintModalTrigger');
    const btnPage1 = document.getElementById('btnPageTab1');
    const btnPage2 = document.getElementById('btnPageTab2');
    const tplSelect = document.getElementById('printModalTemplateSelect');
    const frame = document.getElementById('printModalFrame');
    const loader = document.getElementById('printModalLoader');
    const btnText = document.getElementById('btnPrintModalBtnText');

    if (closeBtn) {
        closeBtn.addEventListener('click', window.closePrintModal);
    }

    if (backdrop) {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                window.closePrintModal();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const b = document.getElementById('printModalBackdrop');
            if (b && b.classList.contains('active')) {
                window.closePrintModal();
            }
        }
    });

    if (printTrigger && frame) {
        printTrigger.addEventListener('click', () => {
            try {
                if (frame.contentWindow) {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                }
            } catch (err) {
                console.error('Frame print failed:', err);
                frame.focus();
                window.print();
            }
        });
    }

    function reloadModalFrame() {
        if (!frame) return;
        if (loader) loader.classList.add('active');
        let url = `print_opd.php?id=${currentPrintModal.patientId}&modal=1&pages=${currentPrintModal.pages}`;
        if (currentPrintModal.templateId > 0) {
            url += `&template_id=${currentPrintModal.templateId}`;
        }
        frame.src = url;
    }

    if (btnPage1) {
        btnPage1.addEventListener('click', () => {
            if (currentPrintModal.pages === 1) return;
            currentPrintModal.pages = 1;
            btnPage1.classList.add('active');
            if (btnPage2) btnPage2.classList.remove('active');
            if (btnText) btnText.textContent = 'Print Slip';
            reloadModalFrame();
        });
    }

    if (btnPage2) {
        btnPage2.addEventListener('click', () => {
            if (currentPrintModal.pages === 2) return;
            currentPrintModal.pages = 2;
            btnPage2.classList.add('active');
            if (btnPage1) btnPage1.classList.remove('active');
            if (btnText) btnText.textContent = 'Print Slip (2 Pages)';
            reloadModalFrame();
        });
    }

    if (tplSelect) {
        tplSelect.addEventListener('change', () => {
            currentPrintModal.templateId = parseInt(tplSelect.value, 10) || 0;
            reloadModalFrame();
        });
    }
}