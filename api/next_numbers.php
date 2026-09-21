<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/settings.php';

header('Content-Type: application/json');
$user = require_login();

$date = trim($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

echo json_encode([
    'ok' => true,
    'uhid' => get_next_uhid(),
    'bill_no' => get_next_bill_no(),
    'app_no' => get_next_app_no($date),
    'uhid_prefix' => get_uhid_prefix(),
    'bill_prefix' => get_bill_prefix(),
    'date' => $date
]);
