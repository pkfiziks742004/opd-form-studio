<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/page_engine.php';

header('Content-Type: application/json');
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

verify_csrf();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$templateId = (int)($input['template_id'] ?? 0);
if ($templateId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Missing template_id']);
    exit;
}

// Fetch template to verify existence
$st = db()->prepare('SELECT * FROM templates WHERE id = ?');
$st->execute([$templateId]);
$tpl = $st->fetch();
if (!$tpl) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Template not found']);
    exit;
}

$presets = get_paper_presets();

$pageSize = trim((string)($input['page_size'] ?? 'A4'));
if (!isset($presets[$pageSize])) {
    $pageSize = 'A4';
}

$orientation = strtolower(trim((string)($input['orientation'] ?? 'portrait')));
if ($orientation !== 'landscape') {
    $orientation = 'portrait';
}

$unit = strtolower(trim((string)($input['page_unit'] ?? 'mm')));
if (!in_array($unit, ['mm', 'cm', 'in', 'inch'], true)) {
    $unit = 'mm';
}
if ($unit === 'inch') $unit = 'in';

if ($pageSize !== 'Custom' && isset($presets[$pageSize])) {
    $pageWidth = (float)$presets[$pageSize]['width'];
    $pageHeight = (float)$presets[$pageSize]['height'];
    $pageUnit = $presets[$pageSize]['unit'];
} else {
    $pageWidth = max(50.0, min(1000.0, (float)($input['page_width'] ?? 210.0)));
    $pageHeight = max(50.0, min(1500.0, (float)($input['page_height'] ?? 297.0)));
    $pageUnit = $unit;
}

$marginTop = max(0.0, min(100.0, (float)($input['margin_top'] ?? 6.0)));
$marginRight = max(0.0, min(100.0, (float)($input['margin_right'] ?? 12.0)));
$marginBottom = max(0.0, min(100.0, (float)($input['margin_bottom'] ?? 6.0)));
$marginLeft = max(0.0, min(100.0, (float)($input['margin_left'] ?? 12.0)));

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

$upSt = db()->prepare('UPDATE templates SET 
    page_size = ?,
    orientation = ?,
    page_width = ?,
    page_height = ?,
    page_unit = ?,
    margin_top = ?,
    margin_right = ?,
    margin_bottom = ?,
    margin_left = ?,
    page_config_json = ?
    WHERE id = ?');

$upSt->execute([
    $pageSize,
    $orientation,
    $pageWidth,
    $pageHeight,
    $pageUnit,
    $marginTop,
    $marginRight,
    $marginBottom,
    $marginLeft,
    $pageConfigJson,
    $templateId
]);

// Return updated geometry for live UI confirmation
$updatedTpl = array_merge($tpl, [
    'page_size' => $pageSize,
    'orientation' => $orientation,
    'page_width' => $pageWidth,
    'page_height' => $pageHeight,
    'page_unit' => $pageUnit,
    'margin_top' => $marginTop,
    'margin_right' => $marginRight,
    'margin_bottom' => $marginBottom,
    'margin_left' => $marginLeft,
    'page_config_json' => $pageConfigJson
]);

$geometry = get_template_page_config($updatedTpl);

echo json_encode([
    'ok' => true,
    'message' => 'Page settings saved successfully',
    'geometry' => $geometry
]);
