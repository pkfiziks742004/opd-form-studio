<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$user = require_admin();
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/code_template.php';
require_once __DIR__ . '/includes/page_engine.php';
require_once __DIR__ . '/includes/theme_engine.php';

// Active Tab navigation
$activeTab = $_GET['tab'] ?? 'all';
if (!in_array($activeTab, ['all', 'customizer', 'images'], true)) {
    $activeTab = 'all';
}

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // 1. Create New Digital Code Template
    if ($action === 'create_code_template') {
        $name = trim($_POST['name'] ?? '');
        $themePreset = trim($_POST['theme_preset'] ?? 'green');
        $pageSize = trim($_POST['page_size'] ?? 'A4');
        $presets = get_paper_presets();
        if (!isset($presets[$pageSize])) $pageSize = 'A4';

        if ($name === '') {
            flash('error', 'Template name is required.');
            header('Location: templates.php?tab=all');
            exit;
        }

        $paper = $presets[$pageSize];
        $pageConfigJson = json_encode([
            'pageSize' => $pageSize,
            'orientation' => 'portrait',
            'width' => (float)$paper['width'],
            'height' => (float)$paper['height'],
            'unit' => $paper['unit'],
            'marginTop' => 6.0,
            'marginRight' => 12.0,
            'marginBottom' => 6.0,
            'marginLeft' => 12.0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $themePresets = get_theme_presets();
        $themeConfig = $themePresets[$themePreset] ?? $themePresets['green'];
        $themeConfigJson = json_encode($themeConfig);

        $slug = 'code:' . strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));

        $st = db()->prepare("
            INSERT INTO templates (name, file_path, original_name, mime_type, width, height, default_layout_json, active, created_by, template_type, page_size, orientation, page_width, page_height, page_unit, margin_top, margin_right, margin_bottom, margin_left, page_config_json, theme_preset, theme_config_json)
            VALUES (?, ?, ?, 'text/html', 794, 1123, '{}', 1, ?, 'code', ?, 'portrait', ?, ?, ?, 6.0, 12.0, 6.0, 12.0, ?, ?, ?)
        ");
        $st->execute([
            $name,
            $slug,
            $name . ' Code Template',
            $user['id'],
            $pageSize,
            (float)$paper['width'],
            (float)$paper['height'],
            $paper['unit'],
            $pageConfigJson,
            $themePreset,
            $themeConfigJson
        ]);

        $newId = (int)db()->lastInsertId();
        flash('success', "Digital Code Template '{$name}' created successfully.");
        header('Location: templates.php?tab=customizer&template_id=' . $newId);
        exit;
    }

    // 2. Upload Scanned Image Template
    if ($action === 'upload_image_template') {
        $name = trim($_POST['name'] ?? '');
        $f = $_FILES['template_file'] ?? null;
        if (!$name || !$f || $f['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Template name and image file are required.');
            header('Location: templates.php?tab=images');
            exit;
        }
        if ($f['size'] > 12 * 1024 * 1024) {
            flash('error', 'Image file must be under 12 MB.');
            header('Location: templates.php?tab=images');
            exit;
        }
        $info = @getimagesize($f['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$info || !isset($allowed[$info['mime']])) {
            flash('error', 'Please upload a valid JPG, PNG, or WEBP image.');
            header('Location: templates.php?tab=images');
            exit;
        }

        $uploadDir = __DIR__ . '/uploads/templates';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
        $fileName = 'tpl_' . bin2hex(random_bytes(10)) . '.' . $allowed[$info['mime']];
        $targetFile = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($f['tmp_name'], $targetFile)) {
            flash('error', 'Failed to save uploaded file.');
            header('Location: templates.php?tab=images');
            exit;
        }

        // Paper configuration
        $presets = get_paper_presets();
        $pageSize = trim($_POST['page_size'] ?? 'A4');
        if (!isset($presets[$pageSize])) $pageSize = 'A4';
        $orientation = strtolower(trim($_POST['orientation'] ?? 'portrait'));
        if ($orientation !== 'landscape') $orientation = 'portrait';
        $unit = 'mm';

        if ($pageSize !== 'Custom' && isset($presets[$pageSize])) {
            $paperWidth = (float)$presets[$pageSize]['width'];
            $paperHeight = (float)$presets[$pageSize]['height'];
        } else {
            $paperWidth = max(50.0, min(1000.0, (float)($_POST['custom_width'] ?? 210.0)));
            $paperHeight = max(50.0, min(1500.0, (float)($_POST['custom_height'] ?? 297.0)));
        }

        $pageConfigJson = json_encode([
            'pageSize' => $pageSize,
            'orientation' => $orientation,
            'width' => $paperWidth,
            'height' => $paperHeight,
            'unit' => $unit,
            'marginTop' => 0.0,
            'marginRight' => 0.0,
            'marginBottom' => 0.0,
            'marginLeft' => 0.0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $fieldMode = trim($_POST['field_mode'] ?? 'val_only');
        $showLabel = ($fieldMode === 'label_val');
        $initialFontSize = max(8, min(24, (int)($_POST['font_size'] ?? 11)));

        // Pre-configure initial layout coordinates cleanly over typical prescription header band
        $defaultLayout = [];
        $i = 0;
        foreach (FIELD_DEFS as $k => $label) {
            $defaultLayout[$k] = [
                'x' => 6.0 + ($i % 2) * 48.0,
                'y' => 12.0 + floor($i / 2) * 4.2,
                'fontSize' => $initialFontSize,
                'width' => 220,
                'fontWeight' => '600',
                'align' => 'left',
                'color' => '#111827',
                'showLabel' => $showLabel,
                'visible' => true
            ];
            $i++;
        }

        ensure_template_page_columns();

        $st = db()->prepare("
            INSERT INTO templates (
                name, file_path, original_name, mime_type, width, height, 
                default_layout_json, active, created_by, template_type,
                page_size, orientation, page_width, page_height, page_unit,
                margin_top, margin_right, margin_bottom, margin_left, page_config_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, 'image', ?, ?, ?, ?, ?, 0.0, 0.0, 0.0, 0.0, ?)
        ");
        $st->execute([
            $name,
            'uploads/templates/' . $fileName,
            $f['name'],
            $info['mime'],
            $info[0],
            $info[1],
            json_encode($defaultLayout, JSON_UNESCAPED_UNICODE),
            $user['id'],
            $pageSize,
            $orientation,
            $paperWidth,
            $paperHeight,
            $unit,
            $pageConfigJson
        ]);

        $newImgId = (int)db()->lastInsertId();
        flash('success', "Pre-Printed Pad Template '{$name}' created ({$pageSize}, {$orientation}). Open Layout Editor to fine-tune field positions.");
        header('Location: template_editor.php?template_id=' . $newImgId);
        exit;
    }

    // 3. Save Code Template Settings & Universal Parameters
    if (isset($_POST['save_code_template'])) {
        $defs = get_motherland_defaults();
        $configData = [];
        foreach ($defs as $key => $defaultVal) {
            if (isset($_POST[$key])) {
                $configData[$key] = trim((string)$_POST[$key]);
            }
        }

        // Opacity normalization
        if (isset($configData['watermark_opacity'])) {
            $opVal = (float)$configData['watermark_opacity'];
            if ($opVal > 1.0) {
                $configData['watermark_opacity'] = (string)round($opVal / 100, 2);
            }
        }

        // Explicitly handle all checkbox toggles
        $checkboxKeys = [
            'show_watermark', 'enable_two_pages', 'show_header', 'show_title',
            'show_patient_info', 'show_doctor_box', 'show_vitals', 'show_validity_note', 'show_footer',
            'show_divider_lines', 'show_signature_box',
            'show_uhid', 'show_name', 'show_age_sex', 'show_guardian', 'show_contact', 'show_address',
            'show_bill', 'show_date', 'show_panel', 'show_dept', 'show_room', 'show_app',
            'show_vital_height', 'show_vital_weight', 'show_vital_temp', 'show_vital_pulse',
            'show_vital_pain', 'show_vital_allergies', 'show_vital_bmi', 'show_vital_bp'
        ];
        foreach ($checkboxKeys as $ck) {
            $configData[$ck] = isset($_POST[$ck]) ? '1' : '0';
        }

        // Custom icon upload
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

        $targetTplId = (int)($_POST['template_id'] ?? 0);
        save_motherland_config($configData);

        if ($targetTplId > 0) {
            $tplJson = json_encode(['code_config' => $configData], JSON_UNESCAPED_UNICODE);
            $st = db()->prepare('UPDATE templates SET default_layout_json = ? WHERE id = ?');
            $st->execute([$tplJson, $targetTplId]);
        }

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
            if ($targetTplId > 0) {
                $upSt = db()->prepare('UPDATE templates SET 
                    page_size = ?, orientation = ?, page_width = ?, page_height = ?, page_unit = ?,
                    margin_top = ?, margin_right = ?, margin_bottom = ?, margin_left = ?, page_config_json = ?
                    WHERE id = ?');
                $upSt->execute([$pageSize, $orientation, $pageWidth, $pageHeight, $pageUnit, $marginTop, $marginRight, $marginBottom, $marginLeft, $pageConfigJson, $targetTplId]);
            }
        }

        // Save Universal Color Theme
        if (isset($_POST['theme_preset'])) {
            $themePreset = strtolower(trim((string)$_POST['theme_preset']));
            $themePresets = get_theme_presets();
            if (!isset($themePresets[$themePreset])) $themePreset = 'green';

            $customTheme = [
                'preset' => $themePreset,
                'primary' => trim((string)($_POST['theme_primary'] ?? '')),
                'secondary' => trim((string)($_POST['theme_secondary'] ?? '')),
                'accent' => trim((string)($_POST['theme_accent'] ?? '')),
                'border' => trim((string)($_POST['theme_border'] ?? '')),
                'heading' => trim((string)($_POST['theme_heading'] ?? '')),
                'text' => trim((string)($_POST['theme_text'] ?? '')),
                'label' => trim((string)($_POST['theme_label'] ?? '')),
                'icon' => trim((string)($_POST['theme_icon'] ?? '')),
                'watermark' => trim((string)($_POST['theme_watermark'] ?? '')),
                'watermarkOpacity' => !empty($_POST['theme_wm_opacity']) ? round((float)$_POST['theme_wm_opacity'] / 100, 2) : 0.08
            ];
            $themeConfigJson = json_encode($customTheme);
            if ($targetTplId > 0) {
                $upTh = db()->prepare('UPDATE templates SET theme_preset = ?, theme_config_json = ? WHERE id = ?');
                $upTh->execute([$themePreset, $themeConfigJson, $targetTplId]);
            }
        }

        flash('success', 'Template settings, branding, page sizes and color themes saved successfully.');
        header('Location: templates.php?tab=customizer&template_id=' . $targetTplId);
        exit;
    }

    // 4. Toggle Active / Blocked Status
    if (isset($_POST['toggle_id'])) {
        $id = (int)$_POST['toggle_id'];
        $st = db()->prepare('UPDATE templates SET active = 1 - active WHERE id = ?');
        $st->execute([$id]);
        flash('success', 'Template status updated.');
        header('Location: templates.php?tab=' . urlencode($activeTab));
        exit;
    }

    // 5. Set Default Template
    if (isset($_POST['set_default_id'])) {
        $defId = (int)$_POST['set_default_id'];
        set_setting('default_template_id', (string)$defId);
        flash('success', 'Default OPD template updated successfully.');
        header('Location: templates.php?tab=' . urlencode($activeTab));
        exit;
    }

    // 6. Delete Template
    if ($action === 'delete_template') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId > 0) {
            $defId = (int)setting('default_template_id', '0');
            if ($delId === $defId) {
                flash('error', 'Cannot delete the template currently set as Default. Set another template as default first.');
                header('Location: templates.php?tab=' . urlencode($activeTab));
                exit;
            }

            $st = db()->prepare('SELECT file_path, template_type FROM templates WHERE id = ?');
            $st->execute([$delId]);
            $tplToDelete = $st->fetch();

            if ($tplToDelete) {
                if ($tplToDelete['template_type'] === 'image' && !empty($tplToDelete['file_path'])) {
                    $imgFullPath = __DIR__ . '/' . ltrim($tplToDelete['file_path'], '/');
                    if (file_exists($imgFullPath) && strpos($tplToDelete['file_path'], 'sample-opd-template') === false) {
                        @unlink($imgFullPath);
                    }
                }
                db()->prepare('DELETE FROM template_layouts WHERE template_id = ?')->execute([$delId]);
                db()->prepare('DELETE FROM templates WHERE id = ?')->execute([$delId]);
                flash('success', 'Template deleted successfully.');
            }
        }
        header('Location: templates.php?tab=' . urlencode($activeTab));
        exit;
    }
}

// Fetch templates
$allTemplates = db()->query('SELECT t.*, u.name creator FROM templates t LEFT JOIN users u ON u.id = t.created_by ORDER BY t.id DESC')->fetchAll();
$codeTemplates = array_values(array_filter($allTemplates, fn($t) => is_code_template($t)));
$imageTemplates = array_values(array_filter($allTemplates, fn($t) => !is_code_template($t)));
$defaultTemplateId = (int)setting('default_template_id', '0');

// Determine selected Code Template for customizer tab
$selectedTplId = (int)($_GET['template_id'] ?? 0);
$selectedCodeTpl = null;
if ($selectedTplId > 0) {
    foreach ($codeTemplates as $ct) {
        if ((int)$ct['id'] === $selectedTplId) {
            $selectedCodeTpl = $ct;
            break;
        }
    }
}
if (!$selectedCodeTpl && !empty($codeTemplates)) {
    foreach ($codeTemplates as $ct) {
        if ((int)$ct['id'] === $defaultTemplateId) {
            $selectedCodeTpl = $ct;
            break;
        }
    }
    if (!$selectedCodeTpl) {
        $selectedCodeTpl = $codeTemplates[0];
    }
}

$cfg = get_motherland_config($selectedCodeTpl);
$pageConfig = $selectedCodeTpl ? get_template_page_config($selectedCodeTpl) : null;
$paperPresets = get_paper_presets();
$themePresets = get_theme_presets();
$tplTheme = $selectedCodeTpl ? get_template_theme($selectedCodeTpl) : $themePresets['green'];
$activeThemeKey = strtolower(trim((string)($selectedCodeTpl['theme_preset'] ?? 'green')));
if (!isset($themePresets[$activeThemeKey])) $activeThemeKey = 'green';
$defaultPrintPages = (string)($cfg['default_print_pages'] ?? '1');

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ==========================================================================
   PRODUCTION-LEVEL OPD TEMPLATES HUB DESIGN SYSTEM
   ========================================================================== */
.tpl-shell {
    display: flex;
    flex-direction: column;
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Master Header Card */
.tpl-nav-banner {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

/* Tab Segmented Switcher */
.tpl-seg-nav {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
}

.tpl-seg-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    transition: all 0.18s ease;
}

.tpl-seg-item:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.7);
}

