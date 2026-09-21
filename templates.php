<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_admin();
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/code_template.php';
require_once __DIR__ . '/includes/page_engine.php';

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();

    // 1. Toggle template status (Active / Blocked)
    if (isset($_POST['toggle_id'])) {
        $id = (int)$_POST['toggle_id'];
        $st = db()->prepare('UPDATE templates SET active = 1 - active WHERE id = ?');
        $st->execute([$id]);
        flash('success', 'Template status updated.');
        header('Location: templates.php');
        exit;
    }

    // 2. Set default template
    if (isset($_POST['set_default_id'])) {
        $defId = (int)$_POST['set_default_id'];
        set_setting('default_template_id', (string)$defId);
        flash('success', 'Default template set successfully.');
        header('Location: templates.php');
        exit;
    }

    // 3. Save Code Template settings
    if (isset($_POST['save_code_template'])) {
        $defs = get_motherland_defaults();
        $configData = [];
        foreach ($defs as $key => $defaultVal) {
            if (isset($_POST[$key])) {
                $configData[$key] = trim((string)$_POST[$key]);
            }
        }

        // Opacity normalization: if submitted as percentage (e.g. 6 or 10), convert to decimal (0.06 or 0.10)
        if (isset($configData['watermark_opacity'])) {
            $opVal = (float)$configData['watermark_opacity'];
            if ($opVal > 1.0) {
                $configData['watermark_opacity'] = (string)round($opVal / 100, 2);
            }
        }

        // Explicitly handle all checkbox toggles (unchecked checkboxes are not sent in POST)
        $checkboxKeys = [
            'show_watermark', 'enable_two_pages', 'show_header', 'show_title',
            'show_patient_info', 'show_doctor_box', 'show_vitals', 'show_validity_note', 'show_footer',
            'show_divider_lines',
            'show_uhid', 'show_name', 'show_age_sex', 'show_guardian', 'show_contact', 'show_address',
            'show_bill', 'show_date', 'show_panel', 'show_dept', 'show_room', 'show_app',
            'show_vital_height', 'show_vital_weight', 'show_vital_temp', 'show_vital_pulse',
            'show_vital_pain', 'show_vital_allergies', 'show_vital_bmi', 'show_vital_bp'
        ];
        foreach ($checkboxKeys as $ck) {
            $configData[$ck] = isset($_POST[$ck]) ? '1' : '0';
        }

        // Handle custom icon upload if provided
        if (!empty($_FILES['custom_icon']['name']) && $_FILES['custom_icon']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['custom_icon'];
            if ($f['size'] <= 5 * 1024 * 1024) {
                $info = @getimagesize($f['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if ($info && isset($allowed[$info['mime']])) {
                    $iconDir = __DIR__ . '/uploads/templates';
                    if (!is_dir($iconDir)) @mkdir($iconDir, 0777, true);
                    $filename = 'icon_' . bin2hex(random_bytes(8)) . '.' . $allowed[$info['mime']];
                    $targetPath = $iconDir . '/' . $filename;
                    if (move_uploaded_file($f['tmp_name'], $targetPath)) {
                        $configData['icon_path'] = 'uploads/templates/' . $filename;
                    }
                }
            }
        } elseif (isset($_POST['reset_icon']) && $_POST['reset_icon'] === '1') {
            $configData['icon_path'] = 'assets/motherland-icon.png';
        }

        save_motherland_config($configData);

        // Save Universal Page Settings
        if (isset($_POST['page_size'])) {
            $presets = get_paper_presets();
            $pageSize = trim((string)$_POST['page_size']);
            if (!isset($presets[$pageSize])) $pageSize = 'A4';
            $orientation = strtolower(trim((string)($_POST['orientation'] ?? 'portrait')));
            if ($orientation !== 'landscape') $orientation = 'portrait';
            $unit = strtolower(trim((string)($_POST['page_unit'] ?? 'mm')));
            if (!in_array($unit, ['mm', 'cm', 'in', 'inch'], true)) $unit = 'mm';

            if ($pageSize !== 'Custom' && isset($presets[$pageSize])) {
                $pageWidth = (float)$presets[$pageSize]['width'];
                $pageHeight = (float)$presets[$pageSize]['height'];
                $pageUnit = $presets[$pageSize]['unit'];
            } else {
                $pageWidth = max(50.0, min(1000.0, (float)($_POST['page_width'] ?? 210.0)));
                $pageHeight = max(50.0, min(1500.0, (float)($_POST['page_height'] ?? 297.0)));
                $pageUnit = $unit;
            }

            $marginTop = max(0.0, min(100.0, (float)($_POST['margin_top'] ?? 6.0)));
            $marginRight = max(0.0, min(100.0, (float)($_POST['margin_right'] ?? 12.0)));
            $marginBottom = max(0.0, min(100.0, (float)($_POST['margin_bottom'] ?? 6.0)));
            $marginLeft = max(0.0, min(100.0, (float)($_POST['margin_left'] ?? 12.0)));

            $pageConfigJson = json_encode([
                'pageSize' => $pageSize,
                'orientation' => $orientation,
                'width' => $pageWidth,
                'height' => $pageHeight,
                'unit' => $pageUnit,
                'marginTop' => $marginTop,
                'marginRight' => $marginRight,
                'marginBottom' => $marginBottom,
                'marginLeft' => $marginLeft,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            ensure_template_page_columns();
            $targetTplId = (int)($_POST['template_id'] ?? 0);
            if ($targetTplId > 0) {
                $upSt = db()->prepare('UPDATE templates SET 
                    page_size = ?, orientation = ?, page_width = ?, page_height = ?, page_unit = ?,
                    margin_top = ?, margin_right = ?, margin_bottom = ?, margin_left = ?, page_config_json = ?
                    WHERE id = ?');
                $upSt->execute([$pageSize, $orientation, $pageWidth, $pageHeight, $pageUnit, $marginTop, $marginRight, $marginBottom, $marginLeft, $pageConfigJson, $targetTplId]);
            }
        }

        flash('success', 'Motherland template and page size settings saved successfully.');
        header('Location: templates.php');
        exit;
    }

    // 4. Existing Upload Image Template form (preserved 100%)
    $name = trim($_POST['name'] ?? '');
    $f = $_FILES['template_file'] ?? null;
    if (!$name || !$f || $f['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Name and image file are required.');
        header('Location: templates.php');
        exit;
    }
    if ($f['size'] > 8 * 1024 * 1024) {
        flash('error', 'Template must be under 8 MB.');
        header('Location: templates.php');
        exit;
    }
    $info = @getimagesize($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($allowed[$info['mime']])) {
        flash('error', 'Use JPG, PNG or WEBP template images.');
        header('Location: templates.php');
        exit;
    }
    $file = 'uploads/templates/' . bin2hex(random_bytes(12)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/' . $file)) {
        flash('error', 'Upload failed.');
        header('Location: templates.php');
        exit;
    }
    $default = [];
    $i = 0;
    foreach (FIELD_DEFS as $k => $label) {
        $default[$k] = ['x' => 7 + ($i % 2) * 48, 'y' => 10 + floor($i / 2) * 5, 'fontSize' => 12, 'width' => 210, 'fontWeight' => '500'];
        $i++;
    }
    $st = db()->prepare('INSERT INTO templates(name,file_path,original_name,mime_type,width,height,default_layout_json,template_type,created_by) VALUES(?,?,?,?,?,?,?,?,?)');
    $st->execute([$name, $file, $f['name'], $info['mime'], $info[0], $info[1], json_encode($default), 'image', $user['id']]);
    flash('success', 'Template uploaded. Open Layout Editor to position fields.');
    header('Location: templates.php');
    exit;
}

// Fetch templates
$allTemplates = db()->query('SELECT t.*, u.name creator FROM templates t LEFT JOIN users u ON u.id = t.created_by ORDER BY t.id DESC')->fetchAll();

$codeTemplates = array_filter($allTemplates, fn($t) => is_code_template($t));
$imageTemplates = array_filter($allTemplates, fn($t) => !is_code_template($t));
$defaultTemplateId = (int)setting('default_template_id', '0');

$cfg = get_motherland_config();
$motherlandTpl = reset($codeTemplates) ?: null;
$pageConfig = $motherlandTpl ? get_template_page_config($motherlandTpl) : null;
$paperPresets = get_paper_presets();
$defaultPrintPages = $cfg['default_print_pages'] ?? '1';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title">
    <div>
        <h2>OPD Templates Management</h2>
        <p>Manage code-based vector templates or upload scanned image templates with drag-and-drop field placement.</p>
    </div>
</div>

<!-- ========================================================= -->
<!-- 1. DEDICATED SECTION: CODE-BASED TEMPLATES (SEPARATE)    -->
<!-- ========================================================= -->
<?php
$wmOpacityVal = (float)($cfg['watermark_opacity'] ?? 0.06);
$wmOpacityPct = (int)round($wmOpacityVal <= 1.0 ? $wmOpacityVal * 100 : $wmOpacityVal);
$wmSizeVal = (int)($cfg['watermark_size'] ?? 105);
if ($wmSizeVal < 40) $wmSizeVal = 105;
$accentHeightVal = (int)($cfg['accent_height'] ?? 28);
if ($accentHeightVal < 15) $accentHeightVal = 28;
$defaultPrintPages = (string)($cfg['default_print_pages'] ?? '1');
?>

<style>
/* Scoped Styling for OPD Code Template Management */
.code-template-section {
    margin-bottom: 28px;
    border: 2px solid #bce8cf;
    background: #fafdfc;
    border-radius: 5px;
    padding: 24px;
    box-shadow: 0 10px 32px rgba(18, 53, 43, 0.05);
}

.tpl-header-card {
    background: #ffffff;
    border: 1px solid #d8ede3;
    border-radius: 5px;
    padding: 20px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 18px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.02);
}

.tpl-card {
    background: #ffffff;
    border: 1px solid #dcece5;
    border-radius: 5px;
    padding: 22px;
    margin-bottom: 20px;
    box-shadow: 0 4px 18px rgba(18, 53, 43, 0.03);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.tpl-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid #edf5f1;
}

.tpl-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 800;
    color: #0c3e2e;
    margin: 0;
}

.tpl-badge-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 3px;
    background: #e4f7ee;
    color: #12794c;
    font-size: 13px;
    font-weight: 800;
}

