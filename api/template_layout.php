<?php
require_once dirname(__DIR__) . '/includes/layout.php';
require_once dirname(__DIR__) . '/includes/code_template.php';

header('Content-Type: application/json');
$u = require_login();
$id = (int)($_GET['id'] ?? 0);
$tpl = active_template($id);
if (!$tpl) {
    http_response_code(404);
    echo json_encode(['ok' => false]);
    exit;
}

$isCode = is_code_template($tpl);
$response = [
    'ok' => true,
    'id' => (int)$tpl['id'],
    'name' => $tpl['name'],
    'type' => $isCode ? 'code' : 'image',
    'file' => $tpl['file_path'],
    'layout' => get_layout($tpl, (int)$u['id'])
];

if ($isCode) {
    $response['html'] = render_motherland_opd([], [], true);
}

echo json_encode($response);