.tpl-seg-item.is-active {
    background: #ffffff;
    color: var(--primary);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    font-weight: 700;
}

.tpl-pill-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    background: #e2e8f0;
    color: #475569;
}

.tpl-seg-item.is-active .tpl-pill-count {
    background: var(--primary-light);
    color: var(--primary);
}

/* Top Creation Actions */
.tpl-top-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-tpl-create {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    text-decoration: none;
}

.btn-tpl-create.primary {
    background: linear-gradient(135deg, #087F6C 0%, #066757 100%);
    color: #ffffff;
    box-shadow: 0 3px 10px rgba(8, 127, 108, 0.25);
}

.btn-tpl-create.primary:hover {
    background: linear-gradient(135deg, #077160 0%, #055447 100%);
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(8, 127, 108, 0.35);
}

.btn-tpl-create.secondary {
    background: #f8fafc;
    color: #334155;
    border: 1.5px solid #cbd5e1;
}

.btn-tpl-create.secondary:hover {
    background: #ffffff;
    border-color: #94a3b8;
    color: #0f172a;
    transform: translateY(-1px);
}

/* Subheader / Template Context Bar */
.tpl-context-bar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.03);
}

.tpl-context-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.tpl-active-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    background: #e6f5f2;
    color: #087f6c;
}

.tpl-context-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* ==========================================================================
   PRODUCTION SaaS CARDS (CUSTOMIZER)
   ========================================================================== */
.prod-card-group {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.prod-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
    transition: all 0.2s ease;
}

.prod-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 8px 26px -4px rgba(15, 23, 42, 0.07);
}

.prod-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 18px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 22px;
}

.prod-card-title-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
}

.prod-card-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.prod-card-icon-box.teal { background: #e6f5f2; color: #087f6c; }
.prod-card-icon-box.blue { background: #e0f2fe; color: #0284c7; }
.prod-card-icon-box.purple { background: #f3e8ff; color: #7e22ce; }
.prod-card-icon-box.amber { background: #fef3c7; color: #b45309; }
.prod-card-icon-box.sky { background: #e0f2fe; color: #0369a1; }
.prod-card-icon-box.green { background: #dcfce7; color: #15803d; }
.prod-card-icon-box.red { background: #fee2e2; color: #b91c1c; }
.prod-card-icon-box.rose { background: #ffe4e6; color: #e11d48; }

.prod-card-headings h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}

.prod-card-headings p {
    margin: 4px 0 0;
    font-size: 12.5px;
    color: #64748b;
}

.prod-badge-tag {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 4px 10px;
    border-radius: 6px;
    text-transform: uppercase;
    flex-shrink: 0;
}

/* Modern Form Field Styles */
.prod-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.prod-grid.cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.prod-grid.cols-4 {
    grid-template-columns: repeat(4, 1fr);
}

.prod-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.prod-field.span-2 {
    grid-column: 1 / -1;
}

.prod-label {
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.prod-input, .prod-select, .prod-textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13.5px;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.prod-input:focus, .prod-select:focus, .prod-textarea:focus {
    border-color: #087f6c;
    box-shadow: 0 0 0 3px rgba(8, 127, 108, 0.12);
    outline: none;
}

.prod-input::placeholder, .prod-textarea::placeholder {
    color: #94a3b8;
}

/* Modern Logo Upload Zone */
.prod-logo-uploader {
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;
    padding: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    transition: border-color 0.2s;
}

.prod-logo-uploader:hover {
    border-color: #087f6c;
}

.prod-logo-preview-col {
    display: flex;
    align-items: center;
    gap: 16px;
}

.prod-logo-box {
    width: 60px;
    height: 60px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.prod-logo-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.prod-logo-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-upload-file {
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all 0.18s ease;
}

.btn-upload-file:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}

.btn-upload-file input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

/* Interactive iOS Switch */
.ios-toggle-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    cursor: pointer;
    transition: all 0.18s ease;
}

.ios-toggle-wrap:hover {
    background: #f1f5f9;
}

.ios-toggle-left {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ios-toggle-title {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
}

.ios-toggle-desc {
    font-size: 11.5px;
    color: #64748b;
}

.ios-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.ios-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.ios-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: #cbd5e1;
    transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 24px;
}

.ios-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.ios-switch input:checked + .ios-slider {
    background-color: #087f6c;
}

.ios-switch input:checked + .ios-slider:before {
    transform: translateX(20px);
}

/* Visual Theme Preset Cards */
.theme-swatch-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.theme-swatch-card {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    cursor: pointer;
    background: #ffffff;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.theme-swatch-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.theme-swatch-card.is-active {
    border-color: #087f6c;
    background: #f7fdfb;
    box-shadow: 0 4px 12px rgba(8, 127, 108, 0.12);
}

.theme-swatch-dots {
    display: flex;
    align-items: center;
    gap: 6px;
}

.swatch-circle {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.theme-swatch-name {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Custom Hex Pickers Container */
.color-picker-box {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
}

.color-picker-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.color-picker-item span {
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
}

.color-input-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    padding: 3px 8px;
}

.color-input-wrap input[type="color"] {
    -webkit-appearance: none;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    cursor: pointer;
    background: none;
    padding: 0;
}

.color-input-wrap input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
}

.color-input-wrap input[type="color"]::-webkit-color-swatch {
    border: 1px solid #cbd5e1;
    border-radius: 4px;
}

.color-input-wrap input[type="text"] {
    border: none;
    font-size: 12px;
    font-family: monospace;
    font-weight: 600;
    color: #0f172a;
    width: 100%;
    outline: none;
    background: transparent;
}

/* Print Mode Big Cards */
.print-mode-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.print-mode-opt {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    cursor: pointer;
    background: #ffffff;
    transition: all 0.2s ease;
    position: relative;
}

.print-mode-opt:hover {
    border-color: #cbd5e1;
    background: #fbfdfe;
}

.print-mode-opt.is-selected {
    border-color: #087f6c;
    background: #f4fbf9;
    box-shadow: 0 4px 14px rgba(8, 127, 108, 0.1);
}

.print-mode-opt input[type="radio"] {
    margin-top: 3px;
    width: 18px;
    height: 18px;
    accent-color: #087f6c;
}

.print-mode-desc strong {
    display: block;
    font-size: 14.5px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.print-mode-desc p {
    margin: 0;
    font-size: 12px;
    color: #64748b;
    line-height: 1.45;
}

/* Margin Controller Box */
.margin-control-panel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px;
}

.margin-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.margin-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.margin-item label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 4px;
}

.margin-item input {
    padding: 8px 12px;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    width: 100%;
}

/* Demographic Micro-Cards */
.field-toggle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}

.field-micro-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    transition: all 0.16s ease;
}

.field-micro-card:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.field-micro-card .input-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.field-micro-card .input-col span {
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
}

.field-micro-card input[type="text"] {
    padding: 6px 10px;
    border: 1.5px solid #cbd5e1;
    border-radius: 6px;
    font-size: 12.5px;
    background: #ffffff;
    width: 100%;
}

/* Vitals Micro-Cards */
.vital-micro-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.vital-micro-card .param-name {
    width: 100px;
    font-size: 12.5px;
    font-weight: 700;
    color: #0f172a;
}

.vital-micro-card .param-label-in {
    flex: 1;
}

.vital-micro-card .param-unit-in {
    width: 70px;
}

/* Range Slider Synchronization */
.slider-container {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 6px;
}

.slider-container input[type="range"] {
    flex: 1;
    height: 6px;
    accent-color: #087f6c;
    cursor: pointer;
}

.slider-badge-val {
    min-width: 46px;
    text-align: center;
    padding: 4px 8px;
    background: #e6f5f2;
    color: #087f6c;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 700;
}

/* Preset Quick Badges */
.quick-preset-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 6px;
}

.badge-preset-btn {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}

.badge-preset-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #cbd5e1;
}

/* ==========================================================================
   FLOATING GLASSMORPHIC BOTTOM DOCK
   ========================================================================== */
.sticky-dock-bar {
    position: sticky;
    bottom: 16px;
    z-index: 100;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1.5px solid rgba(8, 127, 108, 0.25);
    border-radius: 12px;
    padding: 14px 22px;
    box-shadow: 0 12px 32px -4px rgba(7, 63, 56, 0.18);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 28px;
}

.dock-left-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.dock-right-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-dock-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #087F6C 0%, #066757 100%);
    color: #ffffff;
    border: none;
    padding: 11px 26px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(8, 127, 108, 0.3);
    transition: all 0.2s ease;
}

.btn-dock-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(8, 127, 108, 0.4);
}