.tpl-badge-pill {
    font-size: 11px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 3px;
    letter-spacing: 0.3px;
}

/* Custom Styled Toggle Switches */
.switch-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f7fcfa;
    border: 1px solid #e1eee8;
    border-radius: 4px;
    padding: 12px 14px;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease;
}

.switch-label:hover {
    background: #eef8f4;
    border-color: #bce3d2;
}

.switch-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #18a96a;
    cursor: pointer;
}

/* Radio Cards for 1-Page vs 2-Page */
.page-mode-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.page-mode-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #fbfdfc;
    border: 2px solid #dcece5;
    border-radius: 4px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.page-mode-card:hover {
    border-color: #18a96a;
    background: #f3fbf7;
}

.page-mode-card input[type="radio"]:checked + .page-mode-content {
    color: #0c3e2e;
}

.page-mode-card.is-selected {
    border-color: #18a96a;
    background: #eef9f4;
    box-shadow: 0 4px 14px rgba(24, 169, 106, 0.12);
}

.input-num-group {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1px solid #cce2d8;
    border-radius: 4px;
    overflow: hidden;
}

.input-num-group input[type="number"] {
    border: 0;
    padding: 10px 12px;
    font-weight: 700;
    font-size: 15px;
    color: #11382b;
    outline: none;
}

