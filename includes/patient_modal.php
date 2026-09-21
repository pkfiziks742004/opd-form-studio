<?php
// Reusable Patient Profile & Clinical Timeline Modal Component
?>
<!-- Patient Profile & Clinical Timeline Modal -->
<div id="patientDetailsModal" class="opd-modal-backdrop" style="display: none;" aria-hidden="true" role="dialog">
    <div class="opd-modal-dialog">
        <div class="opd-modal-content">
            <!-- Modal Header: Patient Identity Bar -->
            <div class="opd-modal-header">
                <div class="opd-modal-patient-info">
                    <div class="opd-modal-avatar" id="modalAvatar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <h3 id="modalPatientName" class="opd-modal-title">Patient Profile</h3>
                            <span id="modalGenderAgePill" class="profile-pill-badge gender">—</span>
                            <span id="modalUhidBadge" class="profile-pill-badge uhid">—</span>
                            <span id="modalVisitsCountBadge" class="profile-pill-badge visits">1 Visit</span>
                            <span id="modalPanelBadge" class="profile-pill-badge panel">CASH</span>
                        </div>
                        <div class="opd-modal-sub-bar">
                            <span class="sub-bar-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>Phone: <strong id="modalContactVal" style="color: #0f172a;">—</strong></span>
                            </span>
                            <span style="color: #cbd5e1;">•</span>
                            <span class="sub-bar-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                <span>Guardian: <strong id="modalGuardianQuickVal" style="color: #0f172a;">—</strong></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="opd-modal-header-actions">
                    <a href="#" id="modalRevisitBtn" class="btn-profile-head revisit" title="Book a follow-up or new OPD visit for this patient">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                        </svg>
                        <span>Revisit Patient</span>
                    </a>
                    <a href="#" id="modalFullPageBtn" class="btn-profile-head page" title="Open full dedicated profile page">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span>Full Profile</span>
                    </a>
                    <button type="button" id="modalPrintBtn" class="btn-profile-head print" title="Print latest OPD visit slip" style="cursor: pointer; border: none; font-family: inherit;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Print Slip</span>
                    </button>
                    <button type="button" class="btn-modal-close" onclick="closePatientModal()" title="Close (Esc)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body: 2-Column Patient Profile & Clinical Timeline -->
            <div class="opd-modal-body">
                <!-- Loading State -->
                <div id="modalLoadingSpinner" class="modal-loading-state">
                    <div class="modal-spinner"></div>
                    <span style="font-weight: 600; color: #475569;">Loading patient medical profile & visit timeline...</span>
                </div>

                <!-- Content State -->
                <div id="modalDataContent" style="display: none;">
                    <div class="patient-profile-grid">
                        <!-- Left Column: Patient Demographic Card -->
                        <div class="profile-info-card">
                            <div class="profile-card-title">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span>Patient Details</span>
                            </div>

                            <div class="profile-data-list">
                                <div class="profile-data-row">
                                    <div class="profile-row-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </div>
                                    <div class="profile-row-content">
                                        <span class="profile-row-lbl">Full Name</span>
                                        <span class="profile-row-val" id="modalDetailName">—</span>
                                    </div>
                                </div>

                                <div class="profile-data-row">
                                    <div class="profile-row-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    </div>
                                    <div class="profile-row-content">
                                        <span class="profile-row-lbl">Guardian / Relation</span>
                                        <span class="profile-row-val" id="modalDetailGuardian">—</span>
                                    </div>
                                </div>

                                <div class="profile-data-row">
                                    <div class="profile-row-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    </div>
                                    <div class="profile-row-content">
                                        <span class="profile-row-lbl">Age & Gender</span>
                                        <span class="profile-row-val" id="modalDetailAgeSex">—</span>
                                    </div>
                                </div>

                                <div class="profile-data-row">
                                    <div class="profile-row-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    </div>
                                    <div class="profile-row-content">
                                        <span class="profile-row-lbl">Phone Number</span>
                                        <span class="profile-row-val" id="modalDetailPhone">—</span>
                                    </div>
                                </div>

                                <div class="profile-data-row">
                                    <div class="profile-row-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    </div>
                                    <div class="profile-row-content">
                                        <span class="profile-row-lbl">Residential Address</span>
                                        <span class="profile-row-val" id="modalDetailAddress">—</span>
                                    </div>
                                </div>

                                <div class="profile-data-row">
                                    <div class="profile-row-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 22 7 12 2"/><line x1="2" y1="20" x2="22" y2="20"/><line x1="6" y1="7" x2="6" y2="20"/><line x1="10" y1="7" x2="10" y2="20"/><line x1="14" y1="7" x2="14" y2="20"/><line x1="18" y1="7" x2="18" y2="20"/></svg>
                                    </div>
                                    <div class="profile-row-content">
                                        <span class="profile-row-lbl">Panel / Billing Type</span>
                                        <span class="profile-row-val" id="modalDetailPanel">—</span>
                                    </div>
                                </div>

                                <div class="profile-data-row">
                                    <div class="profile-row-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </div>
                                    <div class="profile-row-content">
                                        <span class="profile-row-lbl">First Registered On</span>
                                        <span class="profile-row-val" id="modalDetailFirstVisit">—</span>
                                    </div>
                                </div>
                            </div>

                            <!-- UHID Quick Copy Box -->
                            <div class="uhid-quick-box">
                                <div class="uhid-quick-box-left">
                                    <span class="uhid-quick-box-lbl">LIFETIME MEDICAL UHID</span>
                                    <span class="uhid-quick-box-val" id="modalDetailUhidBox">—</span>
                                </div>
                                <button type="button" class="btn-copy-uhid" id="modalCopyUhidBtn" onclick="copyModalUhid()" title="Copy UHID to clipboard">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    <span>Copy</span>
                                </button>
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
                                <span class="timeline-head-badge" id="modalTimelineBadge">1 Total Visit</span>
                            </div>

                            <!-- Visual Timeline Tree -->
                            <div class="timeline-tree" id="modalTimelineList">
                                <!-- Dynamic Visit Items Rendered via JavaScript -->
                            </div>

                            <!-- Revisit Callout Prompt -->
                            <div class="timeline-revisit-banner">
                                <div class="timeline-revisit-text">
                                    <div class="timeline-revisit-title">Need to register a follow-up visit for this patient?</div>
                                    <div class="timeline-revisit-sub">Pre-fills existing demographic records and generates today's new Bill No. & Token automatically.</div>
                                </div>
                                <a href="#" id="modalTimelineRevisitBtn" class="btn-timeline-revisit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                                    </svg>
                                    <span>Revisit Patient</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="opd-modal-footer">
                <div class="modal-footer-left">
                    <span>Lifetime UHID: <strong id="modalFooterUhid" style="font-family: monospace; color: #064e3b;">—</strong> &bull; Total Consultations: <strong id="modalFooterVisits">1 Visit</strong></span>
                </div>
                <div class="modal-footer-right">
                    <button type="button" class="btn btn-soft" onclick="closePatientModal()">Close</button>
                    <a href="#" id="modalBottomRevisitBtn" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                        </svg>
                        <span>Revisit (New OPD Slip)</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Patient Profile & Timeline Modal Controller