.btn-dock-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 16px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    text-decoration: none;
    transition: all 0.18s ease;
}

.btn-dock-link:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
    transform: translateY(-1px);
}

.btn-dock-link.preview {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #15803d;
}

.btn-dock-link.preview:hover {
    background: #dcfce7;
    border-color: #86efac;
    color: #14532d;
}

/* ==========================================================================
   CARDS IN TAB 1 (ALL TEMPLATES) & TAB 3 (IMAGE SCANS)
   ========================================================================== */
.tpl-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 20px;
}

.tpl-overview-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 16px;
    transition: all 0.2s ease;
}

.tpl-overview-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
}

.tpl-overview-card.is-default {
    border-color: #087f6c;
    background: linear-gradient(180deg, #f7fdfb 0%, #ffffff 100%);
    box-shadow: 0 4px 18px rgba(8, 127, 108, 0.1);
}

.tpl-overview-card.is-inactive {
    opacity: 0.72;
    background: #fafafa;
}

.card-top-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.type-indicator-avatar {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.type-indicator-avatar.code {
    background: #e6f5f2;
    color: #087f6c;
}

.type-indicator-avatar.image {
    background: #e0f2fe;
    color: #0284c7;
}

.card-title-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.card-title-details h3 {
    margin: 0;
    font-size: 15.5px;
    font-weight: 700;
    color: #0f172a;
}

.card-badges-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 2px;
}

.badge-tag-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
}

.badge-tag-pill.code { background: #e6f5f2; color: #087f6c; }
.badge-tag-pill.image { background: #e0f2fe; color: #0284c7; }
.badge-tag-pill.default { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

.card-specs-list {
    background: #f8fafc;
    border-radius: 8px;
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 12px;
    color: #64748b;
}

.card-specs-list div {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-specs-list strong {
    color: #1e293b;
    font-weight: 600;
}

.card-bottom-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
    flex-wrap: wrap;
}

.action-btn-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
}

.action-btn-pill:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.action-btn-pill.primary {
    background: #e6f5f2;
    color: #087f6c;
    border-color: #b2dfdb;
    font-weight: 700;
}

.action-btn-pill.primary:hover {
    background: #087f6c;
    color: #ffffff;
    border-color: #087f6c;
}

.action-btn-pill.danger:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
}

/* Modals */
.tpl-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
    padding: 16px;
}

