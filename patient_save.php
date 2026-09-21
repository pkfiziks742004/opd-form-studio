<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/sheets.php';

$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: patient_form.php');
    exit;
}
verify_csrf();

$perm = field_permissions((int)$user['id']);

function get_field_val(array $perm, string $key, string $default = ''): string {
    $val = trim($_POST[$key] ?? '');
    if (!empty($perm[$key]['visible'])) {
        return $val !== '' ? $val : $default;
    }
    return $default;
}

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    flash('error', 'Patient name is required.');
    header('Location: patient_form.php');
    exit;
}

$visit = trim($_POST['visit_date'] ?? '') ?: date('Y-m-d');
$visit_time = trim($_POST['visit_time'] ?? '') ?: date('H:i:s');
if (strlen($visit_time) === 5) $visit_time .= ':00';

$uhid = get_field_val($perm, 'uhid', get_next_uhid());
$bill_no = get_field_val($perm, 'bill_no', get_next_bill_no());
$app_no = get_field_val($perm, 'app_no', get_next_app_no($visit));

$data = [
    $uhid,
    $name,
    get_field_val($perm, 'age_sex', trim($_POST['age'] ?? '')),
    get_field_val($perm, 'age_sex', trim($_POST['sex'] ?? '')),
    get_field_val($perm, 'guardian', trim($_POST['guardian'] ?? '')),
    get_field_val($perm, 'contact_number', trim($_POST['contact_number'] ?? '')),
    get_field_val($perm, 'address', trim($_POST['address'] ?? '')),
    $bill_no,
    $visit,
    $visit_time,
    get_field_val($perm, 'panel', trim($_POST['panel'] ?? '')),
    get_field_val($perm, 'doctor_dept', trim($_POST['doctor_dept'] ?? '')),
    get_field_val($perm, 'room_no', trim($_POST['room_no'] ?? '')),
    $app_no,
    (int)$user['id']
];

$st = db()->prepare('INSERT INTO patients(uhid,name,age,sex,guardian,contact_number,address,bill_no,visit_date,visit_time,panel,doctor_dept,room_no,app_no,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
$st->execute($data);
$id = (int)db()->lastInsertId();

$log = db()->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,details) VALUES(?,'create','patient',?,?)");
$log->execute([$user['id'], $id, json_encode(['name' => $name, 'uhid' => $uhid, 'bill_no' => $bill_no])]);

queue_sheet_sync($id);
$templateId = (int)($_POST['template_id'] ?? 0);
header('Location: print_opd.php?id=' . $id . ($templateId ? '&template_id=' . $templateId : ''));
exit;
