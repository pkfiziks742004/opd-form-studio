<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
header('Content-Type: application/json');
$user = require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}
verify_csrf();

$input = json_decode(file_get_contents('php://input'), true);
$templateId = (int)($input['template_id'] ?? 0);
$layout = $input['layout'] ?? null;
$sections = $input['sections'] ?? null;

if (!$templateId) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid template ID']);
    exit;
}

$clean = [];
if (is_array($layout)) {
    foreach ($layout as $k => $v) {
        if (!is_array($v)) continue;
        if ($k === 'sections') {
            $clean['sections'] = $v;
            continue;
        }
        if (!isset(FIELD_DEFS[$k]) && !str_starts_with((string)$k, 'block_')) continue;
        $fw = trim((string)($v['fontWeight'] ?? '500'));
        if (!in_array($fw, ['normal', '400', '500', '600', 'bold', '700', '800'], true)) $fw = '500';

        $align = trim((string)($v['align'] ?? 'left'));
        if (!in_array($align, ['left', 'center', 'right'], true)) $align = 'left';

        $color = trim((string)($v['color'] ?? '#111827'));
        if (!preg_match('/^#[0-9A-Fa-f]{3,8}$/', $color)) $color = '#111827';

        $clean[$k] = [
            'x' => max(0.0, min(99.0, (float)($v['x'] ?? 0))),
            'y' => max(0.0, min(99.0, (float)($v['y'] ?? 0))),
            'fontSize' => max(8, min(36, (int)($v['fontSize'] ?? 12))),
            'width' => max(30, min(900, (int)($v['width'] ?? 220))),
            'fontWeight' => $fw,
            'align' => $align,
            'color' => $color,
            'showLabel' => !empty($v['showLabel']),
            'visible' => (!isset($v['visible']) || !empty($v['visible']))
        ];
    }
}

if (is_array($sections)) {
    $clean['sections'] = [
        'header_mode' => in_array($sections['header_mode'] ?? '', ['digital', 'blank'], true) ? $sections['header_mode'] : 'digital',
        'header_height' => max(0.0, min(120.0, (float)($sections['header_height'] ?? 36.0))),
        'patient_top' => max(0.0, min(200.0, (float)($sections['patient_top'] ?? 42.0))),
        'patient_style' => in_array($sections['patient_style'] ?? '', ['divider', 'box', 'none'], true) ? $sections['patient_style'] : 'divider',
        'patient_density' => in_array($sections['patient_density'] ?? '', ['compact', 'normal', 'relaxed'], true) ? $sections['patient_density'] : 'normal',
        'patient_font_size' => max(8, min(16, (int)($sections['patient_font_size'] ?? 11))),
        'vitals_top' => max(0.0, min(250.0, (float)($sections['vitals_top'] ?? 78.0))),
        'show_vitals' => !empty($sections['show_vitals']) ? 1 : 0,
        'footer_mode' => in_array($sections['footer_mode'] ?? '', ['digital', 'blank'], true) ? $sections['footer_mode'] : 'digital',
        'footer_height' => max(0.0, min(100.0, (float)($sections['footer_height'] ?? 28.0))),
        'show_signature' => !empty($sections['show_signature']) ? 1 : 0
    ];
}

$code_config = $input['code_config'] ?? null;
if (is_array($code_config)) {
    $cleanCfg = [];
    $allowedKeys = [
        'hospital_name', 'hospital_tagline', 'doc_title', 'doctor_name', 'doctor_dept',
        'font_family', 'icon_path', 'validity_note', 'hospital_address', 'reg_office',
        'cin', 'phone_whatsapp', 'phone_landline', 'email', 'website',
        'show_watermark', 'watermark_opacity', 'watermark_position', 'show_vitals', 'show_header', 'show_title',
        'show_patient_info', 'show_doctor_box', 'show_validity_note', 'show_signature_box', 'lbl_signature', 'show_footer',
        'show_vital_height', 'show_vital_weight', 'show_vital_temp', 'show_vital_pulse',
        'show_vital_pain', 'show_vital_allergies', 'show_vital_bmi', 'show_vital_bp'
    ];
    foreach ($allowedKeys as $ak) {
        if (isset($code_config[$ak])) {
            $cleanCfg[$ak] = trim((string)$code_config[$ak]);
        }
    }
    if (!empty($cleanCfg)) {
        $clean['code_config'] = $cleanCfg;
    }
}

$cleanJson = json_encode($clean, JSON_UNESCAPED_UNICODE);

// Save to user layout
$st = db()->prepare('INSERT INTO template_layouts(template_id, user_id, layout_json) VALUES(?,?,?) ON DUPLICATE KEY UPDATE layout_json=VALUES(layout_json), updated_at=NOW()');
$st->execute([$templateId, $user['id'], $cleanJson]);

// If admin, also update template default_layout_json
if ($user['role'] === 'admin') {
    $curSt = db()->prepare('SELECT default_layout_json FROM templates WHERE id = ?');
    $curSt->execute([$templateId]);
    $curTpl = $curSt->fetch();
    $curData = $curTpl ? json_decode((string)$curTpl['default_layout_json'], true) : [];
    if (!is_array($curData)) $curData = [];
    if (isset($clean['sections'])) {
        $curData['sections'] = $clean['sections'];
    }
    if (isset($clean['code_config'])) {
        $curData['code_config'] = array_merge($curData['code_config'] ?? [], $clean['code_config']);
    }
    foreach ($clean as $ck => $cv) {
        if ($ck !== 'sections' && $ck !== 'code_config') $curData[$ck] = $cv;
    }
    db()->prepare('UPDATE templates SET default_layout_json = ? WHERE id = ?')->execute([json_encode($curData, JSON_UNESCAPED_UNICODE), $templateId]);
}

echo json_encode(['ok' => true]);
