<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login();
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/code_template.php';
require_once __DIR__ . '/includes/page_engine.php';

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
$perm = field_permissions((int)$user['id']);

$cfg = get_motherland_config();
$pageConfig = $tpl ? get_template_page_config($tpl) : null;
$paperPresets = get_paper_presets();
$themePresets = get_theme_presets();
$templateTheme = $tpl ? get_template_theme($tpl) : $themePresets['green'];
$activeThemeKey = strtolower(trim((string)($tpl['theme_preset'] ?? 'green')));

// Default positions for code template blocks
$defaultCodeLayout = [
    'block_logo' => ['x' => 4.5, 'y' => 1.8, 'fontSize' => 24, 'width' => 380],
    'block_title' => ['x' => 0.0, 'y' => 9.2, 'fontSize' => 18, 'width' => 790],
    'block_meta' => ['x' => 4.5, 'y' => 13.0, 'fontSize' => 12, 'width' => 720],
    'block_doctor_vitals' => ['x' => 4.5, 'y' => 24.5, 'fontSize' => 14, 'width' => 720],
    'block_validity' => ['x' => 4.5, 'y' => 86.8, 'fontSize' => 11, 'width' => 720],
    'block_footer' => ['x' => 4.5, 'y' => 89.2, 'fontSize' => 10, 'width' => 720]
];

