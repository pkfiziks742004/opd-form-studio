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

$templateId = (int)($_POST['template_id'] ?? 0);
$assetType = trim((string)($_POST['asset_type'] ?? 'logo'));

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'No file uploaded or upload error occurred']);
    exit;
}

$file = $_FILES['file'];
$maxBytes = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'File size exceeds 5MB limit']);
    exit;
}

$ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
$allowed = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
if (!in_array($ext, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid image format. Allowed: PNG, JPG, WebP, SVG']);
    exit;
}

$uploadDir = dirname(__DIR__) . '/uploads/templates';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
$destPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Failed to save uploaded image']);
    exit;
}

$relPath = 'uploads/templates/' . $filename;

// If templateId is provided and user is admin, update template config
if ($templateId > 0 && $user['role'] === 'admin') {
    $st = db()->prepare('SELECT default_layout_json FROM templates WHERE id = ?');
    $st->execute([$templateId]);
    $tpl = $st->fetch();
    if ($tpl) {
        $curData = json_decode((string)$tpl['default_layout_json'], true);
        if (!is_array($curData)) $curData = [];
        if (!isset($curData['code_config']) || !is_array($curData['code_config'])) {
            $curData['code_config'] = [];
        }
        $curData['code_config']['icon_path'] = $relPath;
        db()->prepare('UPDATE templates SET default_layout_json = ? WHERE id = ?')
            ->execute([json_encode($curData, JSON_UNESCAPED_UNICODE), $templateId]);
    }
}

echo json_encode([
    'ok' => true,
    'file_path' => $relPath,
    'message' => 'Hospital logo uploaded successfully'
]);
