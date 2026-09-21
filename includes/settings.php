<?php
require_once __DIR__ . '/config.php';

function setting(string $key, string $default = ''): string {
    $st = db()->prepare('SELECT setting_value FROM settings WHERE setting_key=?');
    $st->execute([$key]);
    $r = $st->fetch();
    return $r ? (string)$r['setting_value'] : $default;
}

function set_setting(string $key, string $value): void {
    $st = db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $st->execute([$key, $value]);
}

function get_uhid_prefix(): string {
    return setting('uhid_prefix', 'MLH') ?: 'MLH';
}

function get_bill_prefix(): string {
    return setting('bill_prefix', 'MOB') ?: 'MOB';
}

function get_financial_year(?int $time = null): string {
    $t = $time ?: time();
    $month = (int)date('n', $t);
    $year = (int)date('y', $t);
    if ($month >= 4) {
        $next = ($year + 1) % 100;
        return sprintf('%02d-%02d', $year, $next);
    } else {
        $prev = ($year - 1 + 100) % 100;
        return sprintf('%02d-%02d', $prev, $year);
    }
}

function get_next_uhid(): string {
    $prefix = get_uhid_prefix();
    $year2 = date('y');
    
    // Find latest sequence matching prefix/year/
    $pattern = $prefix . '/' . $year2 . '/%';
    $st = db()->prepare('SELECT uhid FROM patients WHERE uhid LIKE ? ORDER BY id DESC LIMIT 100');
    $st->execute([$pattern]);
    $rows = $st->fetchAll(PDO::FETCH_COLUMN);
    
    $maxNum = 0;
    foreach ($rows as $u) {
        if (preg_match('#/(\d+)$#', (string)$u, $m)) {
            $n = (int)$m[1];
            if ($n > $maxNum) $maxNum = $n;
        }
    }
    
    $startOffset = (int)setting('uhid_start_number', '0');
    $next = max($maxNum + 1, $startOffset);
    if ($next < 1) $next = 1;
    
    return sprintf('%s/%s/%06d', $prefix, $year2, $next);
}

function get_next_bill_no(): string {
    $prefix = get_bill_prefix();
    $fy = get_financial_year();
    
    // Find latest sequence matching prefix/fy/
    $pattern = $prefix . '/' . $fy . '/%';
    $st = db()->prepare('SELECT bill_no FROM patients WHERE bill_no LIKE ? ORDER BY id DESC LIMIT 100');
    $st->execute([$pattern]);
    $rows = $st->fetchAll(PDO::FETCH_COLUMN);
    
    $maxNum = 0;
    foreach ($rows as $b) {
        if (preg_match('#/(\d+)$#', (string)$b, $m)) {
            $n = (int)$m[1];
            if ($n > $maxNum) $maxNum = $n;
        }
    }
    
    $startOffset = (int)setting('bill_start_number', '0');
    $next = max($maxNum + 1, $startOffset);
    if ($next < 1) $next = 1;
    
    return sprintf('%s/%s/%06d', $prefix, $fy, $next);
}

function get_next_app_no(?string $date = null): string {
    $d = $date ?: date('Y-m-d');
    $st = db()->prepare('SELECT COUNT(*) FROM patients WHERE visit_date=?');
    $st->execute([$d]);
    $count = (int)$st->fetchColumn();
    return (string)($count + 1);
}

function get_doctor_departments(): array {
    $json = setting('doctor_departments', '');
    if ($json) {
        $arr = json_decode($json, true);
        if (is_array($arr) && !empty($arr)) {
            return array_values(array_filter(array_map('trim', $arr)));
        }
    }
    return [
        'IVF',
        'Gynecology & Obstetrics',
        'General Medicine',
        'Pediatrics',
        'Orthopedics',
        'Cardiology',
        'ENT',
        'Dermatology',
        'Dental',
        'Ophthalmology'
    ];
}

function save_doctor_departments(array $departments): void {
    $clean = [];
    foreach ($departments as $d) {
        $d = trim((string)$d);
        if ($d !== '' && !in_array($d, $clean, true)) {
            $clean[] = $d;
        }
    }
    if (empty($clean)) {
        $clean = ['IVF', 'Gynecology & Obstetrics', 'General Medicine'];
    }
    set_setting('doctor_departments', json_encode($clean, JSON_UNESCAPED_UNICODE));
}
