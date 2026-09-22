<?php
require_once __DIR__ . '/includes/auth.php';

try {
    $db = db();
    echo "<h1>Database Update Script</h1>";

    $queries = [
        "ALTER TABLE templates ADD COLUMN page_size VARCHAR(40) NULL DEFAULT 'A4'",
        "ALTER TABLE templates ADD COLUMN orientation ENUM('portrait', 'landscape') NULL DEFAULT 'portrait'",
        "ALTER TABLE templates ADD COLUMN page_width DECIMAL(8,2) NULL",
        "ALTER TABLE templates ADD COLUMN page_height DECIMAL(8,2) NULL",
        "ALTER TABLE templates ADD COLUMN page_unit VARCHAR(10) NOT NULL DEFAULT 'mm'",
        "ALTER TABLE templates ADD COLUMN margin_top DECIMAL(8,2) NULL DEFAULT 6.00",
        "ALTER TABLE templates ADD COLUMN margin_right DECIMAL(8,2) NULL DEFAULT 12.00",
        "ALTER TABLE templates ADD COLUMN margin_bottom DECIMAL(8,2) NULL DEFAULT 6.00",
        "ALTER TABLE templates ADD COLUMN margin_left DECIMAL(8,2) NULL DEFAULT 12.00",
        "ALTER TABLE templates ADD COLUMN page_config_json TEXT NULL",
        "ALTER TABLE templates ADD COLUMN theme_preset VARCHAR(40) NULL DEFAULT 'green'",
        "ALTER TABLE templates ADD COLUMN theme_config_json TEXT NULL",

        "CREATE TABLE IF NOT EXISTS departments (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(140) NOT NULL UNIQUE,
          code VARCHAR(40) NULL,
          description VARCHAR(255) NULL,
          active TINYINT(1) NOT NULL DEFAULT 1,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX idx_dept_active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        "CREATE TABLE IF NOT EXISTS doctors (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        "ALTER TABLE patients ADD COLUMN doctor_id INT UNSIGNED NULL",
        "ALTER TABLE patients ADD COLUMN doctor_name VARCHAR(160) NULL"
    ];

    foreach ($queries as $q) {
        try {
            $db->exec($q);
            echo "<p style='color:green;'>Success: " . htmlspecialchars(substr($q, 0, 70)) . "...</p>";
        } catch (Exception $e) {
            echo "<p style='color:gray;'>Skipped (Already exists): " . htmlspecialchars(substr($q, 0, 70)) . "...</p>";
        }
    }
    
    // Check indexes separately to avoid duplicate index errors
    $indexes = [
        ['table' => 'patients', 'name' => 'idx_patients_doc_id', 'cols' => 'doctor_id'],
        ['table' => 'patients', 'name' => 'idx_patients_doc_name', 'cols' => 'doctor_name']
    ];
    
    foreach ($indexes as $idx) {
        try {
            $db->exec("CREATE INDEX {$idx['name']} ON {$idx['table']}({$idx['cols']})");
            echo "<p style='color:green;'>Index {$idx['name']} created.</p>";
        } catch (Exception $e) {
            echo "<p style='color:gray;'>Index {$idx['name']} already exists or skipped.</p>";
        }
    }

    echo "<h2>Update Complete!</h2>";
    echo "<p><a href='templates.php' style='padding:10px 20px; background:#087f6c; color:#fff; text-decoration:none; border-radius:5px;'>Go back to Templates</a></p>";
} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
