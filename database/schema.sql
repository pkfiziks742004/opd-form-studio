CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','reception') NOT NULL DEFAULT 'reception',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uhid VARCHAR(100) NULL,
  name VARCHAR(160) NOT NULL,
  age VARCHAR(20) NULL,
  sex VARCHAR(20) NULL,
  guardian VARCHAR(160) NULL,
  contact_number VARCHAR(40) NULL,
  address TEXT NULL,
  bill_no VARCHAR(100) NULL,
  visit_date DATE NOT NULL,
  visit_time TIME NULL,
  panel VARCHAR(120) NULL,
  doctor_dept VARCHAR(160) NULL,
  room_no VARCHAR(60) NULL,
  app_no VARCHAR(60) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_patients_visit_date(visit_date),
  INDEX idx_patients_uhid(uhid),
  INDEX idx_patients_name(name),
  INDEX idx_patients_bill(bill_no),
  INDEX idx_patients_phone(contact_number),
  CONSTRAINT fk_patients_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NULL,
  mime_type VARCHAR(80) NULL,
  width INT NULL,
  height INT NULL,
  default_layout_json LONGTEXT NOT NULL,
  template_type ENUM('image','code') NOT NULL DEFAULT 'image',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_templates_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS template_layouts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  layout_json LONGTEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_template_user(template_id,user_id),
  CONSTRAINT fk_layout_template FOREIGN KEY(template_id) REFERENCES templates(id) ON DELETE CASCADE,
  CONSTRAINT fk_layout_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_field_permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  field_key VARCHAR(80) NOT NULL,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  editable TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_user_field(user_id,field_key),
  CONSTRAINT fk_permission_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sheet_sync_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id BIGINT UNSIGNED NOT NULL UNIQUE,
  payload_json LONGTEXT NOT NULL,
  status ENUM('pending','synced','failed') NOT NULL DEFAULT 'pending',
  attempts INT NOT NULL DEFAULT 0,
  last_error VARCHAR(500) NULL,
  last_attempt_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sync_patient FOREIGN KEY(patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(40) NOT NULL,
  entity_type VARCHAR(60) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  details TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created(created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info', 'alert', 'urgent') NOT NULL DEFAULT 'info',
  target_role ENUM('all', 'reception') NOT NULL DEFAULT 'reception',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_created(created_at),
  INDEX idx_notif_target(target_role),
  CONSTRAINT fk_notif_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_reads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notification_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_notif_user (notification_id, user_id),
  INDEX idx_reads_user (user_id),
  CONSTRAINT fk_reads_notif FOREIGN KEY(notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
  CONSTRAINT fk_reads_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO templates(name,file_path,original_name,mime_type,width,height,default_layout_json,template_type,active,created_by)
SELECT 'Motherland Hospital (Code Template)','code:motherland','motherland_code_template','text/html',794,1123,'{}','code',1,NULL
WHERE NOT EXISTS (SELECT 1 FROM templates WHERE file_path='code:motherland');

INSERT INTO templates(name,file_path,original_name,mime_type,width,height,default_layout_json,template_type,active,created_by)
SELECT 'Sample OPD Template','assets/sample-opd-template.jpg','sample-opd-template.jpg','image/jpeg',960,1280,
'{"uhid":{"x":18.0,"y":12.6,"fontSize":12,"width":250,"fontWeight":"500"},"name":{"x":18.0,"y":14.4,"fontSize":12,"width":250,"fontWeight":"500"},"age_sex":{"x":18.0,"y":16.2,"fontSize":12,"width":250,"fontWeight":"500"},"guardian":{"x":18.0,"y":18.0,"fontSize":12,"width":250,"fontWeight":"500"},"contact_number":{"x":18.0,"y":19.8,"fontSize":12,"width":250,"fontWeight":"500"},"address":{"x":18.0,"y":21.6,"fontSize":12,"width":320,"fontWeight":"500"},"bill_no":{"x":74.0,"y":12.6,"fontSize":12,"width":190,"fontWeight":"500"},"date":{"x":74.0,"y":14.4,"fontSize":12,"width":190,"fontWeight":"500"},"panel":{"x":74.0,"y":16.2,"fontSize":12,"width":190,"fontWeight":"500"},"doctor_dept":{"x":74.0,"y":18.0,"fontSize":12,"width":190,"fontWeight":"500"},"room_no":{"x":74.0,"y":19.8,"fontSize":12,"width":190,"fontWeight":"500"},"app_no":{"x":74.0,"y":21.6,"fontSize":12,"width":190,"fontWeight":"500"}}','image',1,NULL
WHERE NOT EXISTS (SELECT 1 FROM templates WHERE file_path='assets/sample-opd-template.jpg');

