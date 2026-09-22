<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login();
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/code_template.php';
require_once __DIR__ . '/includes/page_engine.php';
// Handle Replace Pad Scan POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'replace_pad_scan') {
    verify_csrf();
    $targetTplId = (int)($_POST['template_id'] ?? 0);
    $f = $_FILES['pad_scan_file'] ?? null;
    if ($targetTplId > 0 && $f && $f['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $info = @getimagesize($f['tmp_name']);
        if ($info && isset($allowed[$info['mime']])) {
            $uploadDir = __DIR__ . '/uploads/templates';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
            $fileName = 'tpl_' . bin2hex(random_bytes(10)) . '.' . $allowed[$info['mime']];
            $targetPath = $uploadDir . '/' . $fileName;
            if (move_uploaded_file($f['tmp_name'], $targetPath)) {
                $st = db()->prepare('UPDATE templates SET file_path = ?, original_name = ?, mime_type = ?, width = ?, height = ? WHERE id = ?');
                $st->execute(['uploads/templates/' . $fileName, $f['name'], $info['mime'], $info[0], $info[1], $targetTplId]);
                flash('success', 'Pad scan image replaced successfully. All field coordinates are preserved.');
                header('Location: template_editor.php?template_id=' . $targetTplId);
                exit;
            }
        }
    }
    flash('error', 'Failed to upload replacement scan image.');
    header('Location: template_editor.php?template_id=' . $targetTplId);
    exit;
}

$templates = db()->query('SELECT * FROM templates WHERE active=1 ORDER BY id ASC')->fetchAll();
$tid = (int)($_GET['template_id'] ?? 0);
$tpl = null;
if ($tid > 0) {
    foreach ($templates as $t) {
        if ((int)$t['id'] === $tid) {
            $tpl = $t;
            break;
        }
    }
}
if (!$tpl && $templates) {
    $defId = (int)setting('default_template_id', '0');
    if ($defId > 0) {
        foreach ($templates as $t) {
            if ((int)$t['id'] === $defId) { $tpl = $t; break; }
        }
    }
    if (!$tpl) $tpl = $templates[0];
}

$isCode = is_code_template($tpl);
$layout = $tpl ? get_layout($tpl, (int)$user['id']) : [];
$sections = get_template_sections($tpl, $layout);
$perm = field_permissions((int)$user['id']);

$cfg = get_motherland_config($tpl);
$pageConfig = $tpl ? get_template_page_config($tpl) : null;
$paperPresets = get_paper_presets();
$themePresets = get_theme_presets();
$templateTheme = $tpl ? get_template_theme($tpl) : $themePresets['green'];
$activeThemeKey = strtolower(trim((string)($tpl['theme_preset'] ?? 'green')));

$iconFile = __DIR__ . '/' . ltrim((string)($cfg['icon_path'] ?? ''), '/');
if (!file_exists($iconFile)) $iconFile = __DIR__ . '/assets/motherland-icon.png';
$iconSrc = file_exists($iconFile) ? ('data:' . (@mime_content_type($iconFile) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($iconFile))) : e($cfg['icon_path'] ?? '');

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
    <div>
        <h2>Drag & Drop Layout Editor</h2>
        <p><?= $isCode ? 'Drag any section anywhere in real-time to adjust spacing, and click "Save my layout".' : 'Move patient information exactly onto the blank spaces in the uploaded OPD template.' ?></p>
    </div>
</div>