.tpl-modal-box {
    background: #ffffff;
    border-radius: 12px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    animation: modalPopIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalPopIn {
    0% { opacity: 0; transform: scale(0.96) translateY(8px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

.tpl-modal-header {
    background: linear-gradient(135deg, #073F38 0%, #087F6C 100%);
    color: #ffffff;
    padding: 16px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.tpl-modal-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
}

.btn-modal-close-icon {
    background: transparent;
    border: none;
    color: #ffffff;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    opacity: 0.85;
}

.btn-modal-close-icon:hover {
    opacity: 1;
}

.tpl-modal-body {
    padding: 22px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.tpl-modal-footer {
    padding: 14px 22px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<div class="tpl-shell">
    <!-- MASTER TOP NAVIGATION & CREATION ACTION BAR -->
    <div class="tpl-nav-banner">
        <div class="tpl-seg-nav">
            <a href="templates.php?tab=all" class="tpl-seg-item <?= $activeTab === 'all' ? 'is-active' : '' ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                    <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                    <rect width="7" height="7" x="14" y="14" rx="1"></rect>
                    <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                </svg>
                <span>All Templates</span>
                <span class="tpl-pill-count"><?= count($allTemplates) ?></span>
            </a>
            <a href="templates.php?tab=customizer" class="tpl-seg-item <?= $activeTab === 'customizer' ? 'is-active' : '' ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>Digital Code Designer</span>
                <span class="tpl-pill-count"><?= count($codeTemplates) ?></span>
            </a>
            <a href="templates.php?tab=images" class="tpl-seg-item <?= $activeTab === 'images' ? 'is-active' : '' ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                    <circle cx="9" cy="9" r="2"></circle>
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                </svg>
                <span>Pre-Printed Image Scans</span>
                <span class="tpl-pill-count"><?= count($imageTemplates) ?></span>
            </a>
        </div>

        <div class="tpl-top-actions">
            <button type="button" class="btn-tpl-create primary" onclick="openCreateCodeModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Create Digital Template</span>
            </button>
            <button type="button" class="btn-tpl-create secondary" onclick="openUploadImageModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <span>Upload Scanned Pad</span>
            </button>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: ALL TEMPLATES OVERVIEW & CARDS                                 -->
    <!-- ===================================================================== -->
    <?php if ($activeTab === 'all'): ?>
        <!-- TWO TEMPLATE TYPES HERO BANNER -->
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(15, 23, 42, 0.04); margin-bottom:20px;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
                        <span>Two Supported OPD Slip Modes</span>
                        <span style="font-size:11px; font-weight:700; background:#e6f5f2; color:#087f6c; padding:2px 8px; border-radius:4px;">BOTH ACTIVE & READY</span>
                    </h3>
                    <p style="margin:4px 0 0; font-size:13px; color:#64748b;">Aap in dono me se jo bhi template type use karna chahein, reception par direct select karke print kar sakte hain.</p>
                </div>
                
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:12.5px; font-weight:600; color:#475569;">System Default:</span>
                    <form method="post" action="templates.php?tab=all" style="display:inline;">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <select name="set_default_id" class="prod-select" style="padding:6px 12px; font-size:13px; font-weight:700; color:#087f6c; width:auto;" onchange="this.form.submit()">
                            <?php foreach ($allTemplates as $at): 
                                $isCt = is_code_template($at);
                            ?>
                                <option value="<?= $at['id'] ?>" <?= ((int)$at['id'] === $defaultTemplateId) ? 'selected' : '' ?>>
                                    <?= $isCt ? '🩺 Digital Vector: ' : '🖼️ Uploaded Pad: ' ?><?= e($at['name']) ?><?= ((int)$at['id'] === $defaultTemplateId) ? ' (Default)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <!-- Type 1 Box -->
                <div style="background:#f7fdfb; border:1.5px solid #b2dfdb; border-radius:10px; padding:16px; display:flex; flex-direction:column; justify-content:space-between; gap:12px;">
                    <div>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                            <strong style="font-size:14.5px; color:#064e3b; display:flex; align-items:center; gap:6px;">
                                <span>🩺 Type 1: Digital Vector Code Template</span>
                            </strong>
                            <span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px; background:#dcfce7; color:#15803d;">BLANK PAPER PRINT</span>
                        </div>
                        <p style="margin:0; font-size:12.5px; color:#334155; line-height:1.45;">
                            Plain A4 blank paper par computer se full slip draw karta hai (Hospital logo, header, vitals table, doctor cabin, 1-page/2-page clinical sheet). Physical pad ki zaroorat nahi.
                        </p>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <a href="templates.php?tab=customizer" class="action-btn-pill primary">
                            Customize Digital Code &rarr;
                        </a>
                        <button type="button" class="action-btn-pill" onclick="openCreateCodeModal()">+ New Code Tpl</button>
                    </div>
                </div>

                <!-- Type 2 Box -->
                <div style="background:#f0f9ff; border:1.5px solid #bae6fd; border-radius:10px; padding:16px; display:flex; flex-direction:column; justify-content:space-between; gap:12px;">
                    <div>
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                            <strong style="font-size:14.5px; color:#0c4a6e; display:flex; align-items:center; gap:6px;">
                                <span>🖼️ Type 2: Uploaded Pre-Printed Pad Scan</span>
                            </strong>
                            <span style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px; background:#e0f2fe; color:#0284c7;">PHYSICAL PAD PRINT</span>
                        </div>
                        <p style="margin:0; font-size:12.5px; color:#334155; line-height:1.45;">
                            Hospital ke printed physical pad/parche ki photo upload karke text fields ko exact line par drag & drop set karein. Printer sirf text print karega printed stationery par.
                        </p>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <?php 
                        $primaryImgId = !empty($imageTemplates) ? (int)$imageTemplates[0]['id'] : 0;
                        ?>
                        <?php if ($primaryImgId > 0): ?>
                            <a href="template_editor.php?template_id=<?= $primaryImgId ?>" class="action-btn-pill" style="background:#e0f2fe; border-color:#93c5fd; color:#0369a1; font-weight:700;">
                                Open Drag-Drop Editor &rarr;
                            </a>
                            <a href="templates.php?tab=images" class="action-btn-pill" style="background:#f0f9ff; border-color:#bae6fd; color:#0369a1;">
                                Manage Pad Scans (<?= count($imageTemplates) ?>)
                            </a>
                        <?php else: ?>
                            <button type="button" class="action-btn-pill" onclick="openUploadImageModal()" style="background:#e0f2fe; border-color:#93c5fd; color:#0369a1; font-weight:700;">
                                Open Drag-Drop Editor &rarr;
                            </button>
                        <?php endif; ?>
                        <button type="button" class="action-btn-pill primary" onclick="openUploadImageModal()">+ Upload New Pad Scan</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="tpl-cards-grid">
            <?php foreach ($allTemplates as $tpl): 
                $isDefault = ((int)$tpl['id'] === $defaultTemplateId);
                $isCode = is_code_template($tpl);
                $pageSize = $tpl['page_size'] ?: 'A4';
                $orientation = ucfirst($tpl['orientation'] ?: 'Portrait');
            ?>
                <div class="tpl-overview-card <?= $isDefault ? 'is-default' : '' ?> <?= empty($tpl['active']) ? 'is-inactive' : '' ?>">
                    <div>
                        <div class="card-top-header">
                            <div class="type-indicator-avatar <?= $isCode ? 'code' : 'image' ?>">
                                <?php if ($isCode): ?>
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="16 18 22 12 16 6"></polyline>
                                        <polyline points="8 6 2 12 8 18"></polyline>
                                    </svg>
                                <?php else: ?>
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <path d="M21 15l-5-5L5 21"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="card-title-details">
                                <h3><?= e($tpl['name']) ?></h3>
                                <div class="card-badges-row">
                                    <span class="badge-tag-pill <?= $isCode ? 'code' : 'image' ?>">
                                        <?= $isCode ? '🩺 Vector Digital' : '🖼️ Pre-printed Scan' ?>
                                    </span>
                                    <?php if ($isDefault): ?>
                                        <span class="badge-tag-pill default">★ Default OPD Template</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-specs-list" style="margin-top:14px;">
                            <div><span>Paper Format:</span> <strong><?= e($pageSize) ?> (<?= e($orientation) ?>)</strong></div>
                            <div><span>Rendering Engine:</span> <strong><?= $isCode ? 'Dynamic HTML5 Engine' : 'Pixel Coordinate Overlay' ?></strong></div>
                            <div><span>Reception Status:</span> <strong style="color:<?= !empty($tpl['active']) ? '#15803d' : '#94a3b8' ?>"><?= !empty($tpl['active']) ? '● Active' : '○ Disabled' ?></strong></div>
                        </div>
                    </div>

                    <div class="card-bottom-actions">
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <?php if ($isCode): ?>
                                <a href="templates.php?tab=customizer&template_id=<?= $tpl['id'] ?>" class="action-btn-pill primary">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    <span>Customize</span>
                                </a>
                            <?php endif; ?>

                            <a href="template_editor.php?template_id=<?= $tpl['id'] ?>" class="action-btn-pill">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M9 3v18"></path></svg>
                                <span>Layout</span>
                            </a>

                            <a href="print_opd.php?template_id=<?= $tpl['id'] ?>" target="_blank" class="action-btn-pill" title="Print Preview">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                <span>Preview</span>
                            </a>
                        </div>

                        <div style="display:flex; gap:6px; align-items:center;">
                            <?php if (!$isDefault): ?>
                                <form method="post" action="templates.php" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="set_default_id" value="<?= $tpl['id'] ?>">
                                    <button type="submit" class="action-btn-pill" title="Set as default OPD slip">Make Default</button>
                                </form>
                            <?php endif; ?>

                            <form method="post" action="templates.php" style="display:inline;">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="toggle_id" value="<?= $tpl['id'] ?>">
                                <button type="submit" class="action-btn-pill" title="Toggle active status">
                                    <?= !empty($tpl['active']) ? 'Block' : 'Activate' ?>
                                </button>
                            </form>

                            <?php if (!$isDefault): ?>
                                <form method="post" action="templates.php" style="display:inline;" onsubmit="return confirm('Delete this template?');">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_template">
                                    <input type="hidden" name="id" value="<?= $tpl['id'] ?>">
                                    <button type="submit" class="action-btn-pill danger" title="Delete template">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: DIGITAL CODE TEMPLATE CUSTOMIZER                               -->
    <!-- ===================================================================== -->
    <?php elseif ($activeTab === 'customizer'): ?>
        <?php if ($selectedCodeTpl): 
            $wmOpacityVal = (float)($cfg['watermark_opacity'] ?? 0.06);
            $wmOpacityPct = (int)round($wmOpacityVal <= 1.0 ? $wmOpacityVal * 100 : $wmOpacityVal);
            $wmSizeVal = (int)($cfg['watermark_size'] ?? 105);
            if ($wmSizeVal < 40) $wmSizeVal = 105;
            $accentHeightVal = (int)($cfg['accent_height'] ?? 28);
            if ($accentHeightVal < 15) $accentHeightVal = 28;
        ?>
            <!-- Template Context Header -->
            <div class="tpl-context-bar">
                <div class="tpl-context-left">
                    <span class="tpl-active-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Editing: <strong><?= e($selectedCodeTpl['name']) ?></strong>
                    </span>
                    <span style="font-size:12px; color:#64748b;">Format: <strong><?= e($pageConfig['pageSize'] ?? 'A4') ?> · <?= ucfirst($pageConfig['orientation'] ?? 'portrait') ?></strong></span>
                    <?php if ((int)$selectedCodeTpl['id'] === $defaultTemplateId): ?>
                        <span class="badge-tag-pill default">★ Default System Template</span>
                    <?php endif; ?>
                </div>

                <div class="tpl-context-right">
                    <?php if (count($codeTemplates) > 1): ?>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label for="selectCodeTpl" style="font-size:12.5px; font-weight:600; color:#475569;">Switch Template:</label>
                            <select id="selectCodeTpl" class="prod-select" style="width:auto; padding:6px 12px; font-size:13px;" onchange="window.location.href='templates.php?tab=customizer&template_id=' + this.value">
                                <?php foreach ($codeTemplates as $ct): ?>
                                    <option value="<?= $ct['id'] ?>" <?= $selectedCodeTpl['id'] === $ct['id'] ? 'selected' : '' ?>>
                                        <?= e($ct['name']) ?> <?= ((int)$ct['id'] === $defaultTemplateId) ? ' (Default)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <a href="template_editor.php?template_id=<?= $selectedCodeTpl['id'] ?>" class="btn-dock-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M9 3v18"></path></svg>
                        <span>Visual Coordinate Editor &rarr;</span>
                    </a>
                </div>
            </div>

            <!-- CUSTOMIZER CARDS FORM -->
            <form method="post" enctype="multipart/form-data" id="customizerForm">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="save_code_template" value="1">
                <input type="hidden" name="template_id" value="<?= $selectedCodeTpl['id'] ?>">

                <div class="prod-card-group">
                    <!-- ========================================================= -->
                    <!-- CARD 1: HOSPITAL IDENTITY & BRANDING                      -->
                    <!-- ========================================================= -->
                    <div class="prod-card">
                        <div class="prod-card-head">
                            <div class="prod-card-title-wrap">
                                <div class="prod-card-icon-box teal">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 21h18"></path>
                                        <path d="M5 21V7l8-4v18"></path>
                                        <path d="M19 21V11l-6-4"></path>
                                        <path d="M9 9h1"></path>
                                        <path d="M9 13h1"></path>
                                        <path d="M9 17h1"></path>
                                    </svg>
                                </div>
                                <div class="prod-card-headings">
                                    <h3>1. Hospital Identity, Brand Logo & Document Title</h3>
                                    <p>Configure hospital naming, branding, document title, and high-resolution logo</p>
                                </div>
                            </div>
                            <span class="prod-badge-tag" style="background:#e6f5f2; color:#087f6c;">IDENTITY & LOGO</span>
                        </div>

                        <div class="prod-grid">
                            <div class="prod-field">
                                <label class="prod-label">Hospital Name (Primary Branding) <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="hospital_name" class="prod-input" value="<?= e($cfg['hospital_name']) ?>" required placeholder="e.g. Motherland Hospital">
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Tagline / Subtitle (Under Hospital Name)</label>
                                <input type="text" name="hospital_tagline" class="prod-input" value="<?= e($cfg['hospital_tagline']) ?>" placeholder="e.g. HOSPITAL & RESEARCH CENTRE">
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Document Title (Centered on Slip)</label>
                                <input type="text" name="doc_title" class="prod-input" value="<?= e($cfg['doc_title']) ?>" placeholder="e.g. Consultation Paper(OPD)">
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Default Doctor Department</label>
                                <input type="text" name="doctor_dept" class="prod-input" value="<?= e($cfg['doctor_dept']) ?>" placeholder="e.g. IVF / General Medicine">
                            </div>

                            <!-- High-End Logo Uploader Component -->
                            <div class="prod-field span-2">
                                <div class="prod-logo-uploader">
                                    <div class="prod-logo-preview-col">
                                        <div class="prod-logo-box">
                                            <img id="logoPreviewImg" src="<?= e($cfg['icon_path']) ?>" alt="Hospital Logo">
                                        </div>
                                        <div>
                                            <div style="font-weight:700; font-size:13.5px; color:#0f172a;">Hospital Brand Logo / Watermark Icon</div>
                                            <div style="font-size:12px; color:#64748b; margin-top:2px;">
                                                PNG with transparency, JPG or WebP up to 5 MB. Appears in top header banner & watermark.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="prod-logo-actions">
                                        <label class="btn-upload-file">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                            <span>Upload New Logo</span>
                                            <input type="file" name="custom_icon" id="customIconInput" accept="image/png,image/jpeg,image/webp" onchange="previewLogo(this)">
                                        </label>

                                        <?php if ($cfg['icon_path'] !== 'assets/motherland-icon.png'): ?>
                                            <button type="submit" name="reset_icon" value="1" class="action-btn-pill danger" style="padding:8px 14px;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
                                                <span>Reset to Default</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- CARD 2: PAPER FORMAT, ORIENTATION & MARGINS               -->
                    <!-- ========================================================= -->
                    <div class="prod-card">
                        <div class="prod-card-head">
                            <div class="prod-card-title-wrap">
                                <div class="prod-card-icon-box blue">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </div>
                                <div class="prod-card-headings">
                                    <h3>2. Paper Format, Orientation & Precise Margins</h3>
                                    <p>Select paper size standard (A4, A5, Letter) and fine-tune printer edge margins</p>
                                </div>
                            </div>
                            <span class="prod-badge-tag" style="background:#e0f2fe; color:#0284c7;">PAGE SETUP</span>
                        </div>

                        <div class="prod-grid">
                            <div class="prod-field">
                                <label class="prod-label">Paper Size Standard</label>
                                <select name="page_size" id="page_size_select" class="prod-select" onchange="toggleCustomDimensions(this.value)">
                                    <?php foreach ($paperPresets as $k => $preset): ?>
                                        <option value="<?= e($k) ?>" <?= ($pageConfig['pageSize'] ?? 'A4') === $k ? 'selected' : '' ?>>
                                            <?= e($preset['name']) ?> (<?= $preset['width'] ?> × <?= $preset['height'] ?> <?= $preset['unit'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Print Orientation</label>
                                <select name="orientation" class="prod-select">
                                    <option value="portrait" <?= ($pageConfig['orientation'] ?? 'portrait') === 'portrait' ? 'selected' : '' ?>>Portrait (Vertical / Standard)</option>
                                    <option value="landscape" <?= ($pageConfig['orientation'] ?? 'portrait') === 'landscape' ? 'selected' : '' ?>>Landscape (Horizontal)</option>
                                </select>
                            </div>

                            <!-- Custom Dimensions (Revealed when Custom selected) -->
                            <div id="customDimensionsPanel" class="prod-field span-2" style="display: <?= ($pageConfig['pageSize'] ?? '') === 'Custom' ? 'block' : 'none' ?>; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">
                                <div style="font-size:12.5px; font-weight:700; color:#334155; margin-bottom:10px;">Custom Sheet Dimensions</div>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px;">
                                    <div>
                                        <label class="prod-label">Width</label>
                                        <input type="number" step="0.5" name="page_width" class="prod-input" value="<?= (float)($pageConfig['width'] ?? 210) ?>">
                                    </div>
                                    <div>
                                        <label class="prod-label">Height</label>
                                        <input type="number" step="0.5" name="page_height" class="prod-input" value="<?= (float)($pageConfig['height'] ?? 297) ?>">
                                    </div>
                                    <div>
                                        <label class="prod-label">Measurement Unit</label>
                                        <select name="page_unit" class="prod-select">
                                            <option value="mm" <?= ($pageConfig['unit'] ?? 'mm') === 'mm' ? 'selected' : '' ?>>Millimeters (mm)</option>
                                            <option value="cm" <?= ($pageConfig['unit'] ?? '') === 'cm' ? 'selected' : '' ?>>Centimeters (cm)</option>
                                            <option value="in" <?= ($pageConfig['unit'] ?? '') === 'in' ? 'selected' : '' ?>>Inches (in)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Margin Controller -->
                            <div class="prod-field span-2">
                                <div class="margin-control-panel">
                                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                        <span style="font-size:13px; font-weight:700; color:#1e293b;">4-Sided Print Margins (mm)</span>
                                        <span style="font-size:12px; color:#64748b;">Calibrate laser and thermal printer bleed edges</span>
                                    </div>
                                    <div class="margin-grid">
                                        <div class="margin-item">
                                            <label>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                                                Top Margin
                                            </label>
                                            <input type="number" step="0.5" name="margin_top" value="<?= (float)($pageConfig['marginTop'] ?? 6) ?>">
                                        </div>
                                        <div class="margin-item">
                                            <label>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                                Right Margin
                                            </label>
                                            <input type="number" step="0.5" name="margin_right" value="<?= (float)($pageConfig['marginRight'] ?? 12) ?>">
                                        </div>
                                        <div class="margin-item">
                                            <label>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                                                Bottom Margin
                                            </label>
                                            <input type="number" step="0.5" name="margin_bottom" value="<?= (float)($pageConfig['marginBottom'] ?? 6) ?>">
                                        </div>
                                        <div class="margin-item">
                                            <label>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                                                Left Margin
                                            </label>
                                            <input type="number" step="0.5" name="margin_left" value="<?= (float)($pageConfig['marginLeft'] ?? 12) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- CARD 3: COLOR THEMES & VISUAL ACCENTS                     -->
                    <!-- ========================================================= -->
                    <div class="prod-card">
                        <div class="prod-card-head">
                            <div class="prod-card-title-wrap">
                                <div class="prod-card-icon-box purple">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="13.5" cy="6.5" r=".5"></circle>
                                        <circle cx="17.5" cy="10.5" r=".5"></circle>
                                        <circle cx="8.5" cy="7.5" r=".5"></circle>
                                        <circle cx="6.5" cy="12.5" r=".5"></circle>
                                        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C22 6.5 17.5 2 12 2z"></path>
                                    </svg>
                                </div>
                                <div class="prod-card-headings">
                                    <h3>3. Color Themes & Visual Accents</h3>
                                    <p>Select healthcare color presets or fine-tune exact hex color codes for crisp printing</p>
                                </div>
                            </div>
                            <span class="prod-badge-tag" style="background:#f3e8ff; color:#7e22ce;">PALETTE</span>
                        </div>

                        <!-- Hidden select for form submission synced with visual cards -->
                        <input type="hidden" name="theme_preset" id="theme_preset_input" value="<?= e($activeThemeKey) ?>">

                        <!-- Visual Theme Presets Grid -->
                        <div style="font-size:12.5px; font-weight:700; color:#334155; margin-bottom:10px;">Select Curated Medical Palette:</div>
                        <div class="theme-swatch-grid">
                            <?php foreach ($themePresets as $k => $tp): 
                                $isActive = ($activeThemeKey === $k);
                            ?>
                                <div class="theme-swatch-card <?= $isActive ? 'is-active' : '' ?>" onclick="pickThemePreset('<?= e($k) ?>', this)">
                                    <div class="theme-swatch-dots">
                                        <span class="swatch-circle" style="background:<?= e($tp['primary']) ?>" title="Primary"></span>
                                        <span class="swatch-circle" style="background:<?= e($tp['secondary']) ?>" title="Secondary"></span>
                                        <span class="swatch-circle" style="background:<?= e($tp['accent']) ?>" title="Accent"></span>
                                        <span class="swatch-circle" style="background:<?= e($tp['border']) ?>" title="Border"></span>
                                    </div>
                                    <div class="theme-swatch-name">
                                        <span><?= e($tp['name']) ?></span>
                                        <?php if ($isActive): ?>
                                            <span class="active-check" style="color:#087f6c; font-size:14px;">✓</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Fine-Tune Custom Hex Codes -->
                        <div style="font-size:12.5px; font-weight:700; color:#334155; margin:16px 0 10px;">Fine-Tune Individual Colors:</div>
                        <div class="color-picker-box">
                            <div class="color-picker-item">
                                <span>Primary Header</span>
                                <div class="color-input-wrap">
                                    <input type="color" id="color_primary" value="<?= e($tplTheme['primary']) ?>" oninput="syncColorHex(this, 'theme_primary')">
                                    <input type="text" name="theme_primary" id="theme_primary" value="<?= e($tplTheme['primary']) ?>" oninput="syncColorPicker(this, 'color_primary')">
                                </div>
                            </div>

                            <div class="color-picker-item">
                                <span>Secondary Ribbon</span>
                                <div class="color-input-wrap">
                                    <input type="color" id="color_secondary" value="<?= e($tplTheme['secondary']) ?>" oninput="syncColorHex(this, 'theme_secondary')">
                                    <input type="text" name="theme_secondary" id="theme_secondary" value="<?= e($tplTheme['secondary']) ?>" oninput="syncColorPicker(this, 'color_secondary')">
                                </div>
                            </div>

                            <div class="color-picker-item">
                                <span>Accent Bar</span>
                                <div class="color-input-wrap">
                                    <input type="color" id="color_accent" value="<?= e($tplTheme['accent']) ?>" oninput="syncColorHex(this, 'theme_accent')">
                                    <input type="text" name="theme_accent" id="theme_accent" value="<?= e($tplTheme['accent']) ?>" oninput="syncColorPicker(this, 'color_accent')">
                                </div>
                            </div>

                            <div class="color-picker-item">
                                <span>Table Borders</span>
                                <div class="color-input-wrap">
                                    <input type="color" id="color_border" value="<?= e($tplTheme['border']) ?>" oninput="syncColorHex(this, 'theme_border')">
                                    <input type="text" name="theme_border" id="theme_border" value="<?= e($tplTheme['border']) ?>" oninput="syncColorPicker(this, 'color_border')">
                                </div>
                            </div>

                            <div class="color-picker-item">
                                <span>Section Headings</span>
                                <div class="color-input-wrap">
                                    <input type="color" id="color_heading" value="<?= e($tplTheme['heading']) ?>" oninput="syncColorHex(this, 'theme_heading')">
                                    <input type="text" name="theme_heading" id="theme_heading" value="<?= e($tplTheme['heading']) ?>" oninput="syncColorPicker(this, 'color_heading')">
                                </div>
                            </div>

                            <div class="color-picker-item">
                                <span>Text Color</span>
                                <div class="color-input-wrap">
                                    <input type="color" id="color_text" value="<?= e($tplTheme['text']) ?>" oninput="syncColorHex(this, 'theme_text')">
                                    <input type="text" name="theme_text" id="theme_text" value="<?= e($tplTheme['text']) ?>" oninput="syncColorPicker(this, 'color_text')">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- CARD 4: DEFAULT PRINT MODE (1 PAGE VS 2 PAGES)           -->
                    <!-- ========================================================= -->
                    <div class="prod-card">
                        <div class="prod-card-head">
                            <div class="prod-card-title-wrap">
                                <div class="prod-card-icon-box amber">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                </div>
                                <div class="prod-card-headings">
                                    <h3>4. Default Print Mode (1 Page vs 2 Pages)</h3>
                                    <p>Choose the default print output when reception prints an OPD registration slip</p>
                                </div>
                            </div>
                            <span class="prod-badge-tag" style="background:#fef3c7; color:#b45309;">PRINT FLOW</span>
                        </div>

                        <div class="print-mode-cards">
                            <label class="print-mode-opt <?= $defaultPrintPages === '1' ? 'is-selected' : '' ?>" onclick="selectPrintMode('1', this)">
                                <input type="radio" name="default_print_pages" value="1" <?= $defaultPrintPages === '1' ? 'checked' : '' ?>>
                                <div class="print-mode-desc">
                                    <strong>1-Page: Fast OPD Registration Slip</strong>
                                    <p>Single A4 slip containing hospital branding, patient demographics, vitals box, and doctor details. Recommended for standard quick check-ins.</p>
                                    <span class="badge-tag-pill code" style="margin-top:8px; display:inline-block;">RECOMMENDED FOR MOST HOSPITALS</span>
                                </div>
                            </label>

                            <label class="print-mode-opt <?= $defaultPrintPages === '2' ? 'is-selected' : '' ?>" onclick="selectPrintMode('2', this)">
                                <input type="radio" name="default_print_pages" value="2" <?= $defaultPrintPages === '2' ? 'checked' : '' ?>>
                                <div class="print-mode-desc">
                                    <strong>2-Pages: Slip + Blank Consultation Sheet</strong>
                                    <p>Page 1 prints registration slip; Page 2 prints a full blank watermarked consultation paper for doctor's handwritten clinical history and examination.</p>
                                    <span class="badge-tag-pill default" style="margin-top:8px; display:inline-block;">DETAILED CLINICAL NOTES</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- CARD 5: WATERMARK & BACKGROUND ACCENT                     -->
                    <!-- ========================================================= -->
                    <div class="prod-card">
                        <div class="prod-card-head">
                            <div class="prod-card-title-wrap">
                                <div class="prod-card-icon-box sky">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                                    </svg>
                                </div>
                                <div class="prod-card-headings">
                                    <h3>5. Security Watermark & Header Accent Bar</h3>
                                    <p>Control background hospital logo watermark transparency and positioning</p>
                                </div>
                            </div>
                            <span class="prod-badge-tag" style="background:#e0f2fe; color:#0369a1;">WATERMARK</span>
                        </div>

                        <div class="prod-grid">
                            <!-- Watermark Master Toggle -->
                            <div class="prod-field span-2">
                                <label class="ios-toggle-wrap">
                                    <div class="ios-toggle-left">
                                        <span class="ios-toggle-title">Enable Background Logo Watermark</span>
                                        <span class="ios-toggle-desc">Renders a subtle, non-intrusive hospital logo watermark behind doctor consultation notes</span>
                                    </div>
                                    <div class="ios-switch">
                                        <input type="checkbox" name="show_watermark" value="1" <?= !empty($cfg['show_watermark']) ? 'checked' : '' ?>>
                                        <span class="ios-slider"></span>
                                    </div>
                                </label>
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Watermark Position on Paper</label>
                                <select name="watermark_position" class="prod-select">
                                    <option value="bottom-right" <?= ($cfg['watermark_position'] ?? '') === 'bottom-right' ? 'selected' : '' ?>>Bottom Right (Standard)</option>
                                    <option value="center" <?= ($cfg['watermark_position'] ?? '') === 'center' ? 'selected' : '' ?>>Center of Page</option>
                                    <option value="top-right" <?= ($cfg['watermark_position'] ?? '') === 'top-right' ? 'selected' : '' ?>>Top Right Corner</option>
                                </select>
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">
                                    <span>Watermark Opacity</span>
                                    <span class="slider-badge-val" id="wm_op_badge"><?= $wmOpacityPct ?>%</span>
                                </label>
                                <div class="slider-container">
                                    <input type="range" name="watermark_opacity" min="2" max="30" value="<?= $wmOpacityPct ?>" oninput="document.getElementById('wm_op_badge').textContent = this.value + '%'">
                                </div>
                                <small style="color:#64748b; font-size:11.5px; margin-top:3px;">Recommended: 5% - 8% for clean laser printer legibility.</small>
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">
                                    <span>Watermark Size (px)</span>
                                    <span class="slider-badge-val" id="wm_sz_badge"><?= $wmSizeVal ?> px</span>
                                </label>
                                <div class="slider-container">
                                    <input type="range" name="watermark_size" min="40" max="180" value="<?= $wmSizeVal ?>" oninput="document.getElementById('wm_sz_badge').textContent = this.value + ' px'">
                                </div>
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">
                                    <span>Accent Bar Height (px)</span>
                                    <span class="slider-badge-val" id="acc_ht_badge"><?= $accentHeightVal ?> px</span>
                                </label>
                                <div class="slider-container">
                                    <input type="range" name="accent_height" min="15" max="60" value="<?= $accentHeightVal ?>" oninput="document.getElementById('acc_ht_badge').textContent = this.value + ' px'">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- CARD 6: PATIENT INFORMATION FIELDS & CUSTOM LABELS        -->
                    <!-- ========================================================= -->
                    <div class="prod-card">
                        <div class="prod-card-head">
                            <div class="prod-card-title-wrap">
                                <div class="prod-card-icon-box green">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <div class="prod-card-headings">
                                    <h3>6. Patient Information Fields & Custom Labels</h3>
                                    <p>Toggle field visibility on printed slip and customize exact label names</p>
                                </div>
                            </div>
                            <span class="prod-badge-tag" style="background:#dcfce7; color:#15803d;">DEMOGRAPHICS</span>
                        </div>

                        <div class="field-toggle-grid">
                            <?php
                            $metaFields = [
                                'uhid' => ['UHID', 'lbl_uhid', 'show_uhid'],
                                'name' => ['Patient Name', 'lbl_name', 'show_name'],
                                'age_sex' => ['Age / Sex', 'lbl_age_sex', 'show_age_sex'],
                                'guardian' => ['Guardian / S/O / D/O', 'lbl_guardian', 'show_guardian'],
                                'contact' => ['Contact Number', 'lbl_contact', 'show_contact'],
                                'address' => ['Address', 'lbl_address', 'show_address'],
                                'bill' => ['Bill Number', 'lbl_bill', 'show_bill'],
                                'date' => ['Visit Date & Time', 'lbl_date', 'show_date'],
                                'panel' => ['Billing Panel / TPA', 'lbl_panel', 'show_panel'],
                                'dept' => ['Doctor Department', 'lbl_dept', 'show_dept'],
                                'room' => ['Room / Cabin No', 'lbl_room', 'show_room'],
                                'app' => ['Appointment Number', 'lbl_app', 'show_app'],
                            ];
                            foreach ($metaFields as $fKey => $fMeta):
                            ?>
                                <div class="field-micro-card">
                                    <div class="input-col">
                                        <span><?= e($fMeta[0]) ?></span>
                                        <input type="text" name="<?= $fMeta[1] ?>" value="<?= e($cfg[$fMeta[1]] ?? '') ?>" placeholder="<?= e($fMeta[0]) ?>">
                                    </div>
                                    <div class="ios-switch" title="Toggle visibility on print slip">
                                        <input type="checkbox" name="<?= $fMeta[2] ?>" value="1" <?= !empty($cfg[$fMeta[2]]) ? 'checked' : '' ?>>
                                        <span class="ios-slider"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- CARD 7: CLINICAL VITALS TABLE                             -->
                    <!-- ========================================================= -->
                    <div class="prod-card">
                        <div class="prod-card-head">
                            <div class="prod-card-title-wrap">
                                <div class="prod-card-icon-box red">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                    </svg>
                                </div>
                                <div class="prod-card-headings">
                                    <h3>7. Clinical Vitals Table & Measurement Units</h3>
                                    <p>Configure patient vitals table parameters, custom headers, and measurement units</p>
                                </div>
                            </div>
                            <span class="prod-badge-tag" style="background:#fee2e2; color:#b91c1c;">VITALS</span>
                        </div>

                        <!-- Vitals Master Toggle -->
                        <div style="margin-bottom:16px;">
                            <label class="ios-toggle-wrap">
                                <div class="ios-toggle-left">
                                    <span class="ios-toggle-title">Show Clinical Vitals Table on Consultation Slip</span>
                                    <span class="ios-toggle-desc">Displays a dedicated 8-parameter vitals grid (BP, Pulse, Temp, Weight, Height, BMI, Allergies, Pain)</span>
                                </div>
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vitals" value="1" <?= !empty($cfg['show_vitals']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </label>
                        </div>

                        <div class="prod-grid cols-2">
                            <div class="vital-micro-card">
                                <span class="param-name">Height</span>
                                <input type="text" name="lbl_height" class="prod-input param-label-in" value="<?= e($cfg['lbl_height']) ?>" placeholder="Label">
                                <input type="text" name="unit_height" class="prod-input param-unit-in" value="<?= e($cfg['unit_height']) ?>" placeholder="Unit">
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vital_height" value="1" <?= !empty($cfg['show_vital_height']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </div>

                            <div class="vital-micro-card">
                                <span class="param-name">Weight</span>
                                <input type="text" name="lbl_weight" class="prod-input param-label-in" value="<?= e($cfg['lbl_weight']) ?>" placeholder="Label">
                                <input type="text" name="unit_weight" class="prod-input param-unit-in" value="<?= e($cfg['unit_weight']) ?>" placeholder="Unit">
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vital_weight" value="1" <?= !empty($cfg['show_vital_weight']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </div>

                            <div class="vital-micro-card">
                                <span class="param-name">Blood Pressure</span>
                                <input type="text" name="lbl_bp" class="prod-input param-label-in" value="<?= e($cfg['lbl_bp']) ?>" placeholder="Label">
                                <input type="text" name="unit_bp" class="prod-input param-unit-in" value="<?= e($cfg['unit_bp']) ?>" placeholder="Unit">
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vital_bp" value="1" <?= !empty($cfg['show_vital_bp']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </div>

                            <div class="vital-micro-card">
                                <span class="param-name">Pulse Rate</span>
                                <input type="text" name="lbl_pulse" class="prod-input param-label-in" value="<?= e($cfg['lbl_pulse']) ?>" placeholder="Label">
                                <input type="text" name="unit_pulse" class="prod-input param-unit-in" value="<?= e($cfg['unit_pulse']) ?>" placeholder="Unit">
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vital_pulse" value="1" <?= !empty($cfg['show_vital_pulse']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </div>

                            <div class="vital-micro-card">
                                <span class="param-name">Body Temp</span>
                                <input type="text" name="lbl_temp" class="prod-input param-label-in" value="<?= e($cfg['lbl_temp']) ?>" placeholder="Label">
                                <input type="text" name="unit_temp" class="prod-input param-unit-in" value="<?= e($cfg['unit_temp']) ?>" placeholder="Unit">
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vital_temp" value="1" <?= !empty($cfg['show_vital_temp']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </div>

                            <div class="vital-micro-card">
                                <span class="param-name">BMI Index</span>
                                <input type="text" name="lbl_bmi" class="prod-input param-label-in" value="<?= e($cfg['lbl_bmi']) ?>" placeholder="Label">
                                <span style="width:70px; font-size:12px; color:#94a3b8; text-align:center;">Auto</span>
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vital_bmi" value="1" <?= !empty($cfg['show_vital_bmi']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </div>

                            <div class="vital-micro-card">
                                <span class="param-name">Pain Score</span>
                                <input type="text" name="lbl_pain" class="prod-input param-label-in" value="<?= e($cfg['lbl_pain']) ?>" placeholder="Label">
                                <span style="width:70px; font-size:12px; color:#94a3b8; text-align:center;">0-10</span>
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vital_pain" value="1" <?= !empty($cfg['show_vital_pain']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </div>

                            <div class="vital-micro-card">
                                <span class="param-name">Allergies</span>
                                <input type="text" name="lbl_allergies" class="prod-input param-label-in" value="<?= e($cfg['lbl_allergies']) ?>" placeholder="Label">
                                <span style="width:70px; font-size:12px; color:#94a3b8; text-align:center;">Text</span>
                                <div class="ios-switch">
                                    <input type="checkbox" name="show_vital_allergies" value="1" <?= !empty($cfg['show_vital_allergies']) ? 'checked' : '' ?>>
                                    <span class="ios-slider"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================================= -->
                    <!-- CARD 8: FOOTER CONTACTS, VALIDITY & SIGNATURE             -->
                    <!-- ========================================================= -->
                    <div class="prod-card">
                        <div class="prod-card-head">
                            <div class="prod-card-title-wrap">
                                <div class="prod-card-icon-box rose">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </div>
                                <div class="prod-card-headings">
                                    <h3>8. Footer Contacts, Validity Notice & Legal Information</h3>
                                    <p>Hospital contact numbers, website, emergency landline, and validity note</p>
                                </div>
                            </div>
                            <span class="prod-badge-tag" style="background:#ffe4e6; color:#e11d48;">FOOTER & LEGAL</span>
                        </div>

                        <div class="prod-grid">
                            <!-- Validity Note with Presets -->
                            <div class="prod-field span-2">
                                <label class="prod-label">Prescription Validity Note</label>
                                <input type="text" id="validityNoteInput" name="validity_note" class="prod-input" value="<?= e($cfg['validity_note']) ?>" placeholder="e.g. Bill is valid for 3 days Including date of Billing.">
                                <div class="quick-preset-row">
                                    <span style="font-size:11.5px; color:#64748b;">Quick Presets:</span>
                                    <button type="button" class="badge-preset-btn" onclick="setValidityNote('Bill is valid for 3 days Including date of Billing.')">3 Days Valid</button>
                                    <button type="button" class="badge-preset-btn" onclick="setValidityNote('Bill is valid for 7 days Including date of Billing.')">7 Days Valid</button>
                                    <button type="button" class="badge-preset-btn" onclick="setValidityNote('Valid for single OPD consultation on billing date only.')">Same Day Only</button>
                                    <button type="button" class="badge-preset-btn" onclick="setValidityNote('Valid for initial consultation + 1 complimentary follow-up within 5 days.')">5 Days Follow-up</button>
                                </div>
                            </div>

                            <!-- Doctor Signature Box -->
                            <div class="prod-field">
                                <label class="prod-label">Doctor Signature / Stamp Title</label>
                                <input type="text" name="lbl_signature" class="prod-input" value="<?= e($cfg['lbl_signature'] ?? "Doctor's Signature / Stamp") ?>" placeholder="Doctor's Signature / Stamp">
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Signature Box Visibility</label>
                                <label class="ios-toggle-wrap" style="padding:8px 14px;">
                                    <span class="ios-toggle-title">Show Signature & Stamp Box</span>
                                    <div class="ios-switch">
                                        <input type="checkbox" name="show_signature_box" value="1" <?= !empty($cfg['show_signature_box']) ? 'checked' : '' ?>>
                                        <span class="ios-slider"></span>
                                    </div>
                                </label>
                            </div>

                            <!-- Hospital Physical Address -->
                            <div class="prod-field span-2">
                                <label class="prod-label">Hospital Physical Address (Appears in Footer)</label>
                                <input type="text" name="hospital_address" class="prod-input" value="<?= e($cfg['hospital_address']) ?>" placeholder="e.g. Hospital.: Sector 119, Noida - 201305, U.P., India">
                            </div>

                            <!-- Reg Office Address -->
                            <div class="prod-field span-2">
                                <label class="prod-label">Registered Corporate Office & CIN Number</label>
                                <textarea name="reg_office" class="prod-textarea" rows="2" placeholder="Reg. Office address, CIN, Registration numbers..."><?= e($cfg['reg_office']) ?></textarea>
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">WhatsApp Helpdesk Contact</label>
                                <input type="tel" name="phone_whatsapp" class="prod-input" value="<?= e($cfg['phone_whatsapp']) ?>" placeholder="+91 99937 77444">
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Emergency Landline / Desk Phone</label>
                                <input type="tel" name="phone_landline" class="prod-input" value="<?= e($cfg['phone_landline']) ?>" placeholder="+91 120 4154949">
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Official Email Address</label>
                                <input type="email" name="email" class="prod-input" value="<?= e($cfg['email']) ?>" placeholder="info@motherlandhospital.com">
                            </div>

                            <div class="prod-field">
                                <label class="prod-label">Official Hospital Website</label>
                                <input type="text" name="website" class="prod-input" value="<?= e($cfg['website']) ?>" placeholder="www.motherlandhospital.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================= -->
                <!-- FLOATING GLASSMORPHIC ACTION DOCK                             -->
                <!-- ============================================================= -->
                <div class="sticky-dock-bar">
                    <div class="dock-left-actions">
                        <button type="submit" class="btn-dock-save">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <span>Save Template Settings</span>
                        </button>

                        <a href="template_editor.php?template_id=<?= $selectedCodeTpl['id'] ?>" class="btn-dock-link">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M9 3v18"></path></svg>
                            <span>Open Layout Editor</span>
                        </a>
                    </div>

                    <div class="dock-right-actions">
                        <a href="print_opd.php?template_id=<?= $selectedCodeTpl['id'] ?>&pages=1" target="_blank" class="btn-dock-link preview">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                            <span>Preview 1-Page</span>
                        </a>
                        <a href="print_opd.php?template_id=<?= $selectedCodeTpl['id'] ?>&pages=2" target="_blank" class="btn-dock-link preview">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <span>Preview 2-Pages</span>
                        </a>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="prod-card" style="text-align:center; padding:50px 20px;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" style="margin:0 auto 16px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <h3 style="margin:0 0 8px; color:#0f172a;">No Digital Code Template Selected</h3>
                <p style="color:#64748b; font-size:13.5px; margin:0 0 20px;">Create your first digital vector template or select one from the All Templates tab.</p>
                <button type="button" class="btn-tpl-create primary" onclick="openCreateCodeModal()" style="margin:0 auto;">
                    + Create Digital Template
                </button>
            </div>
        <?php endif; ?>

    <!-- ===================================================================== -->
    <!-- TAB 3: PRE-PRINTED IMAGE TEMPLATES                                    -->
    <!-- ===================================================================== -->
    <?php elseif ($activeTab === 'images'): ?>
        <div class="tpl-cards-grid">
            <?php foreach ($imageTemplates as $imgTpl): 
                $isDefault = ((int)$imgTpl['id'] === $defaultTemplateId);
            ?>
                <div class="tpl-overview-card <?= $isDefault ? 'is-default' : '' ?> <?= empty($imgTpl['active']) ? 'is-inactive' : '' ?>">
                    <div>
                        <div class="card-top-header">
                            <div style="width:68px; height:88px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <img src="<?= e($imgTpl['file_path']) ?>" alt="Thumbnail" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <div class="card-title-details">
                                <h3><?= e($imgTpl['name']) ?></h3>
                                <div class="card-badges-row">
                                    <span class="badge-tag-pill image">🖼️ Pre-printed Scan</span>
                                    <?php if ($isDefault): ?>
                                        <span class="badge-tag-pill default">★ Default Template</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:12px; color:#64748b; margin-top:6px;">
                                    Resolution: <strong><?= e($imgTpl['width'] . ' × ' . $imgTpl['height']) ?> px</strong>
                                </div>
                            </div>
                        </div>

                        <div class="card-specs-list" style="margin-top:14px;">
                            <div><span>Layout Position Method:</span> <strong>X/Y Drag & Drop Coordinates</strong></div>
                            <div><span>Reception Status:</span> <strong style="color:<?= !empty($imgTpl['active']) ? '#15803d' : '#94a3b8' ?>"><?= !empty($imgTpl['active']) ? '● Active' : '○ Disabled' ?></strong></div>
                        </div>
                    </div>

                    <div class="card-bottom-actions">
                        <a href="template_editor.php?template_id=<?= $imgTpl['id'] ?>" class="action-btn-pill primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M9 3v18"></path></svg>
                            <span>Open Layout Editor &rarr;</span>
                        </a>

                        <div style="display:flex; gap:6px; align-items:center;">
                            <?php if (!$isDefault): ?>
                                <form method="post" action="templates.php?tab=images" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="set_default_id" value="<?= $imgTpl['id'] ?>">
                                    <button type="submit" class="action-btn-pill">Make Default</button>
                                </form>
                            <?php endif; ?>

                            <form method="post" action="templates.php?tab=images" style="display:inline;">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="toggle_id" value="<?= $imgTpl['id'] ?>">
                                <button type="submit" class="action-btn-pill"><?= !empty($imgTpl['active']) ? 'Block' : 'Activate' ?></button>
                            </form>

                            <?php if (!$isDefault): ?>
                                <form method="post" action="templates.php?tab=images" style="display:inline;" onsubmit="return confirm('Delete this scanned template?');">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_template">
                                    <input type="hidden" name="id" value="<?= $imgTpl['id'] ?>">
                                    <button type="submit" class="action-btn-pill danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($imageTemplates)): ?>
                <div class="prod-card span-2" style="text-align:center; padding:50px 20px;">
                    <h3 style="margin:0 0 8px; color:#0f172a;">No Pre-Printed Image Templates</h3>
                    <p style="color:#64748b; font-size:13px; margin:0 0 20px;">Upload a straight flatbed photo/scan of your physical prescription pad to print text directly onto pre-printed blank lines.</p>
                    <button type="button" class="btn-tpl-create primary" onclick="openUploadImageModal()" style="margin:0 auto;">
                        + Upload First Image Template
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: Create Digital Code Template                                    -->
<!-- ========================================================================= -->
<div id="createCodeModal" class="tpl-modal-backdrop" style="display:none;" role="dialog" aria-modal="true">
    <div class="tpl-modal-box">
        <div class="tpl-modal-header">
            <h3>Create Digital Code Template</h3>
            <button type="button" class="btn-modal-close-icon" onclick="closeCreateCodeModal()">&times;</button>
        </div>
        <form method="post" action="templates.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="create_code_template">

            <div class="tpl-modal-body">
                <div class="prod-field">
                    <label class="prod-label" for="codeTplName">Template Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" id="codeTplName" class="prod-input" placeholder="e.g. City Care Digital OPD Slip" required>
                </div>

                <div class="prod-field">
                    <label class="prod-label" for="codeTplPaper">Paper Size Format</label>
                    <select name="page_size" id="codeTplPaper" class="prod-select">
                        <option value="A4" selected>A4 (210 × 297 mm) — Standard Hospital Size</option>
                        <option value="A5">A5 (148 × 210 mm) — Compact Prescription Size</option>
                        <option value="Letter">US Letter (8.5 × 11 in)</option>
                    </select>
                </div>

                <div class="prod-field">
                    <label class="prod-label" for="codeTplTheme">Color Theme Palette</label>
                    <select name="theme_preset" id="codeTplTheme" class="prod-select">
                        <option value="green" selected>Mint Forest Green (Healthcare Default)</option>
                        <option value="blue">Royal Sapphire Blue</option>
                        <option value="crimson">Rose Crimson Red</option>
                        <option value="violet">Royal Amethyst Purple</option>
                        <option value="amber">Warm Medical Amber</option>
                        <option value="slate">Clinical Slate Monochrome</option>
                    </select>
                </div>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; font-size:12px; color:#475569; line-height:1.5;">
                    💡 <strong>What is a Digital Code Template?</strong><br>
                    It automatically renders clean hospital headers, logo, vitals table, doctor info, and patient details with vector clarity. You can customize all colors, margins and 1/2-page options.
                </div>
            </div>

            <div class="tpl-modal-footer">
                <button type="button" class="btn-dock-link" onclick="closeCreateCodeModal()">Cancel</button>
                <button type="submit" class="btn-tpl-create primary">Create & Customize</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: Upload Pre-Printed Image Template                               -->
<!-- ========================================================================= -->
<div id="uploadImageModal" class="tpl-modal-backdrop" style="display:none;" role="dialog" aria-modal="true">
    <div class="tpl-modal-box" style="max-width:580px;">
        <div class="tpl-modal-header">
            <h3>Upload Pre-Printed Physical Pad Scan</h3>
            <button type="button" class="btn-modal-close-icon" onclick="closeUploadImageModal()">&times;</button>
        </div>
        <form method="post" action="templates.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="upload_image_template">

            <div class="tpl-modal-body">
                <div class="prod-field">
                    <label class="prod-label" for="imgTplName">Template Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" id="imgTplName" class="prod-input" placeholder="e.g. Apollo Hospital A5 Prescription Pad" required>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                    <div class="prod-field">
                        <label class="prod-label" for="imgTplPaper">Stationery Paper Size</label>
                        <select name="page_size" id="imgTplPaper" class="prod-select" onchange="toggleCustomDimensionsImg(this.value)">
                            <option value="A4">A4 (210 × 297 mm) — Standard Full Sheet</option>
                            <option value="A5" selected>A5 (148 × 210 mm) — Standard Hospital Pad (Most Popular)</option>
                            <option value="Letter">US Letter (8.5 × 11 in)</option>
                            <option value="Custom">Custom Cut Stationery Size</option>
                        </select>
                    </div>

                    <div class="prod-field">
                        <label class="prod-label" for="imgTplOrientation">Paper Orientation</label>
                        <select name="orientation" id="imgTplOrientation" class="prod-select">
                            <option value="portrait" selected>Portrait (Vertical Pad)</option>
                            <option value="landscape">Landscape (Horizontal Pad)</option>
                        </select>
                    </div>
                </div>

                <!-- Custom Dimensions Panel -->
                <div id="customDimensionsPanelImg" style="display:none; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:12px;">
                    <span style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:8px;">Custom Paper Dimensions (Millimeters)</span>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <div>
                            <label style="font-size:11px; color:#64748b; display:block; margin-bottom:2px;">Width (mm)</label>
                            <input type="number" step="0.5" name="custom_width" value="148" class="prod-input">
                        </div>
                        <div>
                            <label style="font-size:11px; color:#64748b; display:block; margin-bottom:2px;">Height (mm)</label>
                            <input type="number" step="0.5" name="custom_height" value="210" class="prod-input">
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                    <div class="prod-field">
                        <label class="prod-label" for="imgTplFieldMode">Field Text Format</label>
                        <select name="field_mode" id="imgTplFieldMode" class="prod-select">
                            <option value="val_only" selected>Value Only (Recommended for Pre-printed Pads)</option>
                            <option value="label_val">Label + Value (e.g. "UHID: 1002")</option>
                        </select>
                    </div>

                    <div class="prod-field">
                        <label class="prod-label" for="imgTplFontSize">Default Font Size</label>
                        <select name="font_size" id="imgTplFontSize" class="prod-select">
                            <option value="10">10 px — Compact</option>
                            <option value="11" selected>11 px — Balanced Standard</option>
                            <option value="12">12 px — Large & Clear</option>
                            <option value="14">14 px — High Visibility</option>
                        </select>
                    </div>
                </div>

                <div class="prod-field">
                    <label class="prod-label" for="imgTplFile">Scanned Pad Image / Photo (JPG, PNG, WEBP) <span style="color:#ef4444;">*</span></label>
                    <input type="file" name="template_file" id="imgTplFile" accept="image/jpeg,image/png,image/webp" class="prod-input" onchange="previewPadScan(this)" required>
                    <small style="color:#64748b; font-size:11.5px; margin-top:3px;">Max 12 MB. Upload a straight flatbed scan or top-down camera photo of your blank printed prescription pad.</small>
                    
                    <div id="imgTplPreviewWrap" style="display:none; margin-top:8px; align-items:center; gap:12px; background:#f1f5f9; padding:8px 12px; border-radius:6px;">
                        <img id="imgTplPreviewImg" src="" alt="Pad Preview" style="max-height:60px; border-radius:4px; border:1px solid #cbd5e1; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                        <span style="font-size:12px; color:#475569; font-weight:600;">Image ready for upload</span>
                    </div>
                </div>

                <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:12px; font-size:12px; color:#0369a1; line-height:1.5;">
                    💡 <strong>Physical Pad Printing Workflow:</strong><br>
                    After upload, the Drag-and-Drop Studio opens where you can drag patient fields directly onto your pad's printed lines with millimeter arrow nudge controls. During OPD printing, the printer prints clean text right onto your physical stationery sheets.
                </div>
            </div>

            <div class="tpl-modal-footer">
                <button type="button" class="btn-dock-link" onclick="closeUploadImageModal()">Cancel</button>
                <button type="submit" class="btn-tpl-create primary">Upload & Open Layout Studio &rarr;</button>
            </div>
        </form>
    </div>
</div>

<script>
// JSON Presets for theme switching
const THEME_PRESETS = <?= json_encode($themePresets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function pickThemePreset(presetKey, cardElem) {
    document.querySelectorAll('.theme-swatch-card').forEach(c => {
        c.classList.remove('is-active');
        const check = c.querySelector('.active-check');
        if (check) check.remove();
    });

    cardElem.classList.add('is-active');
    const nameEl = cardElem.querySelector('.theme-swatch-name');
    if (nameEl && !nameEl.querySelector('.active-check')) {
        const span = document.createElement('span');
        span.className = 'active-check';
        span.style.color = '#087f6c';
        span.style.fontSize = '14px';
        span.textContent = '✓';
        nameEl.appendChild(span);
    }

    document.getElementById('theme_preset_input').value = presetKey;

    if (THEME_PRESETS[presetKey]) {
        const tp = THEME_PRESETS[presetKey];
        updateColorField('theme_primary', 'color_primary', tp.primary);
        updateColorField('theme_secondary', 'color_secondary', tp.secondary);
        updateColorField('theme_accent', 'color_accent', tp.accent);
        updateColorField('theme_border', 'color_border', tp.border);
        updateColorField('theme_heading', 'color_heading', tp.heading);
        updateColorField('theme_text', 'color_text', tp.text);
    }
}

function updateColorField(textId, pickerId, val) {
    const textEl = document.getElementById(textId);
    const pickerEl = document.getElementById(pickerId);
    if (textEl && val) textEl.value = val;
    if (pickerEl && val) pickerEl.value = val;
}

function syncColorHex(colorPicker, textInputId) {
    const textInput = document.getElementById(textInputId);
    if (textInput) textInput.value = colorPicker.value;
}

function syncColorPicker(textInput, colorPickerId) {
    const colorPicker = document.getElementById(colorPickerId);
    if (colorPicker && /^#[0-9A-Fa-f]{6}$/.test(textInput.value.trim())) {
        colorPicker.value = textInput.value.trim();
    }
}

function toggleCustomDimensions(val) {
    const p = document.getElementById('customDimensionsPanel');
    if (p) p.style.display = (val === 'Custom') ? 'block' : 'none';
}

function toggleCustomDimensionsImg(val) {
    const p = document.getElementById('customDimensionsPanelImg');
    if (p) p.style.display = (val === 'Custom') ? 'block' : 'none';
}

function previewPadScan(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const wrap = document.getElementById('imgTplPreviewWrap');
            const img = document.getElementById('imgTplPreviewImg');
            if (img) img.src = e.target.result;
            if (wrap) wrap.style.display = 'flex';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function selectPrintMode(val, labelElem) {
    document.querySelectorAll('.print-mode-opt').forEach(opt => opt.classList.remove('is-selected'));
    labelElem.classList.add('is-selected');
    const radio = labelElem.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

function setValidityNote(text) {
    const el = document.getElementById('validityNoteInput');
    if (el) {
        el.value = text;
        el.focus();
    }
}

function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('logoPreviewImg');
            if (img) img.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openCreateCodeModal() {
    document.getElementById('createCodeModal').style.display = 'flex';
    document.getElementById('codeTplName').focus();
}

function closeCreateCodeModal() {
    document.getElementById('createCodeModal').style.display = 'none';
}

function openUploadImageModal() {
    document.getElementById('uploadImageModal').style.display = 'flex';
    document.getElementById('imgTplName').focus();
}

function closeUploadImageModal() {
    document.getElementById('uploadImageModal').style.display = 'none';
}

window.addEventListener('click', (e) => {
    const m1 = document.getElementById('createCodeModal');
    const m2 = document.getElementById('uploadImageModal');
    if (e.target === m1) closeCreateCodeModal();
    if (e.target === m2) closeUploadImageModal();
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
