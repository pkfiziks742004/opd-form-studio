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
<div class="print-modal-backdrop" id="printModalBackdrop" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="printModalTitle">
    <div class="print-modal-card">
        <!-- Top Toolbar Header -->
        <div class="print-modal-header">
            <div class="print-modal-header-left">
                <div class="print-modal-icon-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
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
