<?php
if (!isset($user) || $user['role'] !== 'admin') {
    return;
}
?>
<!-- Admin Broadcast Notice Modal -->
<div class="admin-notice-modal-backdrop" id="adminNoticeModal" style="display: none;" aria-hidden="true" role="dialog" aria-labelledby="noticeModalTitle">
    <div class="admin-notice-modal-card">
        <div class="admin-notice-modal-header">
            <div class="modal-title-with-icon">
                <div class="modal-icon-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;max-width:20px;max-height:20px;display:block;">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </div>
                <div>
                    <h2 id="noticeModalTitle">Broadcast Notice to Reception</h2>
                    <p>Send typed announcement to reception desk • Auto-expires in 24 hours</p>
                </div>
            </div>
            <button type="button" class="btn-modal-close" id="btnCloseComposeNotice" aria-label="Close dialog">&times;</button>
        </div>

        <form id="composeNoticeForm" class="admin-notice-form" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="send">

            <div class="notice-form-group">
                <label for="noticeTitle">Notice Title / Subject <span class="required-star">*</span></label>
                <input type="text" id="noticeTitle" name="title" class="notice-input" placeholder="e.g. Doctor Timing Change, OPD Token Update, Urgent Patient Alert..." required maxlength="190">
            </div>

            <div class="notice-form-row">
                <div class="notice-form-group">
                    <label for="noticeType">Priority / Urgency</label>
                    <select id="noticeType" name="type" class="notice-select">
                        <option value="info">🔵 General Notice</option>
                        <option value="alert" selected>🟠 Important Alert</option>
                        <option value="urgent">🔴 Urgent / Immediate Action</option>
                    </select>
                </div>
                <div class="notice-form-group">
                    <label for="noticeTarget">Target Role</label>
                    <select id="noticeTarget" name="target_role" class="notice-select">
                        <option value="reception" selected>Reception Desk</option>
                        <option value="all">All Hospital Users</option>
                    </select>
                </div>
            </div>

            <div class="notice-form-group">
                <label for="noticeMessage">Notice Message <span class="required-star">*</span></label>
                <textarea id="noticeMessage" name="message" class="notice-textarea" rows="4" placeholder="Type instructions or notice details to show receptionists in real-time..." required maxlength="3000"></textarea>
                <div class="notice-char-counter"><span id="noticeCharCount">0</span> / 3000 characters</div>
            </div>

            <div class="admin-notice-modal-actions">
                <button type="button" class="btn-notice-cancel" id="btnCancelComposeNotice">Cancel</button>
                <button type="submit" class="btn-notice-submit" id="btnSubmitNotice">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    <span id="btnSubmitNoticeText">Send to Reception</span>
                </button>
            </div>
        </form>
    </div>
</div>