$codeBlocks = [
    'block_logo' => '1. Logo & Hospital Name',
    'block_title' => '2. Consultation Paper Title',
    'block_meta' => '3. Patient Details 2-Col Table',
    'block_doctor_vitals' => '4. Doctor & Vitals Box',
    'block_validity' => '5. Validity Note',
    'block_footer' => '6. 3-Column Footer Contacts'
];

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
            <div class="studio-tabs-nav" role="tablist">
                <button type="button" class="studio-tab-btn active" data-tab="tab-page" role="tab" aria-selected="true">
                    <span>📄</span>
                    <span>Page</span>
                    <span id="badgePagePreset" class="studio-tab-chip"><?= e($pageConfig['pageSize']) ?></span>
                </button>
                <button type="button" class="studio-tab-btn" data-tab="tab-theme" role="tab" aria-selected="false">
                    <span>🎨</span>
                    <span>Theme</span>
                    <span id="tabThemeDot" class="studio-tab-dot" style="background:<?= htmlspecialchars($templateTheme['primary'] ?? '#087F6C') ?>;"></span>
                </button>
                <button type="button" class="studio-tab-btn" data-tab="tab-sections" role="tab" aria-selected="false">
                    <span>📐</span>
                    <span><?= $isCode ? 'Sections' : 'Fields' ?></span>
                </button>
            </div>

            <div class="studio-tabs-body">
                <!-- TAB 1: Page Settings -->
                <div class="studio-tab-pane active" id="tab-page" role="tabpanel">
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

                <!-- TAB 2: Color Theme -->
                <div class="studio-tab-pane" id="tab-theme" role="tabpanel">
                    <div class="pane-header">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                            <h4 class="pane-title" style="margin:0;">Palette Presets</h4>
                            <span id="badgeThemePreset" class="badge-theme-active">
                                <span id="badgeThemeDot" class="studio-swatch-dot" style="background:<?= htmlspecialchars($templateTheme['primary']) ?>;"></span>
                                <span id="badgeThemeName"><?= e($templateTheme['name']) ?></span>
                            </span>
                        </div>
                        <p class="pane-desc">Select a coordinated clinical theme or customize individual shades.</p>
                    </div>

                    <!-- Theme Preset Palette Chips Grid -->
                    <div class="theme-presets-grid" id="themePresetGrid">
                        <?php foreach ($themePresets as $pk => $pv): ?>
                            <button type="button" class="btn-theme-preset <?= ($activeThemeKey === $pk) ? 'is-active-theme' : '' ?>" data-theme-key="<?= $pk ?>">
                                <span class="preset-dots">
                                    <span style="background:<?= $pv['primary'] ?>;"></span>
                                    <span style="background:<?= $pv['accent'] ?>;"></span>
                                </span>
                                <span class="preset-title"><?= $pv['name'] ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Granular Color Customizer -->
                    <div id="customColorsWrap" class="granular-colors-box">
                        <div class="granular-colors-toggle" id="toggleCustomColorsBtn">
                            <span class="toggle-title">Granular Color Customizer</span>
                            <span id="customColorsArrow" class="toggle-arrow">▼</span>
                        </div>
                        <div id="customColorsContent" class="granular-colors-grid">
                            <div class="color-picker-item">
                                <label>Primary Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorPrimary" value="<?= htmlspecialchars($templateTheme['primary']) ?>">
                                    <input type="text" id="themeHexPrimary" value="<?= htmlspecialchars($templateTheme['primary']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>Secondary Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorSecondary" value="<?= htmlspecialchars($templateTheme['secondary']) ?>">
                                    <input type="text" id="themeHexSecondary" value="<?= htmlspecialchars($templateTheme['secondary']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>Accent Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorAccent" value="<?= htmlspecialchars($templateTheme['accent']) ?>">
                                    <input type="text" id="themeHexAccent" value="<?= htmlspecialchars($templateTheme['accent']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>Border Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorBorder" value="<?= htmlspecialchars($templateTheme['border']) ?>">
                                    <input type="text" id="themeHexBorder" value="<?= htmlspecialchars($templateTheme['border']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>Heading Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorHeading" value="<?= htmlspecialchars($templateTheme['heading']) ?>">
                                    <input type="text" id="themeHexHeading" value="<?= htmlspecialchars($templateTheme['heading']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>Text Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorText" value="<?= htmlspecialchars($templateTheme['text']) ?>">
                                    <input type="text" id="themeHexText" value="<?= htmlspecialchars($templateTheme['text']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>Label Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorLabel" value="<?= htmlspecialchars($templateTheme['label']) ?>">
                                    <input type="text" id="themeHexLabel" value="<?= htmlspecialchars($templateTheme['label']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>Icon Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorIcon" value="<?= htmlspecialchars($templateTheme['icon']) ?>">
                                    <input type="text" id="themeHexIcon" value="<?= htmlspecialchars($templateTheme['icon']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>Watermark Color</label>
                                <div class="picker-row">
                                    <input type="color" id="themeColorWatermark" value="<?= htmlspecialchars($templateTheme['watermark']) ?>">
                                    <input type="text" id="themeHexWatermark" value="<?= htmlspecialchars($templateTheme['watermark']) ?>" maxlength="7">
                                </div>
                            </div>
                            <div class="color-picker-item">
                                <label>WM Opacity (<span id="wmOpacityVal"><?= round(($templateTheme['watermarkOpacity'] ?? 0.08) * 100) ?>%</span>)</label>
                                <input type="range" id="themeWmOpacity" min="1" max="30" value="<?= round(($templateTheme['watermarkOpacity'] ?? 0.08) * 100) ?>" class="studio-range">
                            </div>
                        </div>
                    </div>

                    <!-- WCAG Contrast Live Notice -->
                    <div id="contrastNotice" class="contrast-notice-box">
                        <div class="contrast-row">
                            <span>Contrast Ratio: </span><strong id="contrastValue">7.5:1</strong>
                            <span id="contrastBadge" class="contrast-badge">✓ AAA High Print Legibility</span>
                        </div>
                    </div>

                    <button type="button" class="btn btn-soft btn-sm studio-save-btn" id="btnSaveThemeSettings">
                        Save Theme
                    </button>
                </div>

                <!-- TAB 3: Sections / Fields -->
                <div class="studio-tab-pane" id="tab-sections" role="tabpanel">
                    <?php if ($isCode): ?>
                        <div class="pane-header">
                            <h4 class="pane-title">Layout Sections</h4>
                            <p class="pane-desc">Click any block below to focus it, or drag directly on the canvas paper.</p>
                        </div>
                        <div class="sections-list">
                            <?php foreach ($codeBlocks as $k => $label): ?>
                                <button type="button" class="palette-item studio-section-btn" data-focus-field="<?= e($k) ?>">
                                    <span class="section-drag-handle">⠿</span>
                                    <span class="section-label"><?= e($label) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-controls-box">
                            <label class="studio-slider-label">
                                <span>Section Width</span>
                                <span id="fieldWidthValue" class="slider-badge">720 px</span>
                            </label>
                            <input type="range" id="fieldWidth" min="150" max="760" value="720" class="studio-range">
                        </div>
                    <?php else: ?>
                        <div class="pane-header">
                            <h4 class="pane-title">Form Slip Fields</h4>
                            <p class="pane-desc">Drag items onto the paper. Blocked fields are hidden.</p>
                        </div>
                        <div class="sections-list">
                            <?php foreach (FIELD_DEFS as $k => $label): if (empty($perm[$k]['visible'])) continue; ?>
                                <button type="button" class="palette-item studio-section-btn" data-focus-field="<?= e($k) ?>">
                                    <span class="section-drag-handle">⠿</span>
                                    <span class="section-label"><?= e($label) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-controls-box">
                            <label class="studio-slider-label">
                                <span>Font Size</span>
                                <span id="fontSizeValue" class="slider-badge">12 px</span>
                            </label>
                            <input type="range" id="fontSize" min="8" max="28" value="12" class="studio-range">

                            <label class="studio-slider-label" style="margin-top:10px;">
                                <span>Field Width</span>
                                <span id="fieldWidthValue" class="slider-badge">220 px</span>
                            </label>
                            <input type="range" id="fieldWidth" min="60" max="420" value="220" class="studio-range">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- Canvas Area -->
        <section class="card canvas-card">
            <?php if ($isCode): ?>
                <style>
                    <?= get_motherland_opd_css() ?>
                    .layout-paper.code-paper {
                        background: #ffffff;
                        position: relative;
                        width: 100%;
                        aspect-ratio: <?= $pageConfig['width'] ?> / <?= $pageConfig['height'] ?>;
                        border: 1px solid #ccd9d6;
                        box-shadow: 0 12px 30px rgba(18,53,44,0.1);
                        overflow: hidden;
                        padding: <?= $pageConfig['marginTop'] . $pageConfig['unit'] ?> <?= $pageConfig['marginRight'] . $pageConfig['unit'] ?> <?= $pageConfig['marginBottom'] . $pageConfig['unit'] ?> <?= $pageConfig['marginLeft'] . $pageConfig['unit'] ?>;
                        transition: aspect-ratio 0.25s ease, padding 0.2s ease;
                    }
                    .draggable-block-item {
                        position: absolute;
                        border: 1.5px dashed #18a96a;
                        background: rgba(255, 255, 255, 0.95);
                        cursor: move;
                        user-select: none;
                        padding: 4px;
                        border-radius: 4px;
                        box-sizing: border-box;
                    }
                    .draggable-block-item.selected {
                        outline: 2px solid #0e9b60;
                        outline-offset: 2px;
                        background: #ffffff;
                        box-shadow: 0 4px 16px rgba(14,155,96,0.2);
                    }
                </style>

                <div class="layout-paper code-paper" id="layoutPaper" style="<?= generate_theme_style_attr($templateTheme) ?>">
                    <!-- Top Left Accent Bar -->
                    <div class="ml-top-accent"></div>

                    <!-- 1. Logo Block -->
                    <?php 
                    $p = $layout['block_logo'] ?? $defaultCodeLayout['block_logo'];
                    $iconFile = dirname(__DIR__) . '/' . ltrim($cfg['icon_path'], '/');
                    if (!file_exists($iconFile)) $iconFile = __DIR__ . '/assets/motherland-icon.png';
                    $iconSrc = file_exists($iconFile) ? ('data:image/png;base64,' . base64_encode(file_get_contents($iconFile))) : e($cfg['icon_path']);
                    ?>
                    <div class="draggable-field draggable-block-item" data-field="block_logo" style="left:<?= floatval($p['x']) ?>%;top:<?= floatval($p['y']) ?>%;width:<?= intval($p['width'] ?? 380) ?>px;">
                        <div class="ml-brand">
                            <img src="<?= $iconSrc ?>" alt="Logo" class="ml-brand-icon" style="height:48px;">
                            <div class="ml-brand-text">
                                <div class="ml-hospital-name" style="font-size:24px;"><?= htmlspecialchars($cfg['hospital_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="ml-hospital-tagline" style="font-size:9px;">— <?= htmlspecialchars($cfg['hospital_tagline'], ENT_QUOTES, 'UTF-8') ?> —</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Title Block -->
                    <?php $p = $layout['block_title'] ?? $defaultCodeLayout['block_title']; ?>
                    <div class="draggable-field draggable-block-item" data-field="block_title" style="left:<?= floatval($p['x']) ?>%;top:<?= floatval($p['y']) ?>%;width:<?= intval($p['width'] ?? 720) ?>px;text-align:center;">
                        <h1 class="ml-title" style="font-size:16px;margin:0;"><?= htmlspecialchars($cfg['doc_title'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <div class="ml-divider-rule" style="margin-top:6px;"></div>
                    </div>

                    <!-- 3. Patient Metadata Block -->
                    <?php $p = $layout['block_meta'] ?? $defaultCodeLayout['block_meta']; ?>
                    <div class="draggable-field draggable-block-item" data-field="block_meta" style="left:<?= floatval($p['x']) ?>%;top:<?= floatval($p['y']) ?>%;width:<?= intval($p['width'] ?? 720) ?>px;">
                        <div class="ml-meta-grid" style="font-size:11px;line-height:1.4;">
                            <div class="ml-col-left" style="padding-right:12px;border-right:1px solid #777;">
                                <div class="ml-field-row"><span class="ml-label" style="width:75px;"><?= e($cfg['lbl_uhid']) ?></span><span class="ml-sep">:</span><span class="ml-value">UHID-20260920-0001</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:75px;"><?= e($cfg['lbl_name']) ?></span><span class="ml-sep">:</span><span class="ml-value font-bold">Sample Patient</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:75px;"><?= e($cfg['lbl_age_sex']) ?></span><span class="ml-sep">:</span><span class="ml-value">28 / Female</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:75px;"><?= e($cfg['lbl_guardian']) ?></span><span class="ml-sep">:</span><span class="ml-value">Guardian Name</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:75px;"><?= e($cfg['lbl_contact']) ?></span><span class="ml-sep">:</span><span class="ml-value">+91 98765 43210</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:75px;"><?= e($cfg['lbl_address']) ?></span><span class="ml-sep">:</span><span class="ml-value">Sample Address, City</span></div>
                            </div>
                            <div class="ml-col-right" style="padding-left:14px;">
                                <div class="ml-field-row"><span class="ml-label" style="width:85px;"><?= e($cfg['lbl_bill']) ?></span><span class="ml-sep">:</span><span class="ml-value">BILL-20260920-001</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:85px;"><?= e($cfg['lbl_date']) ?></span><span class="ml-sep">:</span><span class="ml-value"><?= date('d-M-Y') ?></span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:85px;"><?= e($cfg['lbl_panel']) ?></span><span class="ml-sep">:</span><span class="ml-value">CASH</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:85px;"><?= e($cfg['lbl_dept']) ?></span><span class="ml-sep">:</span><span class="ml-value">IVF</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:85px;"><?= e($cfg['lbl_room']) ?></span><span class="ml-sep">:</span><span class="ml-value">102</span></div>
                                <div class="ml-field-row"><span class="ml-label" style="width:85px;"><?= e($cfg['lbl_app']) ?></span><span class="ml-sep">:</span><span class="ml-value">1</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Doctor Banner & Vitals Box -->
                    <?php $p = $layout['block_doctor_vitals'] ?? $defaultCodeLayout['block_doctor_vitals']; ?>
                    <div class="draggable-field draggable-block-item" data-field="block_doctor_vitals" style="left:<?= floatval($p['x']) ?>%;top:<?= floatval($p['y']) ?>%;width:<?= intval($p['width'] ?? 720) ?>px;">
                        <div class="ml-doctor-vitals-box" style="margin:0;font-size:11px;">
                            <div class="ml-doc-header" style="padding:4px 0;"><span class="ml-doctor-name" style="font-size:13px;"><?= htmlspecialchars($cfg['doctor_name'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="ml-vitals-table">
                                <div class="ml-vitals-row ml-vitals-row-1" style="padding:4px 8px;">
                                    <div class="ml-v-cell"><span class="v-lbl"><?= e($cfg['lbl_height']) ?></span><span class="v-unit"><?= e($cfg['unit_height']) ?></span></div>
                                    <div class="ml-v-cell"><span class="v-lbl"><?= e($cfg['lbl_weight']) ?></span><span class="v-unit"><?= e($cfg['unit_weight']) ?></span></div>
                                    <div class="ml-v-cell"><span class="v-lbl"><?= e($cfg['lbl_temp']) ?></span><span class="v-unit"><?= e($cfg['unit_temp']) ?></span></div>
                                    <div class="ml-v-cell"><span class="v-lbl"><?= e($cfg['lbl_pulse']) ?></span><span class="v-unit"><?= e($cfg['unit_pulse']) ?></span></div>
                                </div>
                                <div class="ml-vitals-row ml-vitals-row-2" style="padding:4px 8px;">
                                    <div class="ml-v-cell"><span class="v-lbl"><?= e($cfg['lbl_pain']) ?></span></div>
                                    <div class="ml-v-cell"><span class="v-lbl"><?= e($cfg['lbl_allergies']) ?></span></div>
                                    <div class="ml-v-cell"><span class="v-lbl"><?= e($cfg['lbl_bmi']) ?></span></div>
                                    <div class="ml-v-cell"><span class="v-lbl"><?= e($cfg['lbl_bp']) ?></span><span class="v-unit"><?= e($cfg['unit_bp']) ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Validity Note Block -->
                    <?php $p = $layout['block_validity'] ?? $defaultCodeLayout['block_validity']; ?>
                    <div class="draggable-field draggable-block-item" data-field="block_validity" style="left:<?= floatval($p['x']) ?>%;top:<?= floatval($p['y']) ?>%;width:<?= intval($p['width'] ?? 720) ?>px;">
                        <div class="ml-validity-note" style="font-size:10px;">
                            <strong>Note :</strong> <u><em><?= htmlspecialchars($cfg['validity_note'], ENT_QUOTES, 'UTF-8') ?></em></u>
                        </div>
                    </div>

                    <!-- 6. Footer Block -->
                    <?php $p = $layout['block_footer'] ?? $defaultCodeLayout['block_footer']; ?>
                    <div class="draggable-field draggable-block-item" data-field="block_footer" style="left:<?= floatval($p['x']) ?>%;top:<?= floatval($p['y']) ?>%;width:<?= intval($p['width'] ?? 720) ?>px;">
                        <div class="ml-divider-rule ml-footer-rule" style="margin-bottom:6px;"></div>
                        <div class="ml-footer" style="font-size:9.5px;line-height:1.35;">
                            <div class="ml-footer-col ml-footer-address">
                                <div class="ml-footer-item">
                                    <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)" style="width:14px;height:14px;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
                                    <div class="ml-footer-text">
                                        <div><strong>Hospital.:</strong> <?= e($cfg['hospital_address']) ?></div>
                                        <div class="ml-reg-office"><?= nl2br(e($cfg['reg_office'])) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-footer-col ml-footer-contact" style="border-left:none;padding-left:0;">
                                <div class="ml-footer-item"><svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)" style="width:14px;height:14px;"><path d="M16.75 13.96c.25.13.41.2.46.3.06.11.04.61-.21 1.18-.25.56-1.23 1.1-1.74 1.15-.46.04-1.02.07-2.06-.34-1.49-.59-2.73-1.63-3.69-2.77-.97-1.14-1.72-2.51-1.89-3.08-.18-.58-.02-.9.12-1.17.13-.25.29-.48.44-.65.15-.17.29-.26.39-.26.11 0 .22 0 .32.01.12.01.27-.04.42.33.15.37.52 1.28.57 1.38.05.1.08.22.02.34-.06.12-.13.23-.22.34-.1.1-.2.23-.29.33-.1.1-.21.21-.09.42.12.21.54.89 1.16 1.44.8.71 1.48.93 1.69 1.04.21.11.33.09.45-.05.13-.14.54-.63.69-.85.14-.21.3-.18.5-.1.21.08 1.32.62 1.55.73zM12 2a10 10 0 0 0-8.66 15L2 22l5.17-1.32A10 10 0 1 0 12 2z"/></svg><span><?= e($cfg['phone_whatsapp']) ?></span></div>
                                <div class="ml-footer-item"><svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)" style="width:14px;height:14px;"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-2.2 2.2a15.053 15.053 0 0 1-6.59-6.59l2.2-2.21a.96.96 0 0 0 .25-1.01A11.36 11.36 0 0 1 8.57 3.9c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.52c0-.55-.45-1-.99-1z"/></svg><span><?= e($cfg['phone_landline']) ?></span></div>
                            </div>
                            <div class="ml-footer-col ml-footer-online" style="border-left:none;padding-left:0;">
                                <div class="ml-footer-item"><svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)" style="width:14px;height:14px;"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg><span><?= e($cfg['email']) ?></span></div>
                                <div class="ml-footer-item"><svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)" style="width:14px;height:14px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg><span><?= e($cfg['website']) ?></span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    window.EDITOR_DATA = {
                        templateId: <?= $tpl['id'] ?>,
                        csrf: '<?= csrf_token() ?>',
                        defaultLayout: <?= json_encode($defaultCodeLayout) ?>,
                        pageConfig: <?= json_encode($pageConfig) ?>,
                        paperPresets: <?= json_encode($paperPresets) ?>,
                        themePresets: <?= json_encode($themePresets) ?>,
                        templateTheme: <?= json_encode($templateTheme) ?>,
                        themePreset: '<?= $activeThemeKey ?>'
                    };
                </script>
                <script src="assets/js/template-editor.js"></script>

            <?php else: ?>
                <!-- Image Template Canvas -->
                <div class="layout-paper" id="layoutPaper" style="background-image:url('<?= e($tpl['file_path']) ?>');aspect-ratio:<?= $pageConfig['width'] ?>/<?= $pageConfig['height'] ?>;padding:<?= $pageConfig['marginTop'] . $pageConfig['unit'] ?> <?= $pageConfig['marginRight'] . $pageConfig['unit'] ?> <?= $pageConfig['marginBottom'] . $pageConfig['unit'] ?> <?= $pageConfig['marginLeft'] . $pageConfig['unit'] ?>;">
                    <?php foreach ($layout as $k => $pos): if (!isset(FIELD_DEFS[$k]) || empty($perm[$k]['visible'])) continue; ?>
                        <div class="draggable-field" data-field="<?= e($k) ?>" style="left:<?= floatval($pos['x'] ?? 5) ?>%;top:<?= floatval($pos['y'] ?? 5) ?>%;font-size:<?= intval($pos['fontSize'] ?? 12) ?>px;width:<?= intval($pos['width'] ?? 220) ?>px;font-weight:<?= e($pos['fontWeight'] ?? '500') ?>">
                            <?= e(FIELD_DEFS[$k]) ?> <span>Sample</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <script>
                    window.EDITOR_DATA = {
                        templateId: <?= $tpl['id'] ?>,
                        csrf: '<?= csrf_token() ?>',
                        defaultLayout: <?= json_encode(json_decode($tpl['default_layout_json'] ?: '{}', true)) ?>,
                        pageConfig: <?= json_encode($pageConfig) ?>,
                        paperPresets: <?= json_encode($paperPresets) ?>
                    };
                </script>
                <script src="assets/js/template-editor.js"></script>
            <?php endif; ?>
        </section>
    </div>
<?php else: ?>
    <div class="card empty">No active template found. Ask an admin to activate or configure a template.</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
