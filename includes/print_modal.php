<?php
declare(strict_types=1);

if (!function_exists('db')) {
    require_once __DIR__ . '/config.php';
}

// Fetch active templates for the modal dropdown
$modalTemplates = [];
try {
    $modalTemplates = db()->query("SELECT id, name, template_type, file_path FROM templates WHERE active = 1 ORDER BY id ASC")->fetchAll();
} catch (Throwable $e) {
    $modalTemplates = [];
}
?>
<!-- ========================================================================= -->
<!-- PREMIUM OPD PRINT PREVIEW POPUP MODAL                                     -->
<!-- ========================================================================= -->
<style>
.print-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(10, 25, 23, 0.72);
    backdrop-filter: blur(10px) saturate(160%);
    -webkit-backdrop-filter: blur(10px) saturate(160%);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    transition: opacity 0.24s cubic-bezier(0.16, 1, 0.3, 1);
}
.print-modal-backdrop.active {
    display: flex !important;
    opacity: 1;
}
.print-modal-card {
    background: #ffffff;
    width: 100%;
    max-width: 1040px;
    height: 92vh;
    border-radius: 4px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 30px 70px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.2);
    transform: scale(0.96) translateY(12px);
    transition: transform 0.26s cubic-bezier(0.16, 1, 0.3, 1);
}
.print-modal-backdrop.active .print-modal-card {
    transform: scale(1) translateY(0);
}
.print-modal-header {
    background: linear-gradient(135deg, #073F38 0%, #0a4f46 50%, #07352f 100%);
    color: #ffffff;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
    z-index: 10;
}
.print-modal-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.print-modal-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 4px;
    background: rgba(16, 185, 129, 0.18);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.print-modal-icon-badge svg {
    width: 20px;
    height: 20px;
    max-width: 20px;
    max-height: 20px;
    display: block;
}
.print-modal-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.print-modal-heading {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.2px;
}
.print-modal-badge-pill {
    background: rgba(255, 255, 255, 0.15);
    color: #a7f3d0;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 3px;
    border: 1px solid rgba(167, 243, 208, 0.25);
    white-space: nowrap;
    max-width: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.print-modal-sub {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 2px;
}
.print-modal-header-center {
    display: flex;
    align-items: center;
    gap: 12px;
}
.print-modal-page-toggle {
    display: inline-flex;
    background: rgba(0, 0, 0, 0.25);
    padding: 2px;
    border-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.btn-print-page-tab {
    background: transparent;
    color: #cbd5e1;
    border: none;
    outline: none;
    font-size: 12px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.16s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}
.btn-print-page-tab:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
}
.btn-print-page-tab.active {
    background: #087F6C;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
}
.print-modal-tpl-select-wrap {
    position: relative;
}
.print-modal-tpl-select {
    background: rgba(0, 0, 0, 0.28);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #f1f5f9;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 4px;
    outline: none;
    cursor: pointer;
    transition: all 0.18s ease;
}
.print-modal-tpl-select:focus {
    border-color: #34d399;
    background: rgba(0, 0, 0, 0.4);
}
.print-modal-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.btn-print-modal-action {
    background: linear-gradient(135deg, #087F6C 0%, #066757 100%);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.22);
    font-size: 13px;
    font-weight: 700;
    padding: 7px 16px;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
    transition: all 0.16s ease;
}
.btn-print-modal-action svg {
    width: 16px;
    height: 16px;
    max-width: 16px;
    max-height: 16px;
}
.btn-print-modal-action:hover {
    background: linear-gradient(135deg, #0aa38b 0%, #087F6C 100%);
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(0, 0, 0, 0.3);
}
.btn-print-modal-action:active {
    transform: translateY(0);
}
.btn-print-modal-close {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #ffffff;
    font-size: 22px;
    line-height: 1;
    width: 34px;
    height: 34px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.16s ease;
    padding: 0;
}
.btn-print-modal-close:hover {
    background: rgba(239, 68, 68, 0.85);
    border-color: rgba(239, 68, 68, 0.9);
    transform: scale(1.05);
}
.print-modal-viewport {
    flex: 1;
    position: relative;
    background: #e9eef2;
    overflow: hidden;
}
.print-modal-loader {
    position: absolute;
    inset: 0;
    background: rgba(241, 245, 249, 0.85);
    backdrop-filter: blur(4px);
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    z-index: 5;
    color: #0f172a;
    font-size: 13px;
    font-weight: 600;
}
.print-modal-loader.active {
    display: flex;
}
.print-modal-spinner {
    width: 38px;
    height: 38px;
    border: 3.5px solid #cbd5e1;
    border-top-color: #087F6C;
    border-radius: 50%;
    animation: modalSpin 0.7s linear infinite;
}
@keyframes modalSpin {
    to { transform: rotate(360deg); }
}
.print-modal-frame {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
    background: transparent;
}
@media print {
    body.print-modal-open .app-sidebar,
    body.print-modal-open .app-topbar,
    body.print-modal-open .page-content,
    body.print-modal-open .print-modal-header,
    body.print-modal-open .print-modal-loader,
    body.print-modal-open .flash-messages {
        display: none !important;
    }
    body.print-modal-open .print-modal-backdrop {
        position: static !important;
        background: transparent !important;
        padding: 0 !important;
        display: block !important;
        width: 100% !important;
        height: 100% !important;
    }
    body.print-modal-open .print-modal-card {
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        height: 100% !important;
        max-width: none !important;
        border-radius: 0 !important;
    }
    body.print-modal-open .print-modal-viewport {
        background: transparent !important;
        padding: 0 !important;
        overflow: visible !important;
    }
}
</style>

<div class="print-modal-backdrop" id="printModalBackdrop" style="display: none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="printModalTitle">
    <div class="print-modal-card">
        <!-- Top Toolbar Header -->
        <div class="print-modal-header">
            <div class="print-modal-header-left">
                <div class="print-modal-icon-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;max-width:20px;max-height:20px;display:block;">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                </div>
                <div>
                    <div class="print-modal-title-row">
                        <h2 class="print-modal-heading" id="printModalTitle">OPD Print Preview</h2>
                        <span class="print-modal-badge-pill" id="printModalPatientBadge">Patient Slip</span>
                    </div>
                    <div class="print-modal-sub" id="printModalSubText">Motherland Hospital OPD Consultation Sheet</div>
                </div>
            </div>

            <!-- Middle Controls: 1 Page / 2 Pages & Template Dropdown -->
            <div class="print-modal-header-center">
                <div class="print-modal-page-toggle" id="printModalPageToggle">
                    <button type="button" class="btn-print-page-tab active" data-pages="1" id="btnPageTab1" title="Print single A4 prescription slip">
                        1 Page
                    </button>
                    <button type="button" class="btn-print-page-tab" data-pages="2" id="btnPageTab2" title="Print 2 pages with blank consultation sheet">
                        2 Pages
                    </button>
                </div>

                <?php if (count($modalTemplates) > 1): ?>
                    <div class="print-modal-tpl-select-wrap">
                        <select id="printModalTemplateSelect" class="print-modal-tpl-select" title="Change print layout template">
                            <?php foreach ($modalTemplates as $mt): ?>
                                <option value="<?= (int)$mt['id'] ?>">
                                    <?= htmlspecialchars($mt['name'], ENT_QUOTES, 'UTF-8') ?><?= ($mt['template_type'] === 'code') ? ' (Code)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Actions: Print & Close -->
            <div class="print-modal-header-right">
                <button type="button" class="btn-print-modal-action" id="btnPrintModalTrigger" title="Print this slip directly (Ctrl+P)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;max-width:16px;max-height:16px;display:inline-block;">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span id="btnPrintModalBtnText">Print Slip</span>
                </button>
                <button type="button" class="btn-print-modal-close" id="btnClosePrintModal" aria-label="Close Preview">&times;</button>
            </div>
        </div>

        <!-- Modal Body (Embeds Preview) -->
        <div class="print-modal-viewport">
            <div class="print-modal-loader" id="printModalLoader">
                <div class="print-modal-spinner"></div>
                <span>Rendering high-resolution A4 preview...</span>
            </div>
            <iframe id="printModalFrame" class="print-modal-frame" src="about:blank" title="OPD Consultation Paper Print View"></iframe>
        </div>
    </div>
</div>
