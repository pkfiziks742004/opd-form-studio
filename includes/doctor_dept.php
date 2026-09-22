<?php
declare(strict_types=1);

if (!function_exists('db')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Self-healing schema migration: Ensures departments and doctors tables exist,
 * seeds initial hospital defaults if empty, and ensures doctor columns exist in patients table.
 */
function ensure_doctor_dept_schema(): void {
    static $migrated = false;
    if ($migrated) {
        return;
    }

    try {
        $pdo = db();

        // 1. Departments table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS departments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(140) NOT NULL UNIQUE,
                code VARCHAR(40) NULL,
                description VARCHAR(255) NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_dept_active (active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Doctors table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS doctors (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(160) NOT NULL,
                department_id INT UNSIGNED NULL,
                department_name VARCHAR(140) NOT NULL,
                room_no VARCHAR(60) NULL,
                qualification VARCHAR(160) NULL,
                opd_timings VARCHAR(160) NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_doc_dept_id (department_id),
                INDEX idx_doc_dept_name (department_name),
                INDEX idx_doc_active (active),
                CONSTRAINT fk_doctors_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Ensure patients table has doctor_id and doctor_name columns
        $existingCols = $pdo->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('doctor_id', $existingCols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN doctor_id INT UNSIGNED NULL AFTER doctor_dept, ADD INDEX idx_patients_doc_id (doctor_id)");
        }
        if (!in_array('doctor_name', $existingCols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN doctor_name VARCHAR(160) NULL AFTER doctor_id, ADD INDEX idx_patients_doc_name (doctor_name)");
        }

        // 4. Seed initial departments if table is empty
        $deptCount = (int)$pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
        if ($deptCount === 0) {
            $initialDepts = [
                ['name' => 'IVF', 'code' => 'IVF'],
                ['name' => 'Gynecology & Obstetrics', 'code' => 'GYN/OBS'],
                ['name' => 'General Medicine', 'code' => 'GEN'],
                ['name' => 'Pediatrics', 'code' => 'PED'],
                ['name' => 'Orthopedics', 'code' => 'ORTHO'],
                ['name' => 'Cardiology', 'code' => 'CARDIO'],
                ['name' => 'ENT', 'code' => 'ENT'],
                ['name' => 'Dermatology', 'code' => 'DERM'],
                ['name' => 'Dental', 'code' => 'DENT'],
                ['name' => 'Ophthalmology', 'code' => 'EYE'],
            ];
            $stIns = $pdo->prepare("INSERT INTO departments (name, code, active) VALUES (?, ?, 1)");
            foreach ($initialDepts as $d) {
                $stIns->execute([$d['name'], $d['code']]);
            }
        }

        // 5. Seed initial doctors if table is empty
        $docCount = (int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
        if ($docCount === 0) {
            // Find IVF department id
            $stIvf = $pdo->prepare("SELECT id FROM departments WHERE name = 'IVF' LIMIT 1");
            $stIvf->execute();
            $ivfId = (int)$stIvf->fetchColumn() ?: 1;

            $initialDoctors = [
                [
                    'name' => 'Dr. ANVITI SARAF',
                    'department_id' => $ivfId,
                    'department_name' => 'IVF',
                    'room_no' => '102',
                    'qualification' => 'MBBS, MS, Fellowship in Reproductive Medicine',
                    'opd_timings' => '10:00 AM - 02:00 PM'
                ],
                [
                    'name' => 'Dr. PRIYA SHARMA',
                    'department_id' => $ivfId,
                    'department_name' => 'IVF',
                    'room_no' => '105',
                    'qualification' => 'MBBS, DGO, Infertility Specialist',
                    'opd_timings' => '02:00 PM - 06:00 PM'
                ]
            ];

            $stDocIns = $pdo->prepare("
                INSERT INTO doctors (name, department_id, department_name, room_no, qualification, opd_timings, active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            foreach ($initialDoctors as $doc) {
                $stDocIns->execute([
                    $doc['name'],
                    $doc['department_id'],
                    $doc['department_name'],
                    $doc['room_no'],
                    $doc['qualification'],
                    $doc['opd_timings']
                ]);
            }
        }

        $migrated = true;
    } catch (Throwable $e) {
        error_log("ensure_doctor_dept_schema error: " . $e->getMessage());
    }
}

// Auto-run migration when this file is included
ensure_doctor_dept_schema();

/**
 * Fetch all active or all departments.
 */
function get_all_departments(bool $onlyActive = true): array {
    ensure_doctor_dept_schema();
    $sql = "SELECT d.*, (SELECT COUNT(*) FROM doctors doc WHERE doc.department_id = d.id AND doc.active = 1) AS doctor_count 
            FROM departments d";
    if ($onlyActive) {
        $sql .= " WHERE d.active = 1";
    }
    $sql .= " ORDER BY d.name ASC";
    return db()->query($sql)->fetchAll();
}

/**
 * Fetch doctors, optionally filtered by department.
 */
function get_all_doctors(bool $onlyActive = true, ?int $deptId = null): array {
    ensure_doctor_dept_schema();
    $sql = "SELECT doc.*, d.name as dept_full_name, d.code as dept_code 
            FROM doctors doc
            LEFT JOIN departments d ON doc.department_id = d.id";
    $params = [];
    $where = [];

    if ($onlyActive) {
        $where[] = "doc.active = 1";
    }
    if ($deptId !== null && $deptId > 0) {
        $where[] = "doc.department_id = ?";
        $params[] = $deptId;
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY doc.department_name ASC, doc.name ASC";

    if (!empty($params)) {
        $st = db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
    return db()->query($sql)->fetchAll();
}

/**
 * Group active doctors by department name for instant client-side dependent dropdowns.
 */
function get_doctors_grouped_by_dept(): array {
    $doctors = get_all_doctors(true);
    $map = [];
    foreach ($doctors as $doc) {
        $dept = trim((string)$doc['department_name']);
        if (!isset($map[$dept])) {
            $map[$dept] = [];
        }
        $map[$dept][] = [
            'id' => (int)$doc['id'],
            'name' => $doc['name'],
            'department_id' => (int)$doc['department_id'],
            'department_name' => $doc['department_name'],
            'room_no' => $doc['room_no'] ?: '',
            'qualification' => $doc['qualification'] ?: '',
            'opd_timings' => $doc['opd_timings'] ?: '',
        ];
    }
    return $map;
}

/**
 * Save / Create department
 */
function save_department(string $name, ?string $code = '', ?string $desc = '', int $id = 0, int $active = 1): int {
    ensure_doctor_dept_schema();
    $name = trim($name);
    $code = trim((string)$code);
    $desc = trim((string)$desc);

    if ($name === '') {
        throw new InvalidArgumentException('Department name cannot be empty.');
    }

    $pdo = db();
    if ($id > 0) {
        // Update
        $st = $pdo->prepare("UPDATE departments SET name = ?, code = ?, description = ?, active = ? WHERE id = ?");
        $st->execute([$name, $code, $desc, $active, $id]);
        // Also update department_name in doctors table
        $stUp = $pdo->prepare("UPDATE doctors SET department_name = ? WHERE department_id = ?");
        $stUp->execute([$name, $id]);
        return $id;
    } else {
        // Check if exists
        $stCheck = $pdo->prepare("SELECT id FROM departments WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stCheck->execute([$name]);
        $existingId = $stCheck->fetchColumn();
        if ($existingId) {
            return (int)$existingId;
        }

        $st = $pdo->prepare("INSERT INTO departments (name, code, description, active) VALUES (?, ?, ?, ?)");
        $st->execute([$name, $code, $desc, $active]);
        return (int)$pdo->lastInsertId();
    }
}

/**
 * Save / Create doctor
 */
function save_doctor(string $name, string $deptName, ?string $roomNo = '', ?string $qualification = '', ?string $opdTimings = '', int $id = 0, int $active = 1, ?int $deptId = null): int {
    ensure_doctor_dept_schema();
    $name = trim($name);
    $deptName = trim($deptName);
    $roomNo = trim((string)$roomNo);
    $qualification = trim((string)$qualification);
    $opdTimings = trim((string)$opdTimings);

    if ($name === '') {
        throw new InvalidArgumentException('Doctor name cannot be empty.');
    }
    if ($deptName === '') {
        throw new InvalidArgumentException('Department cannot be empty.');
    }

    $pdo = db();

    // Resolve department_id
    if (!$deptId || $deptId <= 0) {
        $stDept = $pdo->prepare("SELECT id FROM departments WHERE LOWER(name) = LOWER(?) LIMIT 1");
        $stDept->execute([$deptName]);
        $foundId = $stDept->fetchColumn();
        if ($foundId) {
            $deptId = (int)$foundId;
        } else {
            // Auto-create department if it doesn't exist
            $deptId = save_department($deptName);
        }
    }

    if ($id > 0) {
        $st = $pdo->prepare("
            UPDATE doctors 
            SET name = ?, department_id = ?, department_name = ?, room_no = ?, qualification = ?, opd_timings = ?, active = ?
            WHERE id = ?
        ");
        $st->execute([$name, $deptId, $deptName, $roomNo, $qualification, $opdTimings, $active, $id]);
        return $id;
    } else {
        $st = $pdo->prepare("
            INSERT INTO doctors (name, department_id, department_name, room_no, qualification, opd_timings, active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([$name, $deptId, $deptName, $roomNo, $qualification, $opdTimings, $active]);
        return (int)$pdo->lastInsertId();
    }
}