.input-num-group .unit-tag {
    padding: 0 12px;
    background: #f0f7f4;
    color: #436b5e;
    font-weight: 700;
    font-size: 13px;
    border-left: 1px solid #dceee7;
    height: 100%;
    display: flex;
    align-items: center;
}

.slider-sync-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 6px;
}

.slider-sync-wrap input[type="range"] {
    flex: 1;
    height: 6px;
    accent-color: #18a96a;
    cursor: pointer;
}

.sticky-action-bar {
    position: sticky;
    bottom: 16px;
    z-index: 10;
    background: #ffffff;
    border: 2px solid #bce8cf;
    border-radius: 5px;
    padding: 14px 20px;
    box-shadow: 0 12px 36px rgba(12, 53, 38, 0.18);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
}

.code-template-section .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.code-template-section .span-2 {
    grid-column: 1 / -1;
}

.code-template-section label {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 13px;
    font-weight: 700;
    color: #204036;
}

.code-template-section input[type="text"],
.code-template-section input[type="tel"],
.code-template-section input[type="email"],
.code-template-section select,
.code-template-section textarea {
    width: 100%;
    border: 1.5px solid #cce2d8;
    background: #ffffff;
    color: #11382b;
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 13.5px;
    box-sizing: border-box;
    font-family: inherit;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.code-template-section input:focus,
.code-template-section select:focus,
.code-template-section textarea:focus {
    border-color: #18a96a;
    box-shadow: 0 0 0 3px rgba(24, 169, 106, 0.12);
}

@media (max-width: 800px) {
    .code-template-section .form-grid,
    .page-mode-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="card code-template-section">
    <div class="card-head" style="margin-bottom: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <h2 style="margin: 0; color: #0d4a34;">Motherland OPD Code Template (HTML & CSS)</h2>
                <span style="background: #18a96a; color: #fff; font-size: 11px; font-weight: 800; padding: 3px 9px; border-radius: 6px; letter-spacing: 0.5px;">ACTIVE</span>
            </div>
            <p>Vector typographic template with zero overlapping text, customizable watermark, 2-page continuation sheet support, and independent section hide/show toggles.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <?php if ($motherlandTpl): ?>
                <a class="btn btn-soft" href="print_opd.php?template_id=<?= $motherlandTpl['id'] ?>&pages=1">Preview 1-Page</a>
                <a class="btn btn-soft" href="print_opd.php?template_id=<?= $motherlandTpl['id'] ?>&pages=2">Preview 2-Pages</a>
                <a class="btn btn-soft" href="template_editor.php?template_id=<?= $motherlandTpl['id'] ?>">Visual Drag Editor</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($motherlandTpl): ?>
        <!-- Top Status & Actions Card -->
        <div class="tpl-header-card">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 54px; height: 54px; border-radius: 4px; background: #e8f8ef; display: grid; place-items: center; border: 1px solid #bce8cf; overflow: hidden; padding: 4px;">
                    <img src="<?= e($cfg['icon_path']) ?>" alt="Icon" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <strong style="font-size: 16px; color: #111;"><?= e($motherlandTpl['name']) ?></strong>
                        <?php if ($defaultTemplateId === (int)$motherlandTpl['id']): ?>
                            <span style="background: #e1f5fe; color: #0277bd; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 3px;">Current Default</span>
                        <?php endif; ?>
                        <span style="background: #e8f8ef; color: #18a96a; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 3px;">A4 Vector Form</span>
                    </div>
                    <small style="color: #6a837c; display: block; margin-top: 4px;">
                        <?= e($cfg['hospital_name']) ?> (<?= e($cfg['hospital_tagline']) ?>) · Doctor: <?= e($cfg['doctor_name']) ?> · <?= $motherlandTpl['active'] ? '<span style="color:#18a96a;font-weight:700;">Active</span>' : '<span style="color:#c62828;font-weight:700;">Blocked</span>' ?>
                    </small>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="set_default_id" value="<?= $motherlandTpl['id'] ?>">
                    <button class="btn btn-soft" <?= $defaultTemplateId === (int)$motherlandTpl['id'] ? 'disabled style="opacity:0.6;"' : '' ?>>
                        <?= $defaultTemplateId === (int)$motherlandTpl['id'] ? 'Default Template' : 'Set as Default' ?>
                    </button>
                </form>

                <form method="post" style="display: inline;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="toggle_id" value="<?= $motherlandTpl['id'] ?>">
                    <button class="mini" style="padding: 10px 14px;">
                        <?= $motherlandTpl['active'] ? 'Block Template' : 'Unblock Template' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- MAIN SETTINGS FORM (ORGANIZED INTO INTUITIVE CARDS) -->
        <form method="post" enctype="multipart/form-data" id="motherlandConfigForm">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="save_code_template" value="1">
            <input type="hidden" name="template_id" value="<?= $motherlandTpl['id'] ?>">

            <!-- CARD 0: PAPER SIZE & ORIENTATION SETTINGS -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">📄</span>
                        <span>Paper Size & Page Configuration (Universal Print Engine)</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#e8f8ef; color:#18a96a;">
                        <?= e($pageConfig['label'] ?? 'A4 Portrait') ?>
                    </span>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px;">
                    <div>
                        <label>
                            Paper Size Preset
                            <select name="page_size" id="tplPageSizeSelect" style="width:100%;margin-top:4px;" onchange="toggleTplCustomDims(this.value)">
                                <?php foreach ($paperPresets as $pk => $pv): ?>
                                    <option value="<?= $pk ?>" <?= ($pageConfig && $pageConfig['pageSize'] === $pk) ? 'selected' : '' ?> data-w="<?= $pv['width'] ?>" data-h="<?= $pv['height'] ?>" data-u="<?= $pv['unit'] ?>">
                                        <?= $pv['name'] ?> (<?= $pv['desc'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div>
                        <label>
                            Orientation
                            <select name="orientation" id="tplOrientationSelect" style="width:100%;margin-top:4px;">
                                <option value="portrait" <?= ($pageConfig && $pageConfig['orientation'] === 'portrait') ? 'selected' : '' ?>>↕ Portrait (Vertical)</option>
                                <option value="landscape" <?= ($pageConfig && $pageConfig['orientation'] === 'landscape') ? 'selected' : '' ?>>↔ Landscape (Horizontal)</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div id="tplCustomDimsRow" style="display:<?= ($pageConfig && $pageConfig['pageSize'] === 'Custom') ? 'grid' : 'none' ?>; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px; background:#f8fafc; padding:12px; border-radius:6px; border:1px solid #e2e8f0;">
                    <div>
                        <label>
                            Custom Width
                            <input type="number" step="0.1" name="page_width" value="<?= $pageConfig['baseWidth'] ?? 210 ?>">
                        </label>
                    </div>
                    <div>
                        <label>
                            Custom Height
                            <input type="number" step="0.1" name="page_height" value="<?= $pageConfig['baseHeight'] ?? 297 ?>">
                        </label>
                    </div>
                    <div>
                        <label>
                            Unit
                            <select name="page_unit">
                                <option value="mm" <?= ($pageConfig && $pageConfig['unit'] === 'mm') ? 'selected' : '' ?>>mm (Millimeter)</option>
                                <option value="cm" <?= ($pageConfig && $pageConfig['unit'] === 'cm') ? 'selected' : '' ?>>cm (Centimeter)</option>
                                <option value="in" <?= ($pageConfig && $pageConfig['unit'] === 'in') ? 'selected' : '' ?>>in (Inch)</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div>
                    <label style="font-weight:700; color:#204036; margin-bottom:6px; display:block;">
                        Page Margins (<?= e($pageConfig['unit'] ?? 'mm') ?>)
                    </label>
                    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                        <div>
                            <label style="font-size:12px;color:#555;">Top</label>
                            <input type="number" step="0.5" name="margin_top" value="<?= $pageConfig['marginTop'] ?? 6 ?>">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#555;">Right</label>
                            <input type="number" step="0.5" name="margin_right" value="<?= $pageConfig['marginRight'] ?? 12 ?>">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#555;">Bottom</label>
                            <input type="number" step="0.5" name="margin_bottom" value="<?= $pageConfig['marginBottom'] ?? 6 ?>">
                        </div>
                        <div>
                            <label style="font-size:12px;color:#555;">Left</label>
                            <input type="number" step="0.5" name="margin_left" value="<?= $pageConfig['marginLeft'] ?? 12 ?>">
                        </div>
                    </div>
                </div>
            </div>

            <script>
            function toggleTplCustomDims(val) {
                const row = document.getElementById('tplCustomDimsRow');
                if (row) row.style.display = (val === 'Custom') ? 'grid' : 'none';
            }
            </script>

            <!-- CARD 1: PRINT PAGES MODE -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">1</span>
                        <span>Print Pages Mode (Kitne Page Print Hone Chahiye)</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#e3f2fd; color:#0d47a1;">PAGE COUNT</span>
                </div>
                <div class="page-mode-grid">
                    <label class="page-mode-card <?= $defaultPrintPages === '1' ? 'is-selected' : '' ?>">
                        <input type="radio" name="default_print_pages" value="1" <?= $defaultPrintPages === '1' ? 'checked' : '' ?> onchange="document.querySelectorAll('.page-mode-card').forEach(el=>el.classList.remove('is-selected')); this.closest('.page-mode-card').classList.add('is-selected');" style="width:18px;height:18px;margin-top:2px;">
                        <div class="page-mode-content">
                            <strong style="display: block; font-size: 14px; color: #0c3e2e;">1 Page (Standard OPD Slip)</strong>
                            <small style="display: block; color: #6a837c; margin-top: 4px; line-height: 1.35;">Prints a complete single-page prescription with header, patient information, clinical vitals, prescription area, validity note, and footer.</small>
                        </div>
                    </label>

                    <label class="page-mode-card <?= $defaultPrintPages === '2' ? 'is-selected' : '' ?>">
                        <input type="radio" name="default_print_pages" value="2" <?= $defaultPrintPages === '2' ? 'checked' : '' ?> onchange="document.querySelectorAll('.page-mode-card').forEach(el=>el.classList.remove('is-selected')); this.closest('.page-mode-card').classList.add('is-selected');" style="width:18px;height:18px;margin-top:2px;">
                        <div class="page-mode-content">
                            <strong style="display: block; font-size: 14px; color: #0c3e2e;">2 Pages (Continuation Sheet Mode)</strong>
                            <small style="display: block; color: #6a837c; margin-top: 4px; line-height: 1.35;">Prints Page 1 (Full OPD Slip) + Page 2 (Continuation sheet with ONLY Header & Footer, without repeating patient details, and a full blank writing area).</small>
                        </div>
                    </label>
                </div>
            </div>

            <!-- CARD 2: SECTION VISIBILITY (SHOW / HIDE) -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">2</span>
                        <span>Section & Feature Visibility (Show / Hide Toggles)</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#e8f8ef; color:#18a96a;">8 SECTIONS</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px;">
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">Header & Logo Block</span>
                        <input type="checkbox" name="show_header" value="1" <?= !empty($cfg['show_header']) ? 'checked' : '' ?>>
                    </label>
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">Consultation Paper Title</span>
                        <input type="checkbox" name="show_title" value="1" <?= !empty($cfg['show_title']) ? 'checked' : '' ?>>
                    </label>
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">Patient Details Table</span>
                        <input type="checkbox" name="show_patient_info" value="1" <?= !empty($cfg['show_patient_info']) ? 'checked' : '' ?>>
                    </label>
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">Doctor Name Banner</span>
                        <input type="checkbox" name="show_doctor_box" value="1" <?= !empty($cfg['show_doctor_box']) ? 'checked' : '' ?>>
                    </label>
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">Vitals Measurements Box</span>
                        <input type="checkbox" name="show_vitals" value="1" <?= !empty($cfg['show_vitals']) ? 'checked' : '' ?>>
                    </label>
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">Validity Note Notice</span>
                        <input type="checkbox" name="show_validity_note" value="1" <?= !empty($cfg['show_validity_note']) ? 'checked' : '' ?>>
                    </label>
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">3-Column Footer Contacts</span>
                        <input type="checkbox" name="show_footer" value="1" <?= !empty($cfg['show_footer']) ? 'checked' : '' ?>>
                    </label>
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">Hospital Watermark</span>
                        <input type="checkbox" name="show_watermark" value="1" <?= !empty($cfg['show_watermark']) ? 'checked' : '' ?>>
                    </label>
                    <label class="switch-label">
                        <span style="font-weight: 700; color: #11382b; font-size: 13.5px;">Divider Lines (Borders)</span>
                        <input type="checkbox" name="show_divider_lines" value="1" <?= !empty($cfg['show_divider_lines']) ? 'checked' : '' ?>>
                    </label>
                </div>
            </div>

            <!-- CARD 3: WATERMARK ENGINE -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">3</span>
                        <span>Premium Watermark Engine (Position, Size & Opacity)</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#ede7f6; color:#512da8;">WATERMARK CONTROLS</span>
                </div>
                <div class="form-grid">
                    <!-- Position -->
                    <label>
                        Watermark Position / Alignment
                        <select name="watermark_position" style="padding: 9px 12px; border-radius: 4px; border: 1px solid #cce2d8; font-weight: 600;">
                            <option value="bottom-right" <?= ($cfg['watermark_position'] ?? '') === 'bottom-right' ? 'selected' : '' ?>>Bottom-Right (Niche Right Corner - Authentic)</option>
                            <option value="center" <?= ($cfg['watermark_position'] ?? '') === 'center' ? 'selected' : '' ?>>Center (Page ke Center mein - Grand Luxury Seal)</option>
                            <option value="bottom-center" <?= ($cfg['watermark_position'] ?? '') === 'bottom-center' ? 'selected' : '' ?>>Bottom-Center (Niche Center)</option>
                            <option value="bottom-left" <?= ($cfg['watermark_position'] ?? '') === 'bottom-left' ? 'selected' : '' ?>>Bottom-Left (Niche Left Corner)</option>
                            <option value="top-right" <?= ($cfg['watermark_position'] ?? '') === 'top-right' ? 'selected' : '' ?>>Top-Right (Upar Right Corner)</option>
                        </select>
                        <small style="color: #6a837c;">Choose where the watermark emblem appears on the consultation sheet.</small>
                    </label>

                    <!-- Watermark Size (Number + Slider) -->
                    <label>
                        Watermark Size (Bada / Chota)
                        <div class="input-num-group">
                            <input type="number" name="watermark_size" id="wm_size_input" min="40" max="200" step="1" value="<?= $wmSizeVal ?>">
                            <span class="unit-tag">mm</span>
                        </div>
                        <div class="slider-sync-wrap">
                            <input type="range" id="wm_size_slider" min="40" max="200" step="1" value="<?= $wmSizeVal ?>">
                            <span id="wm_size_badge" style="font-weight: 700; font-size: 12px; color: #0d4a34; width: 130px;">
                                <?= $wmSizeVal ?> mm (<?= $wmSizeVal < 85 ? 'Small' : ($wmSizeVal > 130 ? 'Large' : 'Standard') ?>)
                            </span>
                        </div>
                        <small style="color: #6a837c;">105mm is standard half-page width. 140mm-170mm gives an extra large seal.</small>
                    </label>

                    <!-- Watermark Opacity (Number + Slider) -->
                    <label>
                        Watermark Opacity (Faintness: 2% - 30%)
                        <div class="input-num-group">
                            <input type="number" name="watermark_opacity" id="wm_opacity_input" min="2" max="30" step="1" value="<?= $wmOpacityPct ?>">
                            <span class="unit-tag">%</span>
                        </div>
                        <div class="slider-sync-wrap">
                            <input type="range" id="wm_opacity_slider" min="2" max="30" step="1" value="<?= $wmOpacityPct ?>">
                            <span id="wm_opacity_badge" style="font-weight: 700; font-size: 12px; color: #0d4a34; width: 60px;"><?= $wmOpacityPct ?>%</span>
                        </div>
                        <small style="color: #6a837c;">6% gives an authentic medical stationery look without obstructing doctor handwriting.</small>
                    </label>

                    <!-- Emblem Status -->
                    <label>
                        Watermark Graphic Emblem
                        <div style="display: flex; align-items: center; gap: 12px; padding: 10px; background: #f7fcfa; border: 1px solid #dcece5; border-radius: 4px;">
                            <img src="<?= e($cfg['icon_path']) ?>" alt="Watermark Graphic" style="width: 38px; height: 38px; object-fit: contain;">
                            <div>
                                <strong style="font-size: 13px; color: #11382b; display: block;">Active Hospital Emblem</strong>
                                <small style="color: #6a837c;">Watermark automatically uses the sharp emblem icon uploaded in Section 4.</small>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- CARD 4: HEADER, LOGO & TOP ACCENT -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">4</span>
                        <span>Header, Logo & Branding (Hospital Name, Tagline & Emblem)</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#e0f2f1; color:#00695c;">BRANDING</span>
                </div>
                <div class="form-grid">
                    <label>
                        Hospital Name (Text Beside Logo)
                        <input type="text" name="hospital_name" value="<?= e($cfg['hospital_name']) ?>" required placeholder="e.g. Motherland">
                    </label>

                    <label>
                        Hospital Subtitle / Tagline
                        <input type="text" name="hospital_tagline" value="<?= e($cfg['hospital_tagline']) ?>" placeholder="e.g. HOSPITAL">
                    </label>

                    <label class="span-2">
                        Document Main Title (Consultation Section Header)
                        <input type="text" name="doc_title" value="<?= e($cfg['doc_title']) ?>" placeholder="e.g. Consultation Paper(OPD)">
                        <small style="color: #6a837c;">Displayed prominently above the patient registration table on Page 1 (omitted on Page 2 continuation sheet).</small>
                    </label>

                    <label>
                        Top Left Accent Bar Color
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" id="accent_color_picker" name="accent_color" value="<?= e($cfg['accent_color']) ?>" style="width: 54px; height: 44px; padding: 3px; cursor: pointer; border-radius: 4px;">
                            <input type="text" id="accent_color_hex" value="<?= e($cfg['accent_color']) ?>" style="flex: 1; font-weight: 700;">
                        </div>
                    </label>

                    <label>
                        Accent Bar Height (Top Left Vertical Tab)
                        <div class="input-num-group">
                            <input type="number" name="accent_height" id="acc_height_input" min="15" max="60" step="1" value="<?= $accentHeightVal ?>">
                            <span class="unit-tag">mm</span>
                        </div>
                        <div class="slider-sync-wrap">
                            <input type="range" id="acc_height_slider" min="15" max="60" step="1" value="<?= $accentHeightVal ?>">
                            <span id="acc_height_badge" style="font-weight: 700; font-size: 12px; color: #0d4a34; width: 60px;"><?= $accentHeightVal ?> mm</span>
                        </div>
                        <small style="color: #6a837c;">Standard is 28mm (aligns exactly with the top header block).</small>
                    </label>

                    <label class="span-2">
                        Hospital Emblem Icon (Upload ONLY the Logo Emblem!)
                        <div style="display: flex; align-items: center; gap: 16px; background: #fbfdfc; padding: 14px; border: 1px solid #dcece5; border-radius: 4px;">
                            <img src="<?= e($cfg['icon_path']) ?>" alt="Current Icon" style="width: 52px; height: 52px; object-fit: contain; background: #fff; padding: 4px; border-radius: 4px; border: 1px solid #cce2d8;">
                            <div style="flex: 1;">
                                <input type="file" name="custom_icon" accept="image/png,image/jpeg,image/webp">
                                <small style="color: #6a837c; display: block; margin-top: 5px;">
                                    Upload only the hospital emblem icon (PNG transparent recommended). The name and tagline are rendered next to it in sharp vector text.
                                </small>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- CARD 5: DOCTOR & DEPARTMENT -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">5</span>
                        <span>Doctor & Clinical Department</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#fff3e0; color:#e65100;">CLINICAL INFO</span>
                </div>
                <div class="form-grid">
                    <label>
                        Doctor Name (Banner Header)
                        <input type="text" name="doctor_name" value="<?= e($cfg['doctor_name']) ?>" required placeholder="e.g. Dr. ANVITI SARAF">
                    </label>

                    <label>
                        Default Doctor Department
                        <input type="text" name="doctor_dept" value="<?= e($cfg['doctor_dept']) ?>" placeholder="e.g. IVF / Cardiology / Gynecology">
                    </label>
                </div>
            </div>

            <!-- CARD 6: PATIENT INFORMATION FIELDS & LABELS -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">6</span>
                        <span>Patient Information Fields & Labels (मरीज़ विवरण फ़ील्ड्स)</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#e8f5e9; color:#2e7d32;">12 FIELDS</span>
                </div>
                <p style="color: #6a837c; font-size: 13px; margin: 0 0 16px;">Two-column patient registration layout matching the physical OPD form. You can customize labels and toggle individual fields on or off.</p>

                <div class="grid-2" style="gap: 20px;">
                    <!-- Left Column Fields -->
                    <div style="background: #fbfdfc; border: 1px solid #dcece5; border-radius: 4px; padding: 14px;">
                        <h4 style="margin: 0 0 12px; font-size: 13px; color: #0d4a34; text-transform: uppercase; letter-spacing: 0.5px;">Left Column Fields</h4>
                        
                        <div class="stack" style="gap: 10px;">
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">UHID Label <input type="text" name="lbl_uhid" value="<?= e($cfg['lbl_uhid']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_uhid" value="1" <?= !empty($cfg['show_uhid']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Patient Name Label <input type="text" name="lbl_name" value="<?= e($cfg['lbl_name']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_name" value="1" <?= !empty($cfg['show_name']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Age / Sex Label <input type="text" name="lbl_age_sex" value="<?= e($cfg['lbl_age_sex']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_age_sex" value="1" <?= !empty($cfg['show_age_sex']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Guardian Label <input type="text" name="lbl_guardian" value="<?= e($cfg['lbl_guardian']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_guardian" value="1" <?= !empty($cfg['show_guardian']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Contact No. Label <input type="text" name="lbl_contact" value="<?= e($cfg['lbl_contact']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_contact" value="1" <?= !empty($cfg['show_contact']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Address Label <input type="text" name="lbl_address" value="<?= e($cfg['lbl_address']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_address" value="1" <?= !empty($cfg['show_address']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column Fields -->
                    <div style="background: #fbfdfc; border: 1px solid #dcece5; border-radius: 4px; padding: 14px;">
                        <h4 style="margin: 0 0 12px; font-size: 13px; color: #0d4a34; text-transform: uppercase; letter-spacing: 0.5px;">Right Column Fields</h4>

                        <div class="stack" style="gap: 10px;">
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Bill No. Label <input type="text" name="lbl_bill" value="<?= e($cfg['lbl_bill']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_bill" value="1" <?= !empty($cfg['show_bill']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Date Label <input type="text" name="lbl_date" value="<?= e($cfg['lbl_date']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_date" value="1" <?= !empty($cfg['show_date']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Panel Label <input type="text" name="lbl_panel" value="<?= e($cfg['lbl_panel']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_panel" value="1" <?= !empty($cfg['show_panel']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Doctor Dept Label <input type="text" name="lbl_dept" value="<?= e($cfg['lbl_dept']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_dept" value="1" <?= !empty($cfg['show_dept']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">Room No Label <input type="text" name="lbl_room" value="<?= e($cfg['lbl_room']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_room" value="1" <?= !empty($cfg['show_room']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                                <label style="margin: 0; font-size: 12px;">App No Label <input type="text" name="lbl_app" value="<?= e($cfg['lbl_app']) ?>"></label>
                                <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_app" value="1" <?= !empty($cfg['show_app']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 7: VITALS & CLINICAL MEASUREMENTS -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">7</span>
                        <span>Vitals Table Labels, Units & Toggles (वाइटल्स एवं इकाइयां)</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#e1f5fe; color:#0277bd;">8 VITALS</span>
                </div>
                <div class="form-grid">
                    <div style="display: grid; grid-template-columns: 2fr 1.2fr auto; gap: 10px; align-items: center;">
                        <label style="margin: 0; font-size: 12px;">Height Label <input type="text" name="lbl_height" value="<?= e($cfg['lbl_height']) ?>"></label>
                        <label style="margin: 0; font-size: 12px;">Unit <input type="text" name="unit_height" value="<?= e($cfg['unit_height']) ?>"></label>
                        <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_vital_height" value="1" <?= !empty($cfg['show_vital_height']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1.2fr auto; gap: 10px; align-items: center;">
                        <label style="margin: 0; font-size: 12px;">Weight Label <input type="text" name="lbl_weight" value="<?= e($cfg['lbl_weight']) ?>"></label>
                        <label style="margin: 0; font-size: 12px;">Unit <input type="text" name="unit_weight" value="<?= e($cfg['unit_weight']) ?>"></label>
                        <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_vital_weight" value="1" <?= !empty($cfg['show_vital_weight']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1.2fr auto; gap: 10px; align-items: center;">
                        <label style="margin: 0; font-size: 12px;">Temp Label <input type="text" name="lbl_temp" value="<?= e($cfg['lbl_temp']) ?>"></label>
                        <label style="margin: 0; font-size: 12px;">Unit <input type="text" name="unit_temp" value="<?= e($cfg['unit_temp']) ?>"></label>
                        <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_vital_temp" value="1" <?= !empty($cfg['show_vital_temp']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1.2fr auto; gap: 10px; align-items: center;">
                        <label style="margin: 0; font-size: 12px;">Pulse Label <input type="text" name="lbl_pulse" value="<?= e($cfg['lbl_pulse']) ?>"></label>
                        <label style="margin: 0; font-size: 12px;">Unit <input type="text" name="unit_pulse" value="<?= e($cfg['unit_pulse']) ?>"></label>
                        <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_vital_pulse" value="1" <?= !empty($cfg['show_vital_pulse']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                        <label style="margin: 0; font-size: 12px;">Pain Score Label <input type="text" name="lbl_pain" value="<?= e($cfg['lbl_pain']) ?>"></label>
                        <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_vital_pain" value="1" <?= !empty($cfg['show_vital_pain']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                        <label style="margin: 0; font-size: 12px;">Allergies Label <input type="text" name="lbl_allergies" value="<?= e($cfg['lbl_allergies']) ?>"></label>
                        <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_vital_allergies" value="1" <?= !empty($cfg['show_vital_allergies']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: center;">
                        <label style="margin: 0; font-size: 12px;">BMI Label <input type="text" name="lbl_bmi" value="<?= e($cfg['lbl_bmi']) ?>"></label>
                        <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_vital_bmi" value="1" <?= !empty($cfg['show_vital_bmi']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1.2fr auto; gap: 10px; align-items: center;">
                        <label style="margin: 0; font-size: 12px;">BP Label <input type="text" name="lbl_bp" value="<?= e($cfg['lbl_bp']) ?>"></label>
                        <label style="margin: 0; font-size: 12px;">Unit <input type="text" name="unit_bp" value="<?= e($cfg['unit_bp']) ?>"></label>
                        <label style="margin: 0; font-size: 11px; align-items: center; cursor: pointer;"><input type="checkbox" name="show_vital_bp" value="1" <?= !empty($cfg['show_vital_bp']) ? 'checked' : '' ?> style="width:18px;height:18px;"> Show</label>
                    </div>
                </div>
            </div>

            <!-- CARD 8: FOOTER, CONTACTS & VALIDITY -->
            <div class="tpl-card">
                <div class="tpl-card-head">
                    <h3 class="tpl-card-title">
                        <span class="tpl-badge-num">8</span>
                        <span>Footer Contacts, Validity Note & Legal Information</span>
                    </h3>
                    <span class="tpl-badge-pill" style="background:#fce4ec; color:#c2185b;">FOOTER & LEGAL</span>
                </div>
                <div class="form-grid">
                    <label class="span-2">
                        Validity Note Text (Top of Footer Notice)
                        <input type="text" name="validity_note" value="<?= e($cfg['validity_note']) ?>" placeholder="e.g. Bill is valid for 3 days Including date of Billing.">
                    </label>

                    <label class="span-2">
                        Hospital Physical Address (Footer Column 1)
                        <input type="text" name="hospital_address" value="<?= e($cfg['hospital_address']) ?>" placeholder="e.g. Hospital.: Sector 119, Noida - 201305, U.P., India">
                    </label>

                    <label class="span-2">
                        Registered Office & CIN (Multi-line Text + Numbers)
                        <textarea name="reg_office" rows="3" placeholder="Reg. Office Address&#10;City, State - PIN&#10;CIN: XXXXXXXXXX"><?= e($cfg['reg_office']) ?></textarea>
                        <small style="color: #6a837c;">Enter complete registered office address and CIN number (line breaks are preserved).</small>
                    </label>

                    <label>
                        WhatsApp Number (Phone / Numbers)
                        <input type="tel" name="phone_whatsapp" value="<?= e($cfg['phone_whatsapp']) ?>" placeholder="+91 99937 77444">
                        <small style="color: #6a837c;">Input type="tel" enables number keypad on mobile & handhelds.</small>
                    </label>

                    <label>
                        Landline / Emergency Phone (Phone / Numbers)
                        <input type="tel" name="phone_landline" value="<?= e($cfg['phone_landline']) ?>" placeholder="+91 120 4154949">
                        <small style="color: #6a837c;">Input type="tel" ensures numeric phone formatting.</small>
                    </label>

                    <label>
                        Hospital Email Address
                        <input type="email" name="email" value="<?= e($cfg['email']) ?>" placeholder="info@motherlandhospital.com">
                    </label>

                    <label>
                        Hospital Website
                        <input type="text" name="website" value="<?= e($cfg['website']) ?>" placeholder="www.motherlandhospital.com">
                    </label>
                </div>
            </div>

            <!-- STICKY ACTION BAR -->
            <div class="sticky-action-bar">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <button class="btn btn-primary" type="submit" style="padding: 12px 24px; font-size: 15px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Save Template Settings
                    </button>
                    <?php if ($cfg['icon_path'] !== 'assets/motherland-icon.png'): ?>
                        <button class="btn btn-soft" type="submit" name="reset_icon" value="1">Reset Icon to Default</button>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <a class="btn btn-soft" href="print_opd.php?template_id=<?= $motherlandTpl['id'] ?>&pages=1">Preview 1-Page</a>
                    <a class="btn btn-soft" href="print_opd.php?template_id=<?= $motherlandTpl['id'] ?>&pages=2">Preview 2-Pages</a>
                </div>
            </div>
        </form>

        <script>
        // Two-way synchronization between numeric inputs and sliders
        (function() {
            // Watermark Size
            const wmSizeIn = document.getElementById('wm_size_input');
            const wmSizeSl = document.getElementById('wm_size_slider');
            const wmSizeBadge = document.getElementById('wm_size_badge');
            function syncWmSize(v) {
                wmSizeIn.value = v;
                wmSizeSl.value = v;
                if (wmSizeBadge) {
                    const tag = v < 85 ? 'Small' : (v > 130 ? 'Large' : 'Standard');
                    wmSizeBadge.textContent = v + ' mm (' + tag + ')';
                }
            }
            if (wmSizeIn && wmSizeSl) {
                wmSizeIn.addEventListener('input', e => syncWmSize(e.target.value));
                wmSizeSl.addEventListener('input', e => syncWmSize(e.target.value));
            }

            // Watermark Opacity
            const wmOpIn = document.getElementById('wm_opacity_input');
            const wmOpSl = document.getElementById('wm_opacity_slider');
            const wmOpBadge = document.getElementById('wm_opacity_badge');
            function syncWmOp(v) {
                wmOpIn.value = v;
                wmOpSl.value = v;
                if (wmOpBadge) wmOpBadge.textContent = v + '%';
            }
            if (wmOpIn && wmOpSl) {
                wmOpIn.addEventListener('input', e => syncWmOp(e.target.value));
                wmOpSl.addEventListener('input', e => syncWmOp(e.target.value));
            }

            // Accent Bar Height
            const accIn = document.getElementById('acc_height_input');
            const accSl = document.getElementById('acc_height_slider');
            const accBadge = document.getElementById('acc_height_badge');
            function syncAcc(v) {
                accIn.value = v;
                accSl.value = v;
                if (accBadge) accBadge.textContent = v + ' mm';
            }
            if (accIn && accSl) {
                accIn.addEventListener('input', e => syncAcc(e.target.value));
                accSl.addEventListener('input', e => syncAcc(e.target.value));
            }

            // Accent Bar Color
            const colPick = document.getElementById('accent_color_picker');
            const colHex = document.getElementById('accent_color_hex');
            if (colPick && colHex) {
                colPick.addEventListener('input', e => { colHex.value = e.target.value; });
                colHex.addEventListener('input', e => {
                    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
                        colPick.value = e.target.value;
                    }
                });
            }
        })();
        </script>
    <?php else: ?>
        <div class="empty">Motherland code template record is not yet initialized.</div>
    <?php endif; ?>
</section>

<!-- ========================================================= -->
<!-- 2. ORIGINAL IMAGE TEMPLATES SECTION (100% UNTOUCHED)     -->
<!-- ========================================================= -->
<div class="page-title" style="margin-top: 10px;">
    <div>
        <h3>Scanned Image Templates (Background Scans)</h3>
        <p>Upload clean A4 JPG/PNG/WEBP background scans and place patient fields using the drag-and-drop Layout Editor.</p>
    </div>
</div>

<div class="grid-2">
    <!-- Existing Upload template card (completely preserved) -->
    <section class="card">
        <h2>Upload template</h2>
        <form method="post" enctype="multipart/form-data" class="stack">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <label>
                Template name
                <input name="name" required placeholder="e.g. Sample Scan Template">
            </label>
            <label>
                Image file
                <input type="file" name="template_file" accept="image/jpeg,image/png,image/webp" required>
            </label>
            <button class="btn btn-primary">Upload template</button>
        </form>
        <div class="note">For exact A4 printing, use a straight, cropped A4 scan with no camera perspective.</div>
    </section>

    <!-- Existing Current templates card (completely preserved) -->
    <section class="card">
        <h2>Current image templates</h2>
        <div class="template-list">
            <?php foreach ($imageTemplates as $t): ?>
                <div class="template-row">
                    <img src="<?= e($t['file_path']) ?>" alt="Thumbnail">
                    <div>
                        <strong><?= e($t['name']) ?></strong>
                        <small><?= e($t['width'] . '×' . $t['height'] . ' · ' . ($t['active'] ? 'Active' : 'Blocked')) ?></small>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <a class="link" href="template_editor.php?template_id=<?= $t['id'] ?>">Edit layout</a>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="toggle_id" value="<?= $t['id'] ?>">
                            <button class="mini"><?= $t['active'] ? 'Block' : 'Unblock' ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($imageTemplates)): ?>
                <div class="empty">No scanned image templates uploaded.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