window.openPatientModal = function(patientId, uhid) {
    const modal = document.getElementById('patientDetailsModal');
    if (!modal) return;

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    const spinner = document.getElementById('modalLoadingSpinner');
    const content = document.getElementById('modalDataContent');
    if (spinner) spinner.style.display = 'flex';
    if (content) content.style.display = 'none';

    let url = 'patient_ajax.php?action=get_detail&';
    if (patientId) url += 'id=' + encodeURIComponent(patientId);
    else if (uhid) url += 'uhid=' + encodeURIComponent(uhid);

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Failed to load patient details.');
                closePatientModal();
                return;
            }

            const p = data.patient;
            const visits = data.visits || [];

            // Clean sanitized name
            const cleanName = (p.name || 'Patient').replace(/\\+/g, '').trim();
            const cleanGuardian = (p.guardian || '').replace(/\\+/g, '').trim();

            document.getElementById('modalPatientName').textContent = cleanName;
            
            // Age & Sex Pill
            let ageSexParts = [];
            if (p.age) ageSexParts.push(p.age + ' Yrs');
            if (p.sex) ageSexParts.push(p.sex);
            const ageSexStr = ageSexParts.join(' · ') || 'Demographics';
            document.getElementById('modalGenderAgePill').textContent = ageSexStr;
            
            // UHID & Badges
            document.getElementById('modalUhidBadge').textContent = 'UHID: ' + (p.uhid || '—');
            document.getElementById('modalVisitsCountBadge').textContent = data.total_visits + (data.total_visits === 1 ? ' Visit Recorded' : ' Visits Recorded');
            document.getElementById('modalPanelBadge').textContent = p.panel || 'CASH';

            // Sub bar
            document.getElementById('modalContactVal').textContent = p.contact_number || 'Not Provided';
            document.getElementById('modalGuardianQuickVal').textContent = cleanGuardian || 'Not Provided';

            // Avatar Gender styling
            const avatar = document.getElementById('modalAvatar');
            if (avatar) {
                const sLower = (p.sex || '').toLowerCase();
                avatar.className = 'opd-modal-avatar ' + (sLower === 'female' ? 'female' : (sLower === 'male' ? 'male' : ''));
            }

            // Left Column Details
            document.getElementById('modalDetailName').textContent = cleanName;
            document.getElementById('modalDetailGuardian').textContent = cleanGuardian ? ('S/O / W/O ' + cleanGuardian) : 'Not Specified';
            document.getElementById('modalDetailAgeSex').textContent = ageSexStr;
            
            // Phone with clickable call link
            const phoneEl = document.getElementById('modalDetailPhone');
            if (p.contact_number && p.contact_number !== '—') {
                phoneEl.innerHTML = `<a href="tel:${encodeURIComponent(p.contact_number)}" style="color:var(--primary-dark); font-weight:800;">${p.contact_number}</a>`;
            } else {
                phoneEl.textContent = 'Not Provided';
            }

            document.getElementById('modalDetailAddress').textContent = (p.address || '').replace(/\\+/g, '').trim() || 'Not Specified';
            document.getElementById('modalDetailPanel').textContent = p.panel || 'CASH';
            document.getElementById('modalDetailFirstVisit').textContent = p.initial_visit_date || p.visit_date || '—';

            // UHID quick box
            document.getElementById('modalDetailUhidBox').textContent = p.uhid || '—';
            document.getElementById('modalFooterUhid').textContent = p.uhid || '—';
            document.getElementById('modalFooterVisits').textContent = data.total_visits + (data.total_visits === 1 ? ' Visit' : ' Visits');

            // Action Links
            const revisitUrl = 'patient_form.php?revisit_id=' + p.id;
            const fullProfileUrl = 'patient_profile.php?id=' + p.id;
            const printLatestUrl = 'print_opd.php?id=' + p.id;

            const revBtn = document.getElementById('modalRevisitBtn');
            const bottomRevBtn = document.getElementById('modalBottomRevisitBtn');
            const timelineRevBtn = document.getElementById('modalTimelineRevisitBtn');
            const fullPageBtn = document.getElementById('modalFullPageBtn');
            const printBtn = document.getElementById('modalPrintBtn');

            if (revBtn) revBtn.href = revisitUrl;
            if (bottomRevBtn) bottomRevBtn.href = revisitUrl;
            if (timelineRevBtn) timelineRevBtn.href = revisitUrl;
            if (fullPageBtn) fullPageBtn.href = fullProfileUrl;
            if (printBtn) {
                printBtn.onclick = (e) => {
                    e.preventDefault();
                    openPrintModal(p.id, p.name, p.uhid);
                };
            }

            // Render Timeline Items
            const timelineList = document.getElementById('modalTimelineList');
            if (timelineList) {
                timelineList.innerHTML = '';
                document.getElementById('modalTimelineBadge').textContent = data.total_visits + (data.total_visits === 1 ? ' Consultation' : ' Consultations');

                if (visits.length === 0) {
                    timelineList.innerHTML = '<div style="padding: 24px; text-align: center; color: #64748b;">No visit records found.</div>';
                } else {
                    visits.forEach((v, index) => {
                        const item = document.createElement('div');
                        item.className = 'timeline-item';

                        const isLatest = (index === 0);
                        const visitBadgeText = isLatest ? `Latest Visit · Visit #${v.visit_num}` : `Visit #${v.visit_num}`;
                        const visitBadgeClass = isLatest ? 'latest' : 'past';
                        const dotClass = isLatest ? 'timeline-dot latest' : 'timeline-dot';
                        const cardClass = isLatest ? 'visit-timeline-card latest-card' : 'visit-timeline-card';

                        item.innerHTML = `
                            <div class="${dotClass}" title="Visit #${v.visit_num}">
                                ${isLatest ? `
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                ` : v.visit_num}
                            </div>
                            <div class="${cardClass}">
                                <div class="visit-card-top">
                                    <div class="visit-card-top-left">
                                        <span class="visit-tag-badge ${visitBadgeClass}">${visitBadgeText}</span>
                                        <span class="visit-date-main">${v.visit_date}</span>
                                        <span class="visit-time-main">${v.visit_time}</span>
                                        ${v.relative_date ? `<span class="visit-relative-badge">${v.relative_date}</span>` : ''}
                                    </div>
                                    <button type="button" class="btn-visit-print" onclick="openPrintModal(${v.id}, '${(p.name || '').replace(/'/g, "\\'")}', '${(p.uhid || '').replace(/'/g, "\\'")}')" title="Print this specific OPD slip" style="cursor: pointer; border: none; font-family: inherit;">
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
                                        <span class="visit-chip-val" style="color: #047857;">${v.doctor_dept}</span>
                                    </div>
                                    <div class="visit-chip">
                                        <span class="visit-chip-lbl">ROOM / CABIN</span>
                                        <span class="visit-chip-val">${v.room_no || '—'}</span>
                                    </div>
                                    <div class="visit-chip">
                                        <span class="visit-chip-lbl">DAILY TOKEN (APP NO.)</span>
                                        <span class="visit-chip-val" style="color: #1e40af;">#${v.app_no || '—'}</span>
                                    </div>
                                    <div class="visit-chip">
                                        <span class="visit-chip-lbl">BILL NO. / INVOICE</span>
                                        <span class="visit-chip-val" style="font-family: monospace;">${v.bill_no || '—'}</span>
                                    </div>
                                </div>

                                <div class="visit-card-foot">
                                    <span>Panel / Payment: <strong>${v.panel}</strong></span>
                                    <span>Registered by <strong>${v.created_by_name}</strong></span>
                                </div>
                            </div>
                        `;
                        timelineList.appendChild(item);
                    });
                }
            }

            if (spinner) spinner.style.display = 'none';
            if (content) content.style.display = 'block';
        })
        .catch(err => {
            console.error(err);
            alert('Failed to connect to server.');
            closePatientModal();
        });
};

window.closePatientModal = function() {
    const modal = document.getElementById('patientDetailsModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
};

window.copyModalUhid = function() {
    const uhidBox = document.getElementById('modalDetailUhidBox');
    if (!uhidBox) return;
    const uhid = uhidBox.textContent.trim();
    if (uhid && uhid !== '—') {
        navigator.clipboard.writeText(uhid).then(() => {
            const btn = document.getElementById('modalCopyUhidBtn');
            if (btn) {
                const orig = btn.innerHTML;
                btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> <span>Copied!</span>`;
                btn.style.background = '#047857';
                btn.style.color = '#ffffff';
                setTimeout(() => {
                    btn.innerHTML = orig;
                    btn.style.background = '';
                    btn.style.color = '';
                }, 2000);
            }
        });
    }
};

// Global click on backdrop to close
document.addEventListener('click', function(e) {
    const modal = document.getElementById('patientDetailsModal');
    if (modal && e.target === modal) {
        closePatientModal();
    }
});

// ESC key listener
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePatientModal();
    }
});
</script>