<?php if ($tpl): ?>
    <div class="editor-toolbar">
        <label>
            <span>Active Template:</span>
            <select onchange="location='template_editor.php?template_id='+this.value" class="studio-select" style="min-width:300px;font-weight:600;">
                <?php foreach ($templates as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (int)$t['id'] === (int)$tpl['id'] ? 'selected' : '' ?>>
                        <?= e($t['name']) ?><?= is_code_template($t) ? ' (Digital Code Template)' : ' (Image Scan)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="editor-actions">
            <?php if ($isCode): ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="templates.php" class="btn btn-soft">Edit Text & Logo</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="print_opd.php?template_id=<?= $tpl['id'] ?>" class="btn btn-soft" id="previewBtn">Preview (<?= e($pageConfig['pageSize'] ?? 'A4') ?>)</a>
            <button class="btn btn-soft" id="resetLayout">Reset</button>
            <button class="btn btn-primary" id="saveLayout">Save my layout</button>
        </div>
    </div>

    <div class="editor-grid">
        <!-- Studio Sidebar with Segmented Tabs -->
        <aside class="studio-sidebar-card card">
            <?php if ($isCode): ?>
                <div class="studio-tabs-nav" role="tablist">
                    <button type="button" class="studio-tab-btn active" data-tab="tab-brand" role="tab" aria-selected="true">
                        <span>🏥</span>
                        <span>Branding</span>
                    </button>
                    <button type="button" class="studio-tab-btn" data-tab="tab-page" role="tab" aria-selected="false">
                        <span>📄</span>
                        <span>Page & Font</span>
                        <span id="badgePagePreset" class="studio-tab-chip"><?= e($pageConfig['pageSize']) ?></span>
                    </button>
                    <button type="button" class="studio-tab-btn" data-tab="tab-doctor" role="tab" aria-selected="false">
                        <span>🩺</span>
                        <span>Doctor & Vitals</span>
                    </button>
                    <button type="button" class="studio-tab-btn" data-tab="tab-content" role="tab" aria-selected="false">
                        <span>✍️</span>
                        <span>Signing</span>
                    </button>
                    <button type="button" class="studio-tab-btn" data-tab="tab-footer" role="tab" aria-selected="false">
                        <span>🏢</span>
                        <span>Footer</span>
                    </button>
                </div>
            <?php else: ?>
                <div class="studio-tabs-nav" role="tablist">
                    <button type="button" class="studio-tab-btn active" data-tab="tab-fields" role="tab" aria-selected="true">
                        <span>📐</span>
                        <span>Fields & Nudge</span>
                    </button>
                    <button type="button" class="studio-tab-btn" data-tab="tab-page" role="tab" aria-selected="false">
                        <span>📄</span>
                        <span>Paper Size</span>
                        <span id="badgePagePreset" class="studio-tab-chip"><?= e($pageConfig['pageSize']) ?></span>
                    </button>
                    <button type="button" class="studio-tab-btn" data-tab="tab-pad" role="tab" aria-selected="false">
                        <span>🖼️</span>
                        <span>Pad Scan & Tools</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="studio-tabs-body">
                <?php if ($isCode): ?>
                    <!-- TAB 1: Hospital Branding & Header -->
                    <div class="studio-tab-pane active" id="tab-brand" role="tabpanel">
                        <div class="pane-header">
                            <h4 class="pane-title">Hospital Branding & Header</h4>
                            <p class="pane-desc">Hospital name, tagline, consultation title, and letterhead mode.</p>
                        </div>

                        <!-- Hospital Logo Upload & Preview -->
                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Hospital Logo</span>
                                <span id="logoUploadStatus" class="badge-tag-mini" style="background:#e0f2fe;color:#0284c7;">Current</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                                <div style="width:60px;height:48px;border:1px solid #cbd5e1;border-radius:6px;background:#ffffff;display:flex;align-items:center;justify-content:center;padding:4px;overflow:hidden;">
                                    <img id="brandLogoPreview" src="<?= $iconSrc ?>" alt="Logo Preview" style="max-width:100%;max-height:100%;object-fit:contain;">
                                </div>
                                <div style="flex:1;">
                                    <input type="file" id="logoUploadInput" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="studio-input" style="font-size:11px;padding:4px;">
                                    <small style="color:#64748b;font-size:10px;display:block;margin-top:2px;">Upload PNG, JPG, or SVG</small>
                                </div>
                            </div>
                            <div style="display:flex;gap:6px;">
                                <button type="button" class="btn btn-sm btn-soft" id="btnResetLogo" style="font-size:11px;padding:3px 8px;">Reset Default Icon</button>
                            </div>
                        </div>

                        <!-- Header Block Visibility -->
                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Header Block Visibility</span>
                            </div>
                            <label class="eka-switch-label">
                                <input type="checkbox" id="chkShowHeader" <?= ($cfg['show_header'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Print Header Block (Logo & Name)</span>
                            </label>
                        </div>

                        <!-- Document Title Visibility -->
                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Document Title Visibility</span>
                            </div>
                            <label class="eka-switch-label">
                                <input type="checkbox" id="chkShowTitle" <?= ($cfg['show_title'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Print Document Title (e.g. Consultation Paper(OPD))</span>
                            </label>
                        </div>

                        <!-- Hospital Name & Tagline & Doc Title -->
                        <div class="eka-section-card">
                            <div class="form-group-studio" style="margin-bottom:10px;">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Hospital Name</label>
                                <input type="text" id="hospitalNameInput" class="studio-input" value="<?= htmlspecialchars($cfg['hospital_name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Motherland">
                            </div>
                            <div class="form-group-studio" style="margin-bottom:10px;">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Tagline / Subtitle</label>
                                <input type="text" id="hospitalTaglineInput" class="studio-input" value="<?= htmlspecialchars($cfg['hospital_tagline'], ENT_QUOTES, 'UTF-8') ?>" placeholder="HOSPITAL">
                            </div>
                            <div class="form-group-studio">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Document Title</label>
                                <input type="text" id="docTitleInput" class="studio-input" value="<?= htmlspecialchars($cfg['doc_title'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Consultation Paper(OPD)">
                            </div>
                        </div>

                        <!-- Header Mode & Height -->
                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Header Mode & Height</span>
                                <span id="badgeHeaderMode" class="badge-eka-mode <?= ($sections['header_mode'] === 'digital') ? 'badge-mode-digital' : 'badge-mode-blank' ?>">
                                    <?= ($sections['header_mode'] === 'digital') ? 'Digital Print' : 'Pre-printed Pad' ?>
                                </span>
                            </div>
                            <div class="eka-btn-segmented">
                                <button type="button" class="btn-seg <?= ($sections['header_mode'] === 'digital') ? 'active' : '' ?>" id="btnHeaderDigital" data-header-mode="digital">
                                    🩺 Digital Header
                                </button>
                                <button type="button" class="btn-seg <?= ($sections['header_mode'] === 'blank') ? 'active' : '' ?>" id="btnHeaderBlank" data-header-mode="blank">
                                    📄 Pre-printed Pad Blank
                                </button>
                            </div>
                            <div class="eka-control-row">
                                <label class="studio-slider-label">
                                    <span>Header Height / Margin</span>
                                    <span id="headerHeightVal" class="slider-badge"><?= (int)$sections['header_height'] ?> mm</span>
                                </label>
                                <input type="range" id="headerHeightSlider" min="15" max="100" step="1" value="<?= (int)$sections['header_height'] ?>" class="studio-range">
                            </div>
                            <div class="eka-presets-row">
                                <button type="button" class="btn-chip" data-set-hheight="30">Compact 30mm</button>
                                <button type="button" class="btn-chip" data-set-hheight="38">Standard 38mm</button>
                                <button type="button" class="btn-chip" data-set-hheight="50">Large 50mm</button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Page Dimensions, Font & Margins -->
                    <div class="studio-tab-pane" id="tab-page" role="tabpanel">
                        <div class="pane-header">
                            <h4 class="pane-title">Paper Size & Clinical Typography</h4>
                            <p class="pane-desc">A4 Portrait standard dimensions, print margins, and font stack.</p>
                        </div>

                        <!-- Font Family Selection -->
                        <div class="eka-section-card">
                            <div class="form-group-studio">
                                <label for="fontFamilySelect" style="font-size:11.5px;font-weight:700;color:#0f172a;margin-bottom:4px;display:block;">Primary Document Font</label>
                                <select id="fontFamilySelect" class="studio-select" style="font-weight:600;">
                                    <?php 
                                    $fonts = [
                                        'Arial' => 'Arial (Reference Hospital Default)',
                                        'Inter' => 'Inter (Modern Clean Sans)',
                                        'Roboto' => 'Roboto (Standard Sans)',
                                        'Cambria' => 'Cambria (Traditional Serif)',
                                        'Calibri' => 'Calibri (Clinical Document)',
                                        'Segoe UI' => 'Segoe UI (System Clean)',
                                        'Times New Roman' => 'Times New Roman (Classic Serif)',
                                        'Outfit' => 'Outfit (Geometric Clean)'
                                    ];
                                    $activeFont = $cfg['font_family'] ?? 'Arial';
                                    foreach ($fonts as $fk => $fl): ?>
                                        <option value="<?= $fk ?>" <?= ($activeFont === $fk) ? 'selected' : '' ?>><?= $fl ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color:#64748b;font-size:11px;margin-top:4px;display:block;">Updates all text elements, table headers, labels, and notes in real time.</small>
                            </div>
                        </div>

                        <!-- Paper Size & Orientation -->
                        <div class="eka-section-card">
                            <div class="form-group-studio" style="margin-bottom:10px;">
                                <label for="pageSizeSelect">Paper Preset</label>
                                <select id="pageSizeSelect" class="studio-select">
                                    <?php foreach ($paperPresets as $pk => $pv): ?>
                                        <option value="<?= $pk ?>" <?= ($pageConfig['pageSize'] === $pk) ? 'selected' : '' ?> data-w="<?= $pv['width'] ?>" data-h="<?= $pv['height'] ?>" data-u="<?= $pv['unit'] ?>">
                                            <?= $pv['name'] ?> (<?= $pv['desc'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-studio">
                                <label>Orientation</label>
                                <div class="studio-btn-group">
                                    <button type="button" id="btnOrientPortrait" class="btn btn-sm <?= ($pageConfig['orientation'] === 'portrait') ? 'btn-primary' : 'btn-soft' ?>" style="width:100%;">
                                        ↕ Portrait
                                    </button>
                                    <button type="button" id="btnOrientLandscape" class="btn btn-sm <?= ($pageConfig['orientation'] === 'landscape') ? 'btn-primary' : 'btn-soft' ?>" style="width:100%;">
                                        ↔ Landscape
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Margins -->
                        <div class="eka-section-card">
                            <div class="form-group-studio">
                                <label>Sheet Margins (mm)</label>
                                <div class="margins-grid">
                                    <div class="margin-input-box">
                                        <span class="margin-lbl">Top</span>
                                        <input type="number" step="0.5" id="marginTop" value="<?= $pageConfig['marginTop'] ?>" class="studio-input">
                                    </div>
                                    <div class="margin-input-box">
                                        <span class="margin-lbl">Right</span>
                                        <input type="number" step="0.5" id="marginRight" value="<?= $pageConfig['marginRight'] ?>" class="studio-input">
                                    </div>
                                    <div class="margin-input-box">
                                        <span class="margin-lbl">Bottom</span>
                                        <input type="number" step="0.5" id="marginBottom" value="<?= $pageConfig['marginBottom'] ?>" class="studio-input">
                                    </div>
                                    <div class="margin-input-box">
                                        <span class="margin-lbl">Left</span>
                                        <input type="number" step="0.5" id="marginLeft" value="<?= $pageConfig['marginLeft'] ?>" class="studio-input">
                                    </div>
                                </div>
                            </div>

                            <div class="studio-metrics-box" style="margin-top:10px;">
                                <div class="metrics-row"><span>Paper Dimension:</span> <strong id="metricDimensions"><?= $pageConfig['width'] ?> × <?= $pageConfig['height'] ?> <?= $pageConfig['unit'] ?></strong></div>
                                <div class="metrics-row"><span>Printable Area:</span> <strong id="metricPrintable"><?= $pageConfig['contentWidth'] ?> × <?= $pageConfig['contentHeight'] ?> <?= $pageConfig['unit'] ?></strong></div>
                            </div>

                            <button type="button" class="btn btn-soft btn-sm studio-save-btn" id="btnSavePageSettings" style="margin-top:10px;">
                                Save Page Settings
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: Doctor & Clinical Vitals -->
                    <div class="studio-tab-pane" id="tab-doctor" role="tabpanel">
                        <div class="pane-header">
                            <h4 class="pane-title">Doctor & Clinical Vitals</h4>
                            <p class="pane-desc">Physician name header and 8-parameter vitals grid.</p>
                        </div>

                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Patient Info & Doctor Box Visibility</span>
                            </div>
                            <label class="eka-switch-label" style="margin-bottom:8px;">
                                <input type="checkbox" id="chkShowPatientInfo" <?= ($cfg['show_patient_info'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Print Patient Information Box</span>
                            </label>
                            <label class="eka-switch-label">
                                <input type="checkbox" id="chkShowDoctorBox" <?= ($cfg['show_doctor_box'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Print Doctor Banner</span>
                            </label>
                        </div>

                        <div class="eka-section-card">
                            <div class="form-group-studio" style="margin-bottom:10px;">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Consulting Doctor Name</label>
                                <input type="text" id="doctorNameInput" class="studio-input" value="<?= htmlspecialchars($cfg['doctor_name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Dr. ANVITI SARAF">
                            </div>
                            <div class="form-group-studio">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Department / Specialty</label>
                                <input type="text" id="doctorDeptInput" class="studio-input" value="<?= htmlspecialchars($cfg['doctor_dept'], ENT_QUOTES, 'UTF-8') ?>" placeholder="IVF">
                            </div>
                        </div>

                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Clinical Vitals Box</span>
                            </div>
                            <label class="eka-switch-label">
                                <input type="checkbox" id="chkShowVitals" <?= !empty($sections['show_vitals']) ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Show 8-Parameter Vitals Grid</span>
                            </label>
                            <small style="color:#64748b;font-size:11px;line-height:1.4;margin-top:6px;display:block;">
                                Displays Height, Weight, Temp, Pulse, Pain Score, Allergies, BMI, and BP with unit designations.
                            </small>
                        </div>
                    </div>

                    <!-- TAB 4: Validity Note, Signing & Watermark -->
                    <div class="studio-tab-pane" id="tab-content" role="tabpanel">
                        <div class="pane-header">
                            <h4 class="pane-title">Validity Note, Signing & Watermark</h4>
                            <p class="pane-desc">Legal validity statement, doctor signature line, and subtle watermark.</p>
                        </div>

                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Validity Note Visibility</span>
                            </div>
                            <label class="eka-switch-label" style="margin-bottom:8px;">
                                <input type="checkbox" id="chkShowValidityNote" <?= ($cfg['show_validity_note'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Print Validity Notice (Left Footer)</span>
                            </label>
                            <div class="form-group-studio">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Validity Notice Text</label>
                                <textarea id="validityNoteInput" class="studio-input" rows="2" style="font-size:12px;resize:vertical;"><?= htmlspecialchars($cfg['validity_note'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                <small style="color:#64748b;font-size:10.5px;margin-top:3px;display:block;">E.g. Bill is valid for 3 days Including date of Billing.</small>
                            </div>
                        </div>

                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Doctor's Signature / Stamp</span>
                            </div>
                            <label class="eka-switch-label" style="margin-bottom:8px;">
                                <input type="checkbox" id="chkShowSignature" <?= !empty($sections['show_signature']) ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Print Signature & Stamp Line</span>
                            </label>
                            <div class="form-group-studio">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Signature Label</label>
                                <input type="text" id="lblSignatureInput" class="studio-input" value="<?= htmlspecialchars($cfg['lbl_signature'] ?? "Doctor's Signature / Stamp", ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>

                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Hospital Logo Watermark</span>
                            </div>
                            <label class="eka-switch-label" style="margin-bottom:8px;">
                                <input type="checkbox" id="chkShowWatermark" <?= !empty($cfg['show_watermark']) ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Enable Background Watermark</span>
                            </label>
                            <div class="form-group-studio" style="margin-bottom:8px;">
                                <label class="studio-slider-label">
                                    <span>Watermark Opacity</span>
                                    <span id="wmOpacityVal" class="slider-badge"><?= round((float)($cfg['watermark_opacity'] ?? 0.06) * 100) ?>%</span>
                                </label>
                                <input type="range" id="watermarkOpacitySlider" min="1" max="25" value="<?= round((float)($cfg['watermark_opacity'] ?? 0.06) * 100) ?>" class="studio-range">
                            </div>
                            <div class="form-group-studio">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Position</label>
                                <select id="watermarkPosSelect" class="studio-select">
                                    <option value="bottom-right" <?= ($cfg['watermark_position'] ?? '') === 'bottom-right' ? 'selected' : '' ?>>Bottom Right (Motherland Default)</option>
                                    <option value="center" <?= ($cfg['watermark_position'] ?? '') === 'center' ? 'selected' : '' ?>>Center</option>
                                    <option value="bottom-center" <?= ($cfg['watermark_position'] ?? '') === 'bottom-center' ? 'selected' : '' ?>>Bottom Center</option>
                                    <option value="bottom-left" <?= ($cfg['watermark_position'] ?? '') === 'bottom-left' ? 'selected' : '' ?>>Bottom Left</option>
                                    <option value="top-right" <?= ($cfg['watermark_position'] ?? '') === 'top-right' ? 'selected' : '' ?>>Top Right</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: Footer & Contacts -->
                    <div class="studio-tab-pane" id="tab-footer" role="tabpanel">
                        <div class="pane-header">
                            <h4 class="pane-title">3-Column Hospital Footer & Contacts</h4>
                            <p class="pane-desc">Hospital location, registered office, phone, WhatsApp, email & web.</p>
                        </div>

                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Footer Block Visibility</span>
                            </div>
                            <label class="eka-switch-label">
                                <input type="checkbox" id="chkShowFooter" <?= ($cfg['show_footer'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <span class="eka-switch-text">Print Footer Block (Address & Contacts)</span>
                            </label>
                        </div>

                        <!-- Footer Mode & Height -->
                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">Footer Mode & Height</span>
                                <span id="badgeFooterMode" class="badge-eka-mode <?= ($sections['footer_mode'] === 'digital') ? 'badge-mode-digital' : 'badge-mode-blank' ?>">
                                    <?= ($sections['footer_mode'] === 'digital') ? 'Digital Print' : 'Pre-printed Pad' ?>
                                </span>
                            </div>
                            <div class="eka-btn-segmented">
                                <button type="button" class="btn-seg <?= ($sections['footer_mode'] === 'digital') ? 'active' : '' ?>" id="btnFooterDigital" data-footer-mode="digital">
                                    🩺 Digital Footer
                                </button>
                                <button type="button" class="btn-seg <?= ($sections['footer_mode'] === 'blank') ? 'active' : '' ?>" id="btnFooterBlank" data-footer-mode="blank">
                                    📄 Pre-printed Pad Blank
                                </button>
                            </div>
                            <div class="eka-control-row">
                                <label class="studio-slider-label">
                                    <span>Footer Height / Margin</span>
                                    <span id="footerHeightVal" class="slider-badge"><?= (int)$sections['footer_height'] ?> mm</span>
                                </label>
                                <input type="range" id="footerHeightSlider" min="15" max="80" step="1" value="<?= (int)$sections['footer_height'] ?>" class="studio-range">
                            </div>
                            <div class="eka-presets-row">
                                <button type="button" class="btn-chip" data-set-fheight="20">Compact 20mm</button>
                                <button type="button" class="btn-chip" data-set-fheight="28">Standard 28mm</button>
                                <button type="button" class="btn-chip" data-set-fheight="40">Large 40mm</button>
                            </div>
                        </div>

                        <!-- Address & Reg Office -->
                        <div class="eka-section-card">
                            <div class="form-group-studio" style="margin-bottom:10px;">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Hospital Address (Column 1)</label>
                                <textarea id="hospitalAddressInput" class="studio-input" rows="2"><?= htmlspecialchars($cfg['hospital_address'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                            <div class="form-group-studio">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Registered Office / CIN (Column 1)</label>
                                <textarea id="regOfficeInput" class="studio-input" rows="3"><?= htmlspecialchars($cfg['reg_office'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <!-- Contacts (Column 2 & 3) -->
                        <div class="eka-section-card">
                            <div class="form-group-studio" style="margin-bottom:10px;">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">WhatsApp / Mobile (Column 2)</label>
                                <input type="text" id="phoneWhatsappInput" class="studio-input" value="<?= htmlspecialchars($cfg['phone_whatsapp'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-group-studio" style="margin-bottom:10px;">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Landline / Emergency (Column 2)</label>
                                <input type="text" id="phoneLandlineInput" class="studio-input" value="<?= htmlspecialchars($cfg['phone_landline'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-group-studio" style="margin-bottom:10px;">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Email Address (Column 3)</label>
                                <input type="text" id="emailInput" class="studio-input" value="<?= htmlspecialchars($cfg['email'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-group-studio">
                                <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Website URL (Column 3)</label>
                                <input type="text" id="websiteInput" class="studio-input" value="<?= htmlspecialchars($cfg['website'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- TAB 1 FOR IMAGE: Field Inspector & Nudge Calibration -->
                    <div class="studio-tab-pane active" id="tab-fields" role="tabpanel">
                        <div class="pane-header">
                            <h4 class="pane-title">Field Alignment & Nudge</h4>
                            <p class="pane-desc">Click any field on paper to calibrate position, font, or label mode.</p>
                        </div>

                        <!-- Selected Field Inspector Box -->
                        <div class="eka-section-card" id="fieldInspectorCard" style="border-left:3.5px solid #0284c7;">
                            <div class="eka-card-header">
                                <span class="eka-card-title" id="inspFieldTitle">1. Field Mode & Visibility</span>
                                <span class="badge-tag-pill image" id="inspFieldKeyBadge">Select Field</span>
                            </div>

                            <div id="inspFieldBody">
                                <!-- Mode Switcher -->
                                <div class="form-group-studio" style="margin-bottom:10px;">
                                    <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:4px;">Print Label Format</label>
                                    <div class="eka-btn-segmented">
                                        <button type="button" class="btn-seg active" id="btnModeValOnly" title="Prints only value on pre-printed lines">Value Only</button>
                                        <button type="button" class="btn-seg" id="btnModeLabelVal" title="Prints 'Label: Value'">Label + Value</button>
                                    </div>
                                    <small style="color:#64748b;font-size:11px;line-height:1.35;display:block;">
                                        💡 Use <strong>Value Only</strong> if your physical pad paper already has words like <em>UHID:</em> or <em>Patient:</em> printed on it.
                                    </small>
                                </div>

                                <!-- Field Visibility Toggle -->
                                <label class="eka-switch-label" style="margin-bottom:12px;">
                                    <input type="checkbox" id="chkFieldVisible" checked>
                                    <span class="eka-switch-text">Print this field on paper</span>
                                </label>

                                <!-- Nudge D-Pad -->
                                <div class="form-group-studio" style="margin-bottom:10px; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:10px;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                        <label style="font-size:11.5px;font-weight:700;color:#0f172a;margin:0;">Millimeter Nudge Controls</label>
                                        <span style="font-size:10px;color:#0284c7;font-weight:600;background:#e0f2fe;padding:1px 6px;border-radius:4px;">Keys: ↑ ↓ ← →</span>
                                    </div>
                                    
                                    <div class="nudge-dpad-wrap" style="display:flex;justify-content:center;margin:6px 0;">
                                        <div class="nudge-dpad">
                                            <button type="button" class="btn-nudge" id="nudgeUp" title="Nudge Up (ArrowUp)">▲</button>
                                            <button type="button" class="btn-nudge" id="nudgeLeft" title="Nudge Left (ArrowLeft)">◀</button>
                                            <div class="nudge-center-indicator">0.2%</div>
                                            <button type="button" class="btn-nudge" id="nudgeRight" title="Nudge Right (ArrowRight)">▶</button>
                                            <button type="button" class="btn-nudge" id="nudgeDown" title="Nudge Down (ArrowDown)">▼</button>
                                        </div>
                                    </div>

                                    <!-- Direct Exact Coordinates Input -->
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px;">
                                        <div>
                                            <span style="font-size:10.5px;color:#64748b;font-weight:600;">Left (X %):</span>
                                            <input type="number" step="0.1" id="fieldCoordX" class="studio-input" style="font-size:12px;padding:4px 8px;">
                                        </div>
                                        <div>
                                            <span style="font-size:10.5px;color:#64748b;font-weight:600;">Top (Y %):</span>
                                            <input type="number" step="0.1" id="fieldCoordY" class="studio-input" style="font-size:12px;padding:4px 8px;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Typography Controls -->
                                <div class="form-group-studio" style="margin-bottom:10px;">
                                    <label class="studio-slider-label">
                                        <span>Font Size</span>
                                        <span id="fontSizeValue" class="slider-badge">12 px</span>
                                    </label>
                                    <input type="range" id="fontSize" min="8" max="32" value="12" class="studio-range">
                                </div>

                                <div class="form-group-studio" style="margin-bottom:10px;">
                                    <label class="studio-slider-label">
                                        <span>Field Width</span>
                                        <span id="fieldWidthValue" class="slider-badge">220 px</span>
                                    </label>
                                    <input type="range" id="fieldWidth" min="40" max="500" value="220" class="studio-range">
                                </div>

                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
                                    <div>
                                        <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Weight</label>
                                        <select id="fieldFontWeight" class="studio-select">
                                            <option value="400">Regular (400)</option>
                                            <option value="500">Medium (500)</option>
                                            <option value="600" selected>Semi-Bold (600)</option>
                                            <option value="700">Bold (700)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Alignment</label>
                                        <select id="fieldAlign" class="studio-select">
                                            <option value="left" selected>Left</option>
                                            <option value="center">Center</option>
                                            <option value="right">Right</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group-studio">
                                    <label style="font-size:11px;font-weight:700;color:#334155;display:block;margin-bottom:3px;">Text Ink Color</label>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <input type="color" id="fieldColorPicker" value="#111827" style="width:34px;height:30px;padding:1px;border:1px solid #cbd5e1;border-radius:4px;cursor:pointer;">
                                        <input type="text" id="fieldColorHex" value="#111827" class="studio-input" maxlength="7" style="font-size:12px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Fields List Navigator -->
                        <div class="pane-header" style="margin-top:16px;">
                            <h4 class="pane-title" style="font-size:12px;">All Template Fields (Click to Select)</h4>
                            <p class="pane-desc">Select any item to focus and adjust position on paper.</p>
                        </div>
                        <div class="sections-list" id="imageFieldsList">
                            <?php foreach (FIELD_DEFS as $k => $label): if (empty($perm[$k]['visible'])) continue; ?>
                                <button type="button" class="palette-item studio-section-btn" data-focus-field="<?= e($k) ?>" id="navFieldBtn_<?= e($k) ?>">
                                    <span class="section-drag-handle">⠿</span>
                                    <span class="section-label"><?= e($label) ?></span>
                                    <span class="badge-tag-mini" id="badgeFStatus_<?= e($k) ?>" style="font-size:9.5px;padding:1px 6px;border-radius:4px;background:#e0f2fe;color:#0369a1;margin-left:auto;">Val</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB 2 FOR IMAGE: Paper Size -->
                    <div class="studio-tab-pane" id="tab-page" role="tabpanel">
                        <div class="pane-header">
                            <h4 class="pane-title">Page Dimensions & Margins</h4>
                            <p class="pane-desc">Set the paper size, print orientation, and boundary margins.</p>
                        </div>

                        <div class="form-group-studio">
                            <label for="pageSizeSelect">Paper Size</label>
                            <select id="pageSizeSelect" class="studio-select">
                                <?php foreach ($paperPresets as $pk => $pv): ?>
                                    <option value="<?= $pk ?>" <?= ($pageConfig['pageSize'] === $pk) ? 'selected' : '' ?> data-w="<?= $pv['width'] ?>" data-h="<?= $pv['height'] ?>" data-u="<?= $pv['unit'] ?>">
                                        <?= $pv['name'] ?> (<?= $pv['desc'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group-studio">
                            <label>Orientation</label>
                            <div class="studio-btn-group">
                                <button type="button" id="btnOrientPortrait" class="btn btn-sm <?= ($pageConfig['orientation'] === 'portrait') ? 'btn-primary' : 'btn-soft' ?>" style="width:100%;">
                                    ↕ Portrait
                                </button>
                                <button type="button" id="btnOrientLandscape" class="btn btn-sm <?= ($pageConfig['orientation'] === 'landscape') ? 'btn-primary' : 'btn-soft' ?>" style="width:100%;">
                                    ↔ Landscape
                                </button>
                            </div>
                        </div>

                        <!-- Custom Dimensions (Shown only when Custom is chosen) -->
                        <div id="customDimensionsWrap" style="display: <?= ($pageConfig['pageSize'] === 'Custom') ? 'block' : 'none' ?>;" class="custom-dims-box">
                            <label class="custom-dims-title">Custom Paper Dimensions</label>
                            <div class="custom-dims-grid">
                                <div>
                                    <span>Width</span>
                                    <input type="number" step="0.1" id="customWidth" value="<?= $pageConfig['baseWidth'] ?>" class="studio-input">
                                </div>
                                <div>
                                    <span>Height</span>
                                    <input type="number" step="0.1" id="customHeight" value="<?= $pageConfig['baseHeight'] ?>" class="studio-input">
                                </div>
                                <div>
                                    <span>Unit</span>
                                    <select id="customUnit" class="studio-select">
                                        <option value="mm" <?= $pageConfig['unit'] === 'mm' ? 'selected' : '' ?>>mm</option>
                                        <option value="cm" <?= $pageConfig['unit'] === 'cm' ? 'selected' : '' ?>>cm</option>
                                        <option value="in" <?= $pageConfig['unit'] === 'in' ? 'selected' : '' ?>>inch</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Margins -->
                        <div class="form-group-studio">
                            <label>Margins (<span id="marginUnitLabel"><?= $pageConfig['unit'] ?></span>)</label>
                            <div class="margins-grid">
                                <div class="margin-input-box">
                                    <span class="margin-lbl">Top</span>
                                    <input type="number" step="0.5" id="marginTop" value="<?= $pageConfig['marginTop'] ?>" class="studio-input">
                                </div>
                                <div class="margin-input-box">
                                    <span class="margin-lbl">Right</span>
                                    <input type="number" step="0.5" id="marginRight" value="<?= $pageConfig['marginRight'] ?>" class="studio-input">
                                </div>
                                <div class="margin-input-box">
                                    <span class="margin-lbl">Bottom</span>
                                    <input type="number" step="0.5" id="marginBottom" value="<?= $pageConfig['marginBottom'] ?>" class="studio-input">
                                </div>
                                <div class="margin-input-box">
                                    <span class="margin-lbl">Left</span>
                                    <input type="number" step="0.5" id="marginLeft" value="<?= $pageConfig['marginLeft'] ?>" class="studio-input">
                                </div>
                            </div>
                        </div>

                        <!-- Live Metrics Badge -->
                        <div class="studio-metrics-box">
                            <div class="metrics-row"><span>Paper Dimension:</span> <strong id="metricDimensions"><?= $pageConfig['width'] ?> × <?= $pageConfig['height'] ?> <?= $pageConfig['unit'] ?></strong></div>
                            <div class="metrics-row"><span>Printable Area:</span> <strong id="metricPrintable"><?= $pageConfig['contentWidth'] ?> × <?= $pageConfig['contentHeight'] ?> <?= $pageConfig['unit'] ?></strong></div>
                        </div>

                        <button type="button" class="btn btn-soft btn-sm studio-save-btn" id="btnSavePageSettings">
                            Save Page Settings
                        </button>
                    </div>

                    <!-- TAB 3 FOR IMAGE: Pad Scan Tools & Calibration -->
                    <div class="studio-tab-pane" id="tab-pad" role="tabpanel">
                        <div class="pane-header">
                            <h4 class="pane-title">Pad Scan Tools & Calibration</h4>
                            <p class="pane-desc">Control scan opacity, test print calibration, and update pad image.</p>
                        </div>

                        <!-- Pad Scan Opacity Slider -->
                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">1. Pad Scan Opacity</span>
                                <span id="padOpacityVal" class="badge-tag-pill image" style="font-weight:700;">100%</span>
                            </div>
                            <input type="range" id="padOpacitySlider" min="0" max="100" value="100" class="studio-range">
                            <div style="display:flex;justify-content:space-between;font-size:10.5px;color:#64748b;margin-top:4px;">
                                <span>0% (Blank Paper)</span>
                                <span>50%</span>
                                <span>100% (Full Pad Scan)</span>
                            </div>
                            <small style="color:#64748b;font-size:11px;line-height:1.4;margin-top:6px;display:block;">
                                💡 Slide to <strong>0%</strong> to verify what the printer will actually print onto your physical stationery paper.
                            </small>
                        </div>

                        <!-- 10mm Calibration Grid -->
                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">2. Calibration Metric Grid</span>
                            </div>
                            <label class="eka-switch-label">
                                <input type="checkbox" id="chkCalibrationGrid">
                                <span class="eka-switch-text">Show 10mm × 10mm Alignment Grid</span>
                            </label>
                            <small style="color:#64748b;font-size:11px;margin-top:4px;display:block;">
                                Overlays metric 10mm grid lines so you can align fields with a physical ruler against printed stationery.
                            </small>
                        </div>

                        <!-- Physical Print Test -->
                        <div class="eka-section-card">
                            <div class="eka-card-header">
                                <span class="eka-card-title">3. Physical Print Test</span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <a href="print_opd.php?template_id=<?= $tpl['id'] ?>" target="_blank" class="action-btn-pill primary" style="text-align:center;justify-content:center;font-weight:700;">
                                    🖨️ Test Print (Text Only on Physical Pad) &rarr;
                                </a>
                                <a href="print_opd.php?template_id=<?= $tpl['id'] ?>&preview_bg=1" target="_blank" class="action-btn-pill" style="text-align:center;justify-content:center;">
                                    📄 Test Print on Blank A4 Paper (with Background)
                                </a>
                            </div>
                        </div>

                <!-- TAB 3 FOR IMAGE: Pad Scan Tools & Calibration -->
                <div class="studio-tab-pane" id="tab-pad" role="tabpanel">
                    <div class="pane-header">
                        <h4 class="pane-title">Pad Scan Tools & Calibration</h4>
                        <p class="pane-desc">Control scan opacity, test print calibration, and update pad image.</p>
                    </div>

                    <!-- Pad Scan Opacity Slider -->
                    <div class="eka-section-card">
                        <div class="eka-card-header">
                            <span class="eka-card-title">1. Pad Scan Opacity</span>
                            <span id="padOpacityVal" class="badge-tag-pill image" style="font-weight:700;">100%</span>
                        </div>
                        <input type="range" id="padOpacitySlider" min="0" max="100" value="100" class="studio-range">
                        <div style="display:flex;justify-content:space-between;font-size:10.5px;color:#64748b;margin-top:4px;">
                            <span>0% (Blank Paper)</span>
                            <span>50%</span>
                            <span>100% (Full Pad Scan)</span>
                        </div>
                        <small style="color:#64748b;font-size:11px;line-height:1.4;margin-top:6px;display:block;">
                            💡 Slide to <strong>0%</strong> to verify what the printer will actually print onto your physical stationery paper.
                        </small>
                    </div>

                    <!-- 10mm Calibration Grid -->
                    <div class="eka-section-card">
                        <div class="eka-card-header">
                            <span class="eka-card-title">2. Calibration Metric Grid</span>
                        </div>
                        <label class="eka-switch-label">
                            <input type="checkbox" id="chkCalibrationGrid">
                            <span class="eka-switch-text">Show 10mm × 10mm Alignment Grid</span>
                        </label>
                        <small style="color:#64748b;font-size:11px;margin-top:4px;display:block;">
                            Overlays metric 10mm grid lines so you can align fields with a physical ruler against printed stationery.
                        </small>
                    </div>

                    <!-- Physical Print Test -->
                    <div class="eka-section-card">
                        <div class="eka-card-header">
                            <span class="eka-card-title">3. Physical Print Test</span>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <a href="print_opd.php?template_id=<?= $tpl['id'] ?>" target="_blank" class="action-btn-pill primary" style="text-align:center;justify-content:center;font-weight:700;">
                                🖨️ Test Print (Text Only on Physical Pad) &rarr;
                            </a>
                            <a href="print_opd.php?template_id=<?= $tpl['id'] ?>&preview_bg=1" target="_blank" class="action-btn-pill" style="text-align:center;justify-content:center;">
                                📄 Test Print on Blank A4 Paper (with Background)
                            </a>
                        </div>
                    </div>

                    <!-- Replace Pad Scan Form -->
                    <div class="eka-section-card">
                        <div class="eka-card-header">
                            <span class="eka-card-title">4. Replace Pad Scan Photo</span>
                        </div>
                        <form method="post" action="template_editor.php?template_id=<?= $tpl['id'] ?>" enctype="multipart/form-data">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="replace_pad_scan">
                            <input type="hidden" name="template_id" value="<?= $tpl['id'] ?>">
                            <input type="file" name="pad_scan_file" accept="image/jpeg,image/png,image/webp" class="studio-input" style="font-size:11px;padding:5px;" required>
                            <button type="submit" class="btn btn-sm btn-primary" style="margin-top:8px;width:100%;font-weight:700;">
                                Update Scan Image
                            </button>
                        </form>
                        <small style="color:#64748b;font-size:10.5px;margin-top:4px;display:block;">All saved field coordinates will be retained cleanly.</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Canvas Area -->
        <section class="card canvas-card">
            <style>
                /* Common Studio D-Pad & Calibration Styles */
                .nudge-dpad {
                    display: grid;
                    grid-template-columns: 36px 36px 36px;
                    grid-template-rows: 36px 36px 36px;
                    gap: 5px;
                    align-items: center;
                    justify-items: center;
                }
                .btn-nudge {
                    width: 36px;
                    height: 36px;
                    background: #f8fafc;
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 13px;
                    font-weight: 700;
                    color: #334155;
                    cursor: pointer;
                    transition: all 0.12s ease;
                    user-select: none;
                }
                .btn-nudge:hover {
                    background: #0284c7;
                    color: #ffffff;
                    border-color: #0284c7;
                    transform: scale(1.05);
                }
                .btn-nudge:active {
                    transform: scale(0.94);
                }
                .nudge-center-indicator {
                    grid-column: 2;
                    grid-row: 2;
                    font-size: 10px;
                    font-weight: 700;
                    color: #64748b;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                /* Image Template Canvas Layering & Field Styling */
                .image-paper-canvas {
                    background: #ffffff !important;
                    position: relative !important;
                    box-shadow: 0 16px 50px rgba(0,0,0,0.15) !important;
                    border: 1px solid #cbd5e1 !important;
                    overflow: hidden !important;
                    box-sizing: border-box !important;
                    touch-action: none;
                }

                .calibration-grid-overlay {
                    position: absolute;
                    inset: 0;
                    pointer-events: none;
                    z-index: 5;
                    background-size: 10mm 10mm;
                    background-image: 
                        linear-gradient(to right, rgba(2, 132, 199, 0.22) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(2, 132, 199, 0.22) 1px, transparent 1px);
                }

                .draggable-field {
                    position: absolute;
                    cursor: move;
                    user-select: none;
                    touch-action: none;
                    border: 1px dashed rgba(2, 132, 199, 0.45);
                    background: rgba(255, 255, 255, 0.9);
                    border-radius: 4px;
                    padding: 2px 6px;
                    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
                    line-height: 1.25;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    transition: box-shadow 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
                }
                .draggable-field:hover {
                    border-color: #0284c7;
                    background: rgba(240, 249, 255, 0.96);
                    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.22);
                }
                .draggable-field.selected {
                    border: 2px solid #0284c7 !important;
                    background: #ffffff !important;
                    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.25), 0 4px 14px rgba(0,0,0,0.12) !important;
                    z-index: 50 !important;
                }
                .draggable-field.is-hidden-field {
                    opacity: 0.35 !important;
                    border-style: dotted !important;
                    border-color: #ef4444 !important;
                    background: rgba(254, 242, 242, 0.7) !important;
                }
                .draggable-field .field-coord-badge {
                    position: absolute;
                    top: -16px;
                    left: 0;
                    background: #0284c7;
                    color: #ffffff;
                    font-size: 9px;
                    font-weight: 700;
                    padding: 1px 5px;
                    border-radius: 3px;
                    pointer-events: none;
                    white-space: nowrap;
                    display: none;
                }
                .draggable-field.selected .field-coord-badge {
                    display: block;
                }

                .palette-item.is-active-field {
                    border-color: #0284c7 !important;
                    background: #e0f2fe !important;
                    color: #0369a1 !important;
                    font-weight: 700 !important;
                }
            </style>
            <?php if ($isCode): ?>
                <style>
                    <?= get_motherland_opd_css() ?>
                    <?= generate_template_page_css($tpl, false) ?>
                    .code-sheet-wrapper {
                        display: flex;
                        justify-content: center;
                        align-items: flex-start;
                        width: 100%;
                        min-height: 850px;
                        background: #eaedf1;
                        padding: 24px 0;
                        overflow-x: auto;
                        border-radius: 8px;
                    }
                    .code-sheet-wrapper .motherland-sheet {
                        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.16);
                        transform-origin: top center;
                        transition: transform 0.2s ease;
                    }
                    .btn-zoom.active {
                        background: var(--primary, #008F3D) !important;
                        color: #ffffff !important;
                    }
                </style>

                <?php
                $samplePatient = [
                    'uhid' => 'MLH/26/000002',
                    'name' => 'Mrs. Sunita Sharma',
                    'age' => '28 Yrs',
                    'sex' => 'Female',
                    'age_sex' => '28 Yrs / Female',
                    'guardian' => 'Mr. Rajesh Sharma',
                    'contact_number' => '+91 98765 43210',
                    'address' => 'Sector 119, Noida',
                    'bill_no' => 'MOB/26-27/000002',
                    'visit_date' => date('Y-m-d'),
                    'visit_time' => date('H:i:s'),
                    'panel' => 'CASH',
                    'doctor_dept' => $cfg['doctor_dept'] ?? 'IVF',
                    'doctor_name' => $cfg['doctor_name'] ?? 'Dr. ANVITI SARAF',
                    'room_no' => '102',
                    'app_no' => '1'
                ];
                ?>

                <!-- Zoom & Preview Toolbar -->
                <div class="code-canvas-toolbar" style="display:flex;align-items:center;justify-content:space-between;width:100%;max-width:850px;margin-bottom:12px;background:#ffffff;padding:8px 16px;border-radius:8px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:12px;font-weight:700;color:#0f172a;">A4 Live Document Preview:</span>
                        <span class="badge-tag-mini" style="background:#e6f8f3;color:#087f6c;font-weight:700;">100% Exact Hospital Print</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:11.5px;color:#64748b;font-weight:600;">Zoom:</span>
                        <button type="button" class="btn btn-sm btn-soft btn-zoom" data-zoom="0.80" style="padding:2px 8px;font-size:11px;">80%</button>
                        <button type="button" class="btn btn-sm btn-soft btn-zoom" data-zoom="0.90" style="padding:2px 8px;font-size:11px;">90%</button>
                        <button type="button" class="btn btn-sm btn-primary btn-zoom active" data-zoom="1.0" style="padding:2px 8px;font-size:11px;">100%</button>
                        <a href="print_opd.php?template_id=<?= $tpl['id'] ?>" target="_blank" class="btn btn-sm btn-soft" style="padding:2px 10px;font-size:11px;font-weight:700;margin-left:8px;" title="Open Print Preview in new tab">🖨️ Test Print ↗</a>
                    </div>
                </div>

                <div class="code-sheet-wrapper" id="codeSheetWrapper">
                    <div id="layoutPaper" style="margin: 0 auto;">
                        <?= render_motherland_opd($samplePatient, [], true, $layout, 1, $tpl) ?>
                    </div>
                </div>

                <script>
                    window.EDITOR_DATA = {
                        templateId: <?= $tpl['id'] ?>,
                        csrf: '<?= csrf_token() ?>',
                        isCode: true,
                        sections: <?= json_encode($sections) ?>,
                        codeConfig: <?= json_encode($cfg) ?>,
                        pageConfig: <?= json_encode($pageConfig) ?>,
                        paperPresets: <?= json_encode($paperPresets) ?>
                    };
                </script>
                <?php $teJsVer = file_exists(__DIR__ . '/assets/js/template-editor.js') ? filemtime(__DIR__ . '/assets/js/template-editor.js') : '3.0'; ?>
                <script src="assets/js/template-editor.js?v=<?= $teJsVer ?>"></script>

            <?php else: ?>
                <!-- Image Template Calibration Canvas -->
                <?php
                $sampleData = [
                    'uhid' => 'MLH/26/000002',
                    'name' => 'Mrs. Sunita Sharma',
                    'age_sex' => '28 Yrs / Female',
                    'guardian' => 'Mr. Rajesh Sharma',
                    'contact_number' => '+91 98765 43210',
                    'address' => 'Sector 119, Noida',
                    'bill_no' => 'MOB/26-27/000002',
                    'date' => date('d-M-Y h:i A'),
                    'panel' => 'CASH',
                    'doctor_dept' => 'IVF - Dr. A. Sharma',
                    'room_no' => '102',
                    'app_no' => '1'
                ];
                ?>
                <div class="layout-paper image-paper-canvas" id="layoutPaper" style="aspect-ratio:<?= $pageConfig['width'] ?>/<?= $pageConfig['height'] ?>; position:relative; overflow:hidden; background:#ffffff;">
                    <!-- Pad Scan Background Layer (Opacity Controlled) -->
                    <div id="padScanLayer" style="position:absolute; inset:0; background: url('<?= e($tpl['file_path']) ?>') center/100% 100% no-repeat; opacity:1; transition:opacity 0.15s ease; pointer-events:none; z-index:1;"></div>
                    
                    <!-- 10mm Calibration Metric Grid Layer -->
                    <div id="calibrationGridLayer" class="calibration-grid-overlay" style="display:none;"></div>

                    <!-- Draggable Interactive Fields -->
                    <?php foreach ($layout as $k => $pos): 
                        if (!isset(FIELD_DEFS[$k]) || empty($perm[$k]['visible'])) continue; 
                        $val = $sampleData[$k] ?? 'Sample Value';
                        $showLbl = !empty($pos['showLabel']);
                        $isVis = (!isset($pos['visible']) || !empty($pos['visible']));
                        $fw = $pos['fontWeight'] ?? '600';
                        $al = $pos['align'] ?? 'left';
                        $col = $pos['color'] ?? '#111827';
                        $xPct = floatval($pos['x'] ?? 5);
                        $yPct = floatval($pos['y'] ?? 5);
                        $fSize = intval($pos['fontSize'] ?? 11);
                        $fWidth = intval($pos['width'] ?? 220);
                    ?>
                        <div class="draggable-field <?= !$isVis ? 'is-hidden-field' : '' ?>" 
                             data-field="<?= e($k) ?>" 
                             data-label="<?= e(FIELD_DEFS[$k]) ?>"
                             data-sample="<?= e($val) ?>"
                             data-show-label="<?= $showLbl ? '1' : '0' ?>"
                             data-font-size="<?= $fSize ?>"
                             data-font-weight="<?= e($fw) ?>"
                             data-align="<?= e($al) ?>"
                             data-color="<?= e($col) ?>"
                             data-visible="<?= $isVis ? '1' : '0' ?>"
                             style="left:<?= $xPct ?>%;top:<?= $yPct ?>%;font-size:<?= $fSize ?>px;width:<?= $fWidth ?>px;font-weight:<?= e($fw) ?>;text-align:<?= e($al) ?>;color:<?= e($col) ?>;z-index:10;">
                            <span class="field-coord-badge">X: <?= number_format($xPct, 1) ?>% Y: <?= number_format($yPct, 1) ?>%</span>
                            <span class="field-content-box">
                                <?php if ($showLbl): ?>
                                    <span class="field-lbl-part"><?= e(FIELD_DEFS[$k]) ?>: </span>
                                <?php endif; ?>
                                <span class="field-val-part"><?= e($val) ?></span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <script>
                    window.EDITOR_DATA = {
                        templateId: <?= $tpl['id'] ?>,
                        csrf: '<?= csrf_token() ?>',
                        isCode: false,
                        defaultLayout: <?= json_encode(json_decode($tpl['default_layout_json'] ?: '{}', true)) ?>,
                        pageConfig: <?= json_encode($pageConfig) ?>,
                        paperPresets: <?= json_encode($paperPresets) ?>
                    };
                </script>
                <?php $teJsVer = file_exists(__DIR__ . '/assets/js/template-editor.js') ? filemtime(__DIR__ . '/assets/js/template-editor.js') : '2.3'; ?>
                <script src="assets/js/template-editor.js?v=<?= $teJsVer ?>"></script>
            <?php endif; ?>
        </section>
    </div>
<?php else: ?>
    <div class="card empty">No active template found. Ask an admin to activate or configure a template.</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
