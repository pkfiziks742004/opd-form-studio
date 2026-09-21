<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/sheets.php';

$isCli = PHP_SAPI === 'cli';
$key = $_GET['key'] ?? '';
$expected = (string)envv('CRON_KEY', '');
if (!$isCli && (!$expected || !hash_equals($expected, (string)$key))) {
    http_response_code(403);
    exit('Forbidden');
}

$ids = db()->query("SELECT patient_id FROM sheet_sync_queue WHERE status <> 'synced' AND attempts < 20 ORDER BY COALESCE(last_attempt_at,'1970-01-01') ASC LIMIT 100")->fetchAll(PDO::FETCH_COLUMN);
$ok = 0;
foreach ($ids as $id) {
    if (attempt_sheet_sync((int)$id)) $ok++;
}
header('Content-Type: text/plain; charset=utf-8');
echo "Processed " . count($ids) . ", synced {$ok}.\n";
