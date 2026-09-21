<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/theme_engine.php';

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

$st = db()->prepare('SELECT * FROM templates WHERE id = ?');
$st->execute([$templateId]);
$tpl = $st->fetch();
if (!$tpl) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Template not found']);
    exit;
}

$presets = get_theme_presets();
$presetKey = strtolower(trim((string)($input['theme_preset'] ?? 'green')));
if (!isset($presets[$presetKey])) {
    $presetKey = 'green';
}

$themeConfigJson = null;
if (!empty($input['theme_config']) && is_array($input['theme_config'])) {
    $themeConfigJson = json_encode($input['theme_config']);
} elseif (!empty($input['theme_config_json'])) {
    $themeConfigJson = is_string($input['theme_config_json']) ? $input['theme_config_json'] : json_encode($input['theme_config_json']);
}

$up = db()->prepare('UPDATE templates SET theme_preset = ?, theme_config_json = ? WHERE id = ?');
$up->execute([$presetKey, $themeConfigJson, $templateId]);

$updatedTpl = array_merge($tpl, [
    'theme_preset' => $presetKey,
    'theme_config_json' => $themeConfigJson
]);
$resolvedTheme = get_template_theme($updatedTpl);

echo json_encode([
    'ok' => true,
    'message' => 'Template color theme saved successfully',
    'theme' => $resolvedTheme
]);
