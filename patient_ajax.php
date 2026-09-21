<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/settings.php';

$user = require_login();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['success' => true, 'patients' => []]);
        exit;
    }

    // Search for distinct patients by UHID or latest record
    $term = '%' . $q . '%';
    $st = db()->prepare("
        SELECT 
            p.*,
            (SELECT COUNT(*) FROM patients p2 WHERE p2.uhid = p.uhid AND p.uhid IS NOT NULL AND p.uhid != '') AS visit_count,
            (SELECT MAX(visit_date) FROM patients p3 WHERE p3.uhid = p.uhid AND p.uhid IS NOT NULL AND p.uhid != '') AS last_visit_date,
            u.name AS created_by_name
        FROM patients p
        LEFT JOIN users u ON u.id = p.created_by
        WHERE p.name LIKE ? OR p.uhid LIKE ? OR p.contact_number LIKE ? OR p.bill_no LIKE ?
        ORDER BY p.id DESC
        LIMIT 20
    ");
    $st->execute([$term, $term, $term, $term]);
    $results = $st->fetchAll();

    // Group by UHID if present so each distinct patient appears once
    $uniquePatients = [];
    $seenUhids = [];
    foreach ($results as $row) {
        $key = !empty($row['uhid']) ? 'uhid_' . $row['uhid'] : 'id_' . $row['id'];
        if (!isset($uniquePatients[$key])) {
            $uniquePatients[$key] = [
                'id' => (int)$row['id'],
                'uhid' => $row['uhid'] ?: '—',
                'name' => $row['name'],
                'age' => $row['age'] ?: '',
                'sex' => $row['sex'] ?: '',
                'guardian' => $row['guardian'] ?: '',
                'contact_number' => $row['contact_number'] ?: '',
                'address' => $row['address'] ?: '',
                'panel' => $row['panel'] ?: '',
                'doctor_dept' => $row['doctor_dept'] ?: '',
                'room_no' => $row['room_no'] ?: '',
                'last_visit_date' => $row['last_visit_date'] ?: $row['visit_date'],
                'visit_count' => max(1, (int)($row['visit_count'] ?? 1)),
                'created_by_name' => $row['created_by_name'] ?: '—'
            ];
        }
    }

    echo json_encode(['success' => true, 'patients' => array_values($uniquePatients)]);
    exit;
}

if ($action === 'get_detail') {
    $id = (int)($_GET['id'] ?? 0);
    $uhid = trim($_GET['uhid'] ?? '');

    $patient = null;
    if ($id > 0) {
        $st = db()->prepare("SELECT p.*, u.name AS created_by_name FROM patients p LEFT JOIN users u ON u.id = p.created_by WHERE p.id = ? LIMIT 1");
        $st->execute([$id]);
        $patient = $st->fetch();
    } elseif ($uhid !== '') {
        $st = db()->prepare("SELECT p.*, u.name AS created_by_name FROM patients p LEFT JOIN users u ON u.id = p.created_by WHERE p.uhid = ? ORDER BY p.id DESC LIMIT 1");
        $st->execute([$uhid]);
        $patient = $st->fetch();
    }

    if (!$patient) {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }

    // Fetch full visit history for this UHID (or this record if UHID is empty)
    $visits = [];
    if (!empty($patient['uhid'])) {
        $vSt = db()->prepare("
            SELECT p.*, u.name AS created_by_name 
            FROM patients p 
            LEFT JOIN users u ON u.id = p.created_by 
            WHERE p.uhid = ? 
            ORDER BY p.visit_date DESC, p.id DESC
        ");
        $vSt->execute([$patient['uhid']]);
        $visits = $vSt->fetchAll();
    } else {
        $visits = [$patient];
    }

    $totalVisits = count($visits);
    $mappedVisits = [];
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    foreach ($visits as $idx => $v) {
        $visitNum = $totalVisits - $idx; // 1 = initial visit, N = latest visit
        $vDate = $v['visit_date'];
        $relativeDate = '';
        if ($vDate === $today) {
            $relativeDate = 'Today';
        } elseif ($vDate === $yesterday) {
            $relativeDate = 'Yesterday';
        } else {
            $daysAgo = (int)round((strtotime($today) - strtotime($vDate)) / 86400);
            if ($daysAgo > 0 && $daysAgo < 30) {
                $relativeDate = $daysAgo . ' days ago';
            } else {
                $relativeDate = date('d M Y', strtotime($vDate));
            }
        }

        $mappedVisits[] = [
            'id' => (int)$v['id'],
            'visit_num' => $visitNum,
            'is_latest' => ($idx === 0),
            'is_first' => ($idx === ($totalVisits - 1)),
            'bill_no' => $v['bill_no'] ?: '—',
            'visit_date' => date('d M Y', strtotime($v['visit_date'])),
            'raw_date' => $v['visit_date'],
            'relative_date' => $relativeDate,
            'visit_time' => $v['visit_time'] ? date('h:i A', strtotime($v['visit_time'])) : '—',
            'doctor_dept' => $v['doctor_dept'] ?: 'General',
            'room_no' => $v['room_no'] ?: '—',
            'app_no' => $v['app_no'] ?: '—',
            'panel' => $v['panel'] ?: 'CASH',
            'created_by_name' => $v['created_by_name'] ?: 'Staff'
        ];
    }

    $initialVisit = end($visits);
    $latestVisit = $visits[0] ?? $patient;

    echo json_encode([
        'success' => true,
        'patient' => [
            'id' => (int)$patient['id'],
            'uhid' => $patient['uhid'] ?: '—',
            'name' => stripslashes(trim($patient['name'])),
            'age' => $patient['age'] ?: '',
            'sex' => $patient['sex'] ?: '',
            'guardian' => stripslashes(trim($patient['guardian'] ?? '')),
            'contact_number' => $patient['contact_number'] ?: '',
            'address' => stripslashes(trim($patient['address'] ?? '')),
            'bill_no' => $patient['bill_no'] ?: '—',
            'visit_date' => date('d M Y', strtotime($patient['visit_date'])),
            'raw_date' => $patient['visit_date'],
            'visit_time' => $patient['visit_time'] ? date('h:i A', strtotime($patient['visit_time'])) : '—',
            'panel' => $patient['panel'] ?: 'CASH',
            'doctor_dept' => $patient['doctor_dept'] ?: 'General',
            'room_no' => $patient['room_no'] ?: '—',
            'app_no' => $patient['app_no'] ?: '—',
            'created_by_name' => $patient['created_by_name'] ?: 'Staff',
            'created_at' => $patient['created_at'],
            'initial_visit_date' => $initialVisit ? date('d M Y', strtotime($initialVisit['visit_date'])) : date('d M Y', strtotime($patient['visit_date'])),
            'latest_visit_date' => $latestVisit ? date('d M Y', strtotime($latestVisit['visit_date'])) : date('d M Y', strtotime($patient['visit_date']))
        ],
        'visits' => $mappedVisits,
        'total_visits' => $totalVisits
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
