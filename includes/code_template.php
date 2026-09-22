<?php
declare(strict_types=1);

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/page_engine.php';
require_once __DIR__ . '/theme_engine.php';

function get_motherland_defaults(): array {
    return [
        'hospital_name' => 'Motherland',
        'hospital_tagline' => 'HOSPITAL',
        'doc_title' => 'Consultation Paper(OPD)',
        'doctor_name' => 'Dr. ANVITI SARAF',
        'doctor_dept' => 'IVF',
        'validity_note' => 'Bill is valid for 3 days Including date of Billing.',
        'font_family' => 'Arial',
        
        // Field Labels
        'lbl_uhid' => 'UHID',
        'lbl_name' => 'Name',
        'lbl_age_sex' => 'Age/Sex',
        'lbl_guardian' => 'Guardian',
        'lbl_contact' => 'Contact No.',
        'lbl_address' => 'Address',
        'lbl_bill' => 'Bill No.',
        'lbl_date' => 'Date',
        'lbl_panel' => 'Panel',
        'lbl_dept' => 'Doctor Dept',
        'lbl_room' => 'Room No',
        'lbl_app' => 'App No',

        // Vitals labels & units
        'lbl_height' => 'Height:',
        'unit_height' => '(cm)',
        'lbl_weight' => 'Weight:',
        'unit_weight' => '(kg)',
        'lbl_temp' => 'Temp:',
        'unit_temp' => '°C',
        'lbl_pulse' => 'Pulse:',
        'unit_pulse' => '/min',
        'lbl_pain' => 'Pain Score(0-10):',
        'lbl_allergies' => 'Allergies If Any:',
        'lbl_bmi' => 'BMI:',
        'lbl_bp' => 'BP:',
        'unit_bp' => '(mmHG)',

        // Footer info
        'hospital_address' => 'Hospital.: Sector 119, Noida - 201305, U.P., India',
        'reg_office' => "Reg. Office.: House No. - 227, Main Road\nVillage Khichipur, Delhi - 110091\nCIN: U85110DL1999PTC098269",
        'phone_whatsapp' => '+91 99937 77444',
        'phone_landline' => '+91 120 4154949',
        'email' => 'info@motherlandhospital.com',
        'website' => 'www.motherlandhospital.com',
        
        // Icon & Accent
        'icon_path' => 'assets/motherland-icon.png',
        'accent_color' => '#02872e',
        'accent_height' => '28',

        // Watermark Controls
        'show_watermark' => '1',
        'watermark_opacity' => '0.06',
        'watermark_size' => '150',
        'watermark_position' => 'bottom-right',

        // Print Pages Control
        'default_print_pages' => '1',
        'enable_two_pages' => '0',

        // Section Visibility Toggles (1 = show, 0 = hide)
        'show_header' => '1',
        'show_title' => '1',
        'show_patient_info' => '1',
        'show_doctor_box' => '1',
        'show_vitals' => '1',
        'show_validity_note' => '1',
        'show_signature_box' => '1',
        'lbl_signature' => "Doctor's Signature / Stamp",
        'show_footer' => '1',
        'show_divider_lines' => '0',

        // Field Level Toggles
        'show_uhid' => '1',
        'show_name' => '1',
        'show_age_sex' => '1',
        'show_guardian' => '1',
        'show_contact' => '1',
        'show_address' => '1',
        'show_bill' => '1',
        'show_date' => '1',
        'show_panel' => '1',
        'show_dept' => '1',
        'show_room' => '1',
        'show_app' => '1',

        // Vitals Level Toggles
        'show_vital_height' => '1',
        'show_vital_weight' => '1',
        'show_vital_temp' => '1',
        'show_vital_pulse' => '1',
        'show_vital_pain' => '1',
        'show_vital_allergies' => '1',
        'show_vital_bmi' => '1',
        'show_vital_bp' => '1',
    ];
}

function get_motherland_config(?array $template = null): array {
    $defs = get_motherland_defaults();
    $cfg = [];
    foreach ($defs as $k => $def) {
        $val = setting('motherland_tpl_' . $k, '');
        $cfg[$k] = ($val !== '') ? $val : $def;
    }

    if ($template && !empty($template['default_layout_json'])) {
        $data = json_decode((string)$template['default_layout_json'], true);
        if (is_array($data) && !empty($data['code_config']) && is_array($data['code_config'])) {
            foreach ($data['code_config'] as $k => $v) {
                if (array_key_exists($k, $defs) && $v !== null && $v !== '') {
                    $cfg[$k] = (string)$v;
                }
            }
        }
    }

    return $cfg;
}

function save_motherland_config(array $data): void {
    $defs = get_motherland_defaults();
    foreach ($defs as $k => $def) {
        if (isset($data[$k])) {
            set_setting('motherland_tpl_' . $k, trim((string)$data[$k]));
        }
    }
}

function get_default_sections(): array {
    return [
        'header_mode' => 'digital',       // 'digital' (print hospital brand header) or 'blank' (leave blank margin space for pre-printed letterhead pad)
        'header_height' => 38.0,          // mm (auto-covered header area height)
        'patient_top' => 42.0,            // mm (top position / spacing of patient details section)
        'patient_style' => 'divider',     // 'divider' (clean top/bottom line like Image 2), 'box' (bordered table box), 'none'
        'patient_density' => 'normal',    // 'compact', 'normal', 'relaxed'
        'patient_font_size' => 10,        // pt
        'vitals_top' => 82.0,             // mm
        'show_vitals' => 1,               // 1 = show vitals table, 0 = hide
        'footer_mode' => 'digital',       // 'digital' (print 3-col footer) or 'blank' (leave blank margin space for pre-printed letterhead pad)
        'footer_height' => 28.0,          // mm (auto-covered footer area height)
        'show_signature' => 1             // 1 = show signature line
    ];
}

function get_template_sections(?array $template = null, ?array $layout = null): array {
    $defs = get_default_sections();
    $raw = null;
    if (!empty($layout['sections']) && is_array($layout['sections'])) {
        $raw = $layout['sections'];
    } elseif ($template && !empty($template['default_layout_json'])) {
        $data = json_decode((string)$template['default_layout_json'], true);
        if (is_array($data) && !empty($data['sections']) && is_array($data['sections'])) {
            $raw = $data['sections'];
        }
    }

    if (!$raw) {
        return $defs;
    }

    $res = $defs;
    // Flat keys
    foreach ($defs as $k => $def) {
        if (isset($raw[$k])) {
            $res[$k] = $raw[$k];
        }
    }
    // Nested structure support
    if (isset($raw['header']) && is_array($raw['header'])) {
        if (isset($raw['header']['mode'])) $res['header_mode'] = (string)$raw['header']['mode'];
        if (isset($raw['header']['height_mm'])) $res['header_height'] = (float)$raw['header']['height_mm'];
        if (isset($raw['header']['height'])) $res['header_height'] = (float)$raw['header']['height'];
    }
    if (isset($raw['patient']) && is_array($raw['patient'])) {
        if (isset($raw['patient']['top_mm'])) $res['patient_top'] = (float)$raw['patient']['top_mm'];
        if (isset($raw['patient']['top'])) $res['patient_top'] = (float)$raw['patient']['top'];
        if (isset($raw['patient']['border_style'])) $res['patient_style'] = (string)$raw['patient']['border_style'];
        if (isset($raw['patient']['style'])) $res['patient_style'] = (string)$raw['patient']['style'];
        if (isset($raw['patient']['density'])) $res['patient_density'] = (string)$raw['patient']['density'];
        if (isset($raw['patient']['font_size_pt'])) $res['patient_font_size'] = (int)$raw['patient']['font_size_pt'];
        if (isset($raw['patient']['font_size'])) $res['patient_font_size'] = (int)$raw['patient']['font_size'];
    }
    if (isset($raw['vitals']) && is_array($raw['vitals'])) {
        if (isset($raw['vitals']['visible'])) $res['show_vitals'] = $raw['vitals']['visible'] ? 1 : 0;
        if (isset($raw['vitals']['top_mm'])) $res['vitals_top'] = (float)$raw['vitals']['top_mm'];
    }
    if (isset($raw['signature']) && is_array($raw['signature'])) {
        if (isset($raw['signature']['visible'])) $res['show_signature'] = $raw['signature']['visible'] ? 1 : 0;
    }
    if (isset($raw['footer']) && is_array($raw['footer'])) {
        if (isset($raw['footer']['mode'])) $res['footer_mode'] = (string)$raw['footer']['mode'];
        if (isset($raw['footer']['height_mm'])) $res['footer_height'] = (float)$raw['footer']['height_mm'];
        if (isset($raw['footer']['height'])) $res['footer_height'] = (float)$raw['footer']['height'];
    }
    return $res;
}

function render_motherland_opd(array $patient, array $config = [], bool $isPreview = false, array $layout = [], int $pages = 0, ?array $template = null): string {
    $rx = [];
    $extraCfg = [];
    if (!empty($config) && array_is_list($config)) {
        $rx = $config;
    } elseif (!empty($config)) {
        $extraCfg = $config;
    }
    if (empty($rx) && !empty($patient['rx']) && is_array($patient['rx'])) {
        $rx = $patient['rx'];
    }

    $cfg = array_merge(get_motherland_config($template), $extraCfg);
    if (!empty($layout['code_config']) && is_array($layout['code_config'])) {
        $cfg = array_merge($cfg, $layout['code_config']);
    }
    $pageConfig = get_template_page_config($template ?: []);
    $theme = get_template_theme($template);
    $themeStyle = generate_theme_style_attr($theme);
    $pageWidthStr = $pageConfig['width'] . $pageConfig['unit'];
    $pageHeightStr = $pageConfig['height'] . $pageConfig['unit'];
    $pagePadStr = "{$pageConfig['marginTop']}{$pageConfig['unit']} {$pageConfig['marginRight']}{$pageConfig['unit']} {$pageConfig['marginBottom']}{$pageConfig['unit']} {$pageConfig['marginLeft']}{$pageConfig['unit']}";

    // Sections & Zone Dimensions (Eka Care production model)
    $sections = get_template_sections($template, $layout);
    $headerHeight = (float)($sections['header_height'] ?? 38.0);
    $patientTop = (float)($sections['patient_top'] ?? 42.0);
    $footerHeight = (float)($sections['footer_height'] ?? 28.0);
    $headerMode = $sections['header_mode'] ?? 'digital';
    $footerMode = $sections['footer_mode'] ?? 'digital';
    $patientStyle = $sections['patient_style'] ?? 'divider';
    $patientDensity = $sections['patient_density'] ?? 'normal';
    $patientFontSize = (int)($sections['patient_font_size'] ?? 10);
    $showVitals = !empty($sections['show_vitals']);
    $showSignature = !empty($sections['show_signature']);

    // Resolve number of pages
    if ($pages <= 0) {
        $pages = (!empty($cfg['default_print_pages']) && (int)$cfg['default_print_pages'] === 2) ? 2 : 1;
        if ($pages === 1 && !empty($cfg['enable_two_pages']) && (int)$cfg['enable_two_pages'] === 1) {
            $pages = 2;
        }
    }

    $uhid = $patient['uhid'] ?? '';
    $name = $patient['name'] ?? '';
    $age = $patient['age'] ?? '';
    $sex = $patient['sex'] ?? '';
    $age_sex = trim(($age !== '' ? $age : '') . ($age !== '' && $sex !== '' ? ' / ' : '') . ($sex !== '' ? $sex : ''));
    if ($age_sex === '' && isset($patient['age_sex'])) $age_sex = $patient['age_sex'];
    $guardian = $patient['guardian'] ?? '';
    $contact = $patient['contact_number'] ?? '';
    $address = $patient['address'] ?? '';

    $bill_no = $patient['bill_no'] ?? '';
    
    // Format date cleanly with real time
    $visit_date = $patient['visit_date'] ?? '';
    $visit_time = $patient['visit_time'] ?? '';
    if (empty($visit_time)) {
        if (!empty($patient['created_at'])) {
            $visit_time = date('H:i:s', strtotime($patient['created_at']));
        } else {
            $visit_time = date('H:i:s');
        }
    }
    $date_formatted = '';
    if (!empty($visit_date)) {
        $timestamp = strtotime($visit_date . ' ' . $visit_time);
        if ($timestamp) {
            $date_formatted = date('d-M-Y h:i A', $timestamp);
        } else {
            $date_formatted = $visit_date . ' ' . date('h:i A', strtotime($visit_time));
        }
    } else {
        $date_formatted = date('d-M-Y h:i A');
    }

    $panel = $patient['panel'] ?? '';
    $dept = $patient['doctor_dept'] ?? ($cfg['doctor_dept'] ?? '');
    $room_no = $patient['room_no'] ?? '';
    $app_no = $patient['app_no'] ?? '';
    $doc_name = !empty($patient['doctor_name']) ? $patient['doctor_name'] : (!empty($cfg['doctor_name']) ? $cfg['doctor_name'] : 'Dr. ANVITI SARAF');

    // Verify icon path and convert to data URI for bulletproof rendering in all environments
    $icon = $cfg['icon_path'];
    $iconFullPath = dirname(__DIR__) . '/' . ltrim($icon, '/');
    if (!file_exists($iconFullPath)) {
        $iconFullPath = dirname(__DIR__) . '/assets/motherland-icon.png';
    }
    if (file_exists($iconFullPath)) {
        $mime = @mime_content_type($iconFullPath) ?: 'image/png';
        $iconSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($iconFullPath));
    } else {
        $iconSrc = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
    }

    $validity = $cfg['validity_note'] ?? 'Bill is valid for 3 days Including date of Billing.';
    
    // Format address with bold label if starts with Hospital.: or Reg. Office.:
    $hosp_addr = htmlspecialchars($cfg['hospital_address'] ?? '', ENT_QUOTES, 'UTF-8');
    $hosp_addr = preg_replace('/^(Hospital\.:?)/i', '<strong>$1</strong>', $hosp_addr);

    $reg_office_nl = nl2br(htmlspecialchars($cfg['reg_office'] ?? '', ENT_QUOTES, 'UTF-8'));
    $reg_office_nl = preg_replace('/^(Reg\.\s*Office\.:?)/i', '<strong>$1</strong>', $reg_office_nl);

    // Optional position offsets from custom layout
    $getPos = function(string $key) use ($layout): string {
        if (!empty($layout[$key]) && isset($layout[$key]['x']) && isset($layout[$key]['y'])) {
            return sprintf('left:%.2f%%;top:%.2f%%;position:absolute;', floatval($layout[$key]['x']), floatval($layout[$key]['y']));
        }
        return '';
    };

    $wmOpacity = !empty($cfg['watermark_opacity']) ? floatval($cfg['watermark_opacity']) : 0.06;
    $wmSize = !empty($cfg['watermark_size']) ? intval($cfg['watermark_size']) : 105;
    $wmPos = !empty($cfg['watermark_position']) ? $cfg['watermark_position'] : 'bottom-right';
    $accentHeight = !empty($cfg['accent_height']) ? intval($cfg['accent_height']) : 28;

    $fontFamily = !empty($cfg['font_family']) ? $cfg['font_family'] : 'Arial';
    $fontCss = match($fontFamily) {
        'Inter' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        'Roboto' => "'Roboto', Arial, sans-serif",
        'Cambria' => "Cambria, Georgia, serif",
        'Calibri' => "Calibri, Candara, Segoe, 'Segoe UI', Optima, Arial, sans-serif",
        'Segoe UI' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
        'Times New Roman' => "'Times New Roman', Times, serif",
        'Outfit' => "'Outfit', 'Segoe UI', sans-serif",
        default => 'Arial, "Helvetica Neue", Helvetica, sans-serif'
    };

    ob_start();
    ?>
    <!-- ========================================== -->
    <!-- PAGE 1: FULL OPD CONSULTATION PAPER        -->
    <!-- ========================================== -->
    <div class="motherland-sheet page-1 <?= $isPreview ? 'is-preview' : '' ?>" id="motherlandSheet" style="<?= $themeStyle ?>--ml-font: <?= $fontCss ?>; --ml-wm-opacity: <?= $wmOpacity ?>; --ml-wm-size: <?= $wmSize ?>mm; --ml-page-width: <?= $pageWidthStr ?>; --ml-page-height: <?= $pageHeightStr ?>; --ml-page-padding: <?= $pagePadStr ?>; --ml-header-height: <?= $headerHeight ?>mm; --ml-footer-height: <?= $footerHeight ?>mm;">
        
        <!-- Watermark (Customizable Position & Size) -->
        <div class="ml-watermark ml-wm-<?= htmlspecialchars($wmPos, ENT_QUOTES, 'UTF-8') ?>" style="<?= empty($cfg['show_watermark']) ? 'display:none;' : '' ?>">
            <img src="<?= $iconSrc ?>" alt="" class="ml-watermark-img ml-wm-img">
        </div>

        <!-- 1. Header Zone (Auto-Covered: Digital Brand Header OR Pre-printed Pad Blank Margin) -->
        <?php if ($isPreview || $headerMode === 'blank'): ?>
            <div class="ml-header-blank-zone" style="height: <?= $headerHeight ?>mm; <?= ($headerMode !== 'blank') ? 'display:none;' : '' ?>">
                <?php if ($isPreview): ?>
                    <div class="ml-zone-blank-tag">
                        <span>📄 Pre-printed Pad Header Area (<span class="ml-h-val-preview"><?= $headerHeight ?></span>mm Blank Reserved)</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($isPreview || $headerMode === 'digital'): ?>
            <!-- Top Left Accent Bar -->
            <div class="ml-top-accent" style="height: <?= (int)$headerHeight ?>mm; <?= ($headerMode !== 'digital' || ($cfg['show_header'] ?? '1') != '1') ? 'display:none;' : '' ?>"></div>

            <div class="ml-header-container" style="height: <?= $headerHeight ?>mm; max-height: <?= $headerHeight ?>mm; <?= ($headerMode !== 'digital' || ($cfg['show_header'] ?? '1') != '1') ? 'display:none;' : '' ?>">
                <div class="ml-header-section ml-block" data-block="block_logo" style="<?= $getPos('block_logo') ?>">
                    <div class="ml-brand">
                        <img src="<?= $iconSrc ?>" alt="Logo" class="ml-brand-icon">
                        <div class="ml-brand-text">
                            <div class="ml-hospital-name"><?= htmlspecialchars($cfg['hospital_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="ml-hospital-tagline">— <?= htmlspecialchars($cfg['hospital_tagline'], ENT_QUOTES, 'UTF-8') ?> —</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Document Title: Consultation Paper(OPD) (Positioned cleanly directly above patient table) -->
        <div class="ml-title-section ml-block" data-block="block_title" style="<?= $getPos('block_title') ?> <?= (($cfg['show_title'] ?? '1') != '1') ? 'display:none;' : '' ?>">
            <h1 class="ml-title"><?= htmlspecialchars($cfg['doc_title'], ENT_QUOTES, 'UTF-8') ?></h1>
        </div>

        <!-- 2. Patient Demographics Section (Structured 2-Column Table as shown in User Image 2) -->
        <div class="ml-patient-section ml-style-<?= htmlspecialchars($patientStyle) ?> ml-density-<?= htmlspecialchars($patientDensity) ?>" style="font-size: <?= $patientFontSize ?>pt; <?= (($cfg['show_patient_info'] ?? '1') != '1') ? 'display:none;' : '' ?>">
            <div class="ml-divider-rule ml-meta-top-rule"></div>
            <div class="ml-meta-grid ml-block" data-block="block_meta" style="<?= $getPos('block_meta') ?>">
                    <!-- Left Column -->
                    <div class="ml-meta-col ml-col-left">
                        <?php if (!empty($cfg['show_uhid'])): ?>
                            <div class="ml-field-row" data-field="uhid">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_uhid'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-uhid font-bold"><?= htmlspecialchars($uhid, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_name'])): ?>
                            <div class="ml-field-row" data-field="name">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-name font-bold"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_age_sex'])): ?>
                            <div class="ml-field-row" data-field="age_sex">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_age_sex'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-age_sex"><?= htmlspecialchars($age_sex, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_guardian'])): ?>
                            <div class="ml-field-row" data-field="guardian">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_guardian'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-guardian"><?= htmlspecialchars($guardian, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_contact'])): ?>
                            <div class="ml-field-row" data-field="contact_number">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_contact'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-contact"><?= htmlspecialchars($contact, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_address'])): ?>
                            <div class="ml-field-row" data-field="address">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_address'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-address"><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column -->
                    <div class="ml-meta-col ml-col-right">
                        <?php if (!empty($cfg['show_bill'])): ?>
                            <div class="ml-field-row" data-field="bill_no">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_bill'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-bill font-bold"><?= htmlspecialchars($bill_no, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_date'])): ?>
                            <div class="ml-field-row" data-field="date">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_date'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-date"><?= htmlspecialchars($date_formatted, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_panel'])): ?>
                            <div class="ml-field-row" data-field="panel">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_panel'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-panel"><?= htmlspecialchars($panel, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_dept'])): ?>
                            <div class="ml-field-row" data-field="doctor_dept">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_dept'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-dept"><?= htmlspecialchars($dept, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_room'])): ?>
                            <div class="ml-field-row" data-field="room_no">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_room'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-room"><?= htmlspecialchars($room_no, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($cfg['show_app'])): ?>
                            <div class="ml-field-row" data-field="app_no">
                                <span class="ml-label"><?= htmlspecialchars($cfg['lbl_app'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="ml-sep">:</span>
                                <span class="ml-value ml-val-app"><?= htmlspecialchars($app_no, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ml-divider-rule ml-meta-bottom-rule"></div>
            </div>

        <!-- 3. Combined Doctor Banner & Vitals Box -->
        <div class="ml-doctor-vitals-box ml-block" data-block="block_doctor_vitals" style="<?= $getPos('block_doctor_vitals') ?> <?= (($cfg['show_doctor_box'] ?? '1') != '1') ? 'display:none;' : '' ?>">
            <!-- Doctor Name Header -->
            <div class="ml-doc-header" style="<?= (!$showVitals) ? 'border-bottom:none;' : '' ?>">
                <span class="ml-doctor-name"><?= htmlspecialchars($doc_name ?: 'Dr. ANVITI SARAF', ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <!-- Vitals Measurements Table -->
            <div class="ml-vitals-table" style="<?= $showVitals ? '' : 'display:none;' ?>">
                <div class="ml-vitals-row ml-vitals-row-1">
                    <div class="ml-v-cell <?= empty($cfg['show_vital_height']) ? 'ml-hide' : '' ?>">
                        <span class="v-lbl"><?= htmlspecialchars($cfg['lbl_height'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="v-unit"><?= htmlspecialchars($cfg['unit_height'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="ml-v-cell <?= empty($cfg['show_vital_weight']) ? 'ml-hide' : '' ?>">
                        <span class="v-lbl"><?= htmlspecialchars($cfg['lbl_weight'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="v-unit"><?= htmlspecialchars($cfg['unit_weight'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="ml-v-cell <?= empty($cfg['show_vital_temp']) ? 'ml-hide' : '' ?>">
                        <span class="v-lbl"><?= htmlspecialchars($cfg['lbl_temp'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="v-unit"><?= htmlspecialchars($cfg['unit_temp'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="ml-v-cell <?= empty($cfg['show_vital_pulse']) ? 'ml-hide' : '' ?>">
                        <span class="v-lbl"><?= htmlspecialchars($cfg['lbl_pulse'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="v-unit"><?= htmlspecialchars($cfg['unit_pulse'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <div class="ml-vitals-row ml-vitals-row-2">
                    <div class="ml-v-cell <?= empty($cfg['show_vital_pain']) ? 'ml-hide' : '' ?>">
                        <span class="v-lbl"><?= htmlspecialchars($cfg['lbl_pain'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="ml-v-cell <?= empty($cfg['show_vital_allergies']) ? 'ml-hide' : '' ?>">
                        <span class="v-lbl"><?= htmlspecialchars($cfg['lbl_allergies'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="ml-v-cell <?= empty($cfg['show_vital_bmi']) ? 'ml-hide' : '' ?>">
                        <span class="v-lbl"><?= htmlspecialchars($cfg['lbl_bmi'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="ml-v-cell <?= empty($cfg['show_vital_bp']) ? 'ml-hide' : '' ?>">
                        <span class="v-lbl"><?= htmlspecialchars($cfg['lbl_bp'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="v-unit"><?= htmlspecialchars($cfg['unit_bp'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Consultation Writing Canvas (Doctors Rx & Notes) -->
        <div class="ml-consultation-body">
            <div class="ml-rx-watermark">℞</div>
            <?php if (!empty($rx) && is_array($rx)): ?>
                <table class="ml-rx-table">
                    <thead>
                        <tr>
                            <th style="width:36%;">Medicine / Test</th>
                            <th style="width:18%;">Dosage</th>
                            <th style="width:16%;">Timing</th>
                            <th style="width:14%;">Duration</th>
                            <th style="width:16%;">Instructions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rx as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string)($item['name'] ?? ($item['item'] ?? '')), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars((string)($item['dosage'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($item['timing'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($item['duration'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($item['instructions'] ?? ($item['advice'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- 5. Bottom Meta Row: Validity Notice & Doctor's Signature -->
        <div class="ml-bottom-meta-row">
            <div class="ml-validity-section ml-block" data-block="block_validity" style="<?= $getPos('block_validity') ?> <?= (($cfg['show_validity_note'] ?? '1') != '1') ? 'display:none;' : '' ?>">
                <div class="ml-validity-note">
                    <strong>Note :</strong> <u><em><?= htmlspecialchars($validity ?: 'Bill is valid for 3 days Including date of Billing.', ENT_QUOTES, 'UTF-8') ?></em></u>
                </div>
            </div>
            <div class="ml-sign-section" style="<?= ($showSignature || !empty($cfg['show_signature_box'])) ? '' : 'display:none;' ?>">
                <div class="ml-sign-line"></div>
                <div class="ml-sign-text"><?= htmlspecialchars($cfg['lbl_signature'] ?? "Doctor's Signature / Stamp", ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <!-- 6. Footer Zone (Auto-Covered: Digital 3-Column Footer OR Pre-printed Pad Blank Margin) -->
        <?php if ($isPreview || $footerMode === 'blank'): ?>
            <div class="ml-footer-blank-zone" style="height: <?= $footerHeight ?>mm; <?= ($footerMode !== 'blank') ? 'display:none;' : '' ?>">
                <?php if ($isPreview): ?>
                    <div class="ml-zone-blank-tag">
                        <span>📄 Pre-printed Pad Footer Area (<span class="ml-f-val-preview"><?= $footerHeight ?></span>mm Blank Reserved)</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($isPreview || $footerMode === 'digital'): ?>
            <div class="ml-footer-wrapper" style="min-height: <?= $footerHeight ?>mm; <?= ($footerMode !== 'digital' || ($cfg['show_footer'] ?? '1') != '1') ? 'display:none;' : '' ?>">
                <div class="ml-divider-rule ml-footer-rule"></div>

                <!-- 3-Column Footer -->
                <div class="ml-footer ml-block" data-block="block_footer" style="<?= $getPos('block_footer') ?>">
                    <!-- Hospital & Office Address -->
                    <div class="ml-footer-col ml-footer-address">
                        <div class="ml-footer-item">
                            <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                            </svg>
                            <div class="ml-footer-text">
                                <div><?= $hosp_addr ?></div>
                                <div class="ml-reg-office"><?= $reg_office_nl ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp & Phone Numbers -->
                    <div class="ml-footer-col ml-footer-contact">
                        <div class="ml-footer-item">
                            <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                <path d="M16.75 13.96c.25.13.41.2.46.3.06.11.04.61-.21 1.18-.25.56-1.23 1.1-1.74 1.15-.46.04-1.02.07-2.06-.34-1.49-.59-2.73-1.63-3.69-2.77-.97-1.14-1.72-2.51-1.89-3.08-.18-.58-.02-.9.12-1.17.13-.25.29-.48.44-.65.15-.17.29-.26.39-.26.11 0 .22 0 .32.01.12.01.27-.04.42.33.15.37.52 1.28.57 1.38.05.1.08.22.02.34-.06.12-.13.23-.22.34-.1.1-.2.23-.29.33-.1.1-.21.21-.09.42.12.21.54.89 1.16 1.44.8.71 1.48.93 1.69 1.04.21.11.33.09.45-.05.13-.14.54-.63.69-.85.14-.21.3-.18.5-.1.21.08 1.32.62 1.55.73zM12 2a10 10 0 0 0-8.66 15L2 22l5.17-1.32A10 10 0 1 0 12 2z"/>
                            </svg>
                            <span><?= htmlspecialchars($cfg['phone_whatsapp'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="ml-footer-item">
                            <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-2.2 2.2a15.053 15.053 0 0 1-6.59-6.59l2.2-2.21a.96.96 0 0 0 .25-1.01A11.36 11.36 0 0 1 8.57 3.9c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.52c0-.55-.45-1-.99-1z"/>
                            </svg>
                            <span><?= htmlspecialchars($cfg['phone_landline'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>

                    <!-- Email & Website -->
                    <div class="ml-footer-col ml-footer-online">
                        <div class="ml-footer-item">
                            <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <span><?= htmlspecialchars($cfg['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="ml-footer-item">
                            <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                            </svg>
                            <span><?= htmlspecialchars($cfg['website'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="ml-page-indicator">Page 1 of <?= $pages ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- PAGE 2: CONTINUATION SHEET (ONLY HEADER & FOOTER, NO PATIENT / VITALS)    -->
    <!-- ========================================================================= -->
    <?php if ($pages >= 2 && !$isPreview): ?>
        <div class="motherland-sheet page-2" style="<?= $themeStyle ?>--ml-font: <?= $fontCss ?>; --ml-wm-opacity: <?= $wmOpacity ?>; --ml-wm-size: <?= $wmSize ?>mm; --ml-page-width: <?= $pageWidthStr ?>; --ml-page-height: <?= $pageHeightStr ?>; --ml-page-padding: <?= $pagePadStr ?>; --ml-header-height: <?= $headerHeight ?>mm; --ml-footer-height: <?= $footerHeight ?>mm;">
            <!-- Watermark -->
            <?php if (!empty($cfg['show_watermark'])): ?>
                <div class="ml-watermark ml-wm-<?= htmlspecialchars($wmPos, ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= $iconSrc ?>" alt="" class="ml-watermark-img">
                </div>
            <?php endif; ?>

            <!-- Header: Blank vs Digital -->
            <?php if ($headerMode === 'blank'): ?>
                <div class="ml-header-blank-zone" style="height: <?= $headerHeight ?>mm;"></div>
            <?php else: ?>
                <!-- Top Left Accent Bar -->
                <div class="ml-top-accent" style="height: <?= (int)$headerHeight ?>mm;"></div>

                <div class="ml-header-section">
                    <div class="ml-brand">
                        <img src="<?= $iconSrc ?>" alt="Logo" class="ml-brand-icon">
                        <div class="ml-brand-text">
                            <div class="ml-hospital-name"><?= htmlspecialchars($cfg['hospital_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="ml-hospital-tagline">— <?= htmlspecialchars($cfg['hospital_tagline'], ENT_QUOTES, 'UTF-8') ?> —</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Full Blank Continuation Canvas (Only Header & Footer on Page 2) -->
            <div class="ml-consultation-body ml-consultation-page-2"></div>

            <!-- Footer: Blank vs Digital -->
            <?php if ($footerMode === 'blank'): ?>
                <div class="ml-footer-blank-zone" style="height: <?= $footerHeight ?>mm;"></div>
            <?php else: ?>
                <div class="ml-divider-rule ml-footer-rule"></div>

                <!-- 3-Column Footer -->
                <div class="ml-footer">
                        <div class="ml-footer-col ml-footer-address">
                            <div class="ml-footer-item">
                                <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                                </svg>
                                <div class="ml-footer-text">
                                    <div><?= $hosp_addr ?></div>
                                    <div class="ml-reg-office"><?= $reg_office_nl ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="ml-footer-col ml-footer-contact">
                            <div class="ml-footer-item">
                                <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                    <path d="M16.75 13.96c.25.13.41.2.46.3.06.11.04.61-.21 1.18-.25.56-1.23 1.1-1.74 1.15-.46.04-1.02.07-2.06-.34-1.49-.59-2.73-1.63-3.69-2.77-.97-1.14-1.72-2.51-1.89-3.08-.18-.58-.02-.9.12-1.17.13-.25.29-.48.44-.65.15-.17.29-.26.39-.26.11 0 .22 0 .32.01.12.01.27-.04.42.33.15.37.52 1.28.57 1.38.05.1.08.22.02.34-.06.12-.13.23-.22.34-.1.1-.2.23-.29.33-.1.1-.21.21-.09.42.12.21.54.89 1.16 1.44.8.71 1.48.93 1.69 1.04.21.11.33.09.45-.05.13-.14.54-.63.69-.85.14-.21.3-.18.5-.1.21.08 1.32.62 1.55.73zM12 2a10 10 0 0 0-8.66 15L2 22l5.17-1.32A10 10 0 1 0 12 2z"/>
                                </svg>
                                <span><?= htmlspecialchars($cfg['phone_whatsapp'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="ml-footer-item">
                                <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                    <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-2.2 2.2a15.053 15.053 0 0 1-6.59-6.59l2.2-2.21a.96.96 0 0 0 .25-1.01A11.36 11.36 0 0 1 8.57 3.9c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.52c0-.55-.45-1-.99-1z"/>
                                </svg>
                                <span><?= htmlspecialchars($cfg['phone_landline'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <div class="ml-footer-col ml-footer-online">
                            <div class="ml-footer-item">
                                <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                                <span><?= htmlspecialchars($cfg['email'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="ml-footer-item">
                                <svg class="ml-icon" viewBox="0 0 24 24" fill="var(--ml-icon, #02872e)">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                </svg>
                                <span><?= htmlspecialchars($cfg['website'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="ml-page-indicator">Page 2 of <?= $pages ?></div>
                        </div>
                    </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

function get_motherland_opd_css(): string {
    return <<<'CSS'
/* Motherland Hospital OPD Form CSS - Dynamic Universal Page Architecture */
.motherland-sheet {
    position: relative;
    width: var(--ml-page-width, 210mm);
    height: var(--ml-page-height, 297mm);
    max-height: var(--ml-page-height, 297mm);
    margin: 0 auto;
    background: #ffffff;
    box-sizing: border-box;
    padding: var(--ml-page-padding, 6mm 12mm 6mm 12mm);
    font-family: var(--ml-font, Arial, "Helvetica Neue", Helvetica, sans-serif) !important;
    color: #111111;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.motherland-sheet.page-2 {
    margin-top: 0;
}

/* 1. Top Left Accent Green Bar */
.ml-top-accent {
    position: absolute;
    top: 0;
    left: 0;
    width: 7.5mm;
    height: 28mm;
    background-color: var(--ml-accent, #02872e);
}

/* 2. Premium Watermark (Configurable Position & Size) */
.ml-watermark {
    position: absolute;
    width: var(--ml-wm-size, 105mm);
    height: var(--ml-wm-size, 105mm);
    pointer-events: none;
    opacity: var(--ml-wm-opacity, 0.06);
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ml-watermark.ml-wm-bottom-right {
    right: 5mm;
    bottom: 24mm;
}

.ml-watermark.ml-wm-center {
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.ml-watermark.ml-wm-bottom-center {
    bottom: 24mm;
    left: 50%;
    transform: translateX(-50%);
}

.ml-watermark.ml-wm-bottom-left {
    left: 10mm;
    bottom: 24mm;
}

.ml-watermark.ml-wm-top-right {
    right: 10mm;
    top: 35mm;
}

.ml-watermark-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: none;
}

/* 3. Brand / Logo Header */
.ml-header-section {
    display: flex;
    align-items: center;
    margin-top: 3.5mm;
    margin-bottom: 3.5mm;
    padding-left: 0;
    z-index: 2;
}

.ml-brand {
    display: flex;
    align-items: center;
    gap: 3.5mm;
}

.ml-brand-icon {
    height: 13.5mm;
    width: auto;
    object-fit: contain;
    image-rendering: -webkit-optimize-contrast;
}

.ml-brand-text {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.ml-hospital-name {
    color: var(--ml-primary, #00783e);
    font-family: Georgia, "Times New Roman", Times, serif;
    font-weight: 700;
    font-size: 24pt;
    line-height: 1.05;
    letter-spacing: -0.2px;
}

.ml-hospital-tagline {
    color: #444444;
    font-family: Arial, sans-serif;
    font-size: 8pt;
    letter-spacing: 4px;
    text-transform: uppercase;
    text-align: center;
    margin-top: 1.5px;
}

/* Pre-printed Letterhead Pad Blank Spacing Zones */
.ml-header-blank-zone {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-sizing: border-box;
}

.ml-footer-blank-zone {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-sizing: border-box;
}

.ml-zone-blank-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 12px;
    background: #f0fdf4;
    border: 1.2px dashed #16a34a;
    border-radius: 4px;
    font-size: 8.5pt;
    font-weight: 600;
    color: #166534;
    pointer-events: none;
}

/* 4. Document Title (Centered Consultation Paper OPD - Image 2 Green Style) */
.ml-title-section {
    text-align: center;
    margin-top: 2mm;
    margin-bottom: 2.5mm;
    z-index: 2;
}

.ml-title {
    margin: 0;
    font-size: 13.5pt;
    font-weight: 700;
    color: var(--ml-primary, #00783e);
    letter-spacing: 0.2px;
}

/* Divider Rules */
.ml-divider-rule {
    border-top: 1pt solid var(--ml-border, #222222);
    margin: 1.5mm 0 2mm 0;
    width: 100%;
    z-index: 2;
}

.ml-meta-top-rule {
    border-top: 1pt solid var(--ml-border, #222222);
    margin: 1.5mm 0 2mm 0;
}

.ml-meta-bottom-rule {
    border-top: 1pt solid var(--ml-border, #222222);
    margin: 2mm 0 2.5mm 0;
}

.ml-footer-rule {
    border-top: 0.8pt solid var(--ml-border, #666666);
    margin: 2mm 0 3mm 0;
}

/* 5. Patient Information Section (Structured 2 Columns - User Image 2) */
.ml-patient-section {
    width: 100%;
    z-index: 2;
    box-sizing: border-box;
}

/* Styles: Divider (Image 2 style), Box, or None */
.ml-patient-section.ml-style-divider .ml-meta-top-rule {
    display: block;
}

.ml-patient-section.ml-style-divider .ml-meta-bottom-rule {
    display: block;
}

.ml-patient-section.ml-style-box {
    border: 1pt solid var(--ml-border, #222222);
    border-radius: 3px;
    padding: 2mm 3.5mm;
    margin: 1.5mm 0 2.5mm 0;
}

.ml-patient-section.ml-style-box .ml-meta-top-rule,
.ml-patient-section.ml-style-box .ml-meta-bottom-rule {
    display: none;
}

.ml-patient-section.ml-style-none .ml-meta-top-rule,
.ml-patient-section.ml-style-none .ml-meta-bottom-rule {
    display: none;
}

/* Densities: Compact, Normal, Relaxed */
.ml-patient-section.ml-density-compact .ml-field-row {
    margin-bottom: 0.7mm;
    line-height: 1.25;
}

.ml-patient-section.ml-density-normal .ml-field-row {
    margin-bottom: 1.2mm;
    line-height: 1.4;
}

.ml-patient-section.ml-density-relaxed .ml-field-row {
    margin-bottom: 1.8mm;
    line-height: 1.55;
}

.ml-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    position: relative;
    font-size: 9.2pt;
    line-height: 1.4;
    z-index: 2;
}

.ml-col-left {
    padding-right: 5mm;
    border-right: none;
}

.ml-col-right {
    padding-left: 5mm;
}

.ml-field-row {
    display: grid;
    grid-template-columns: 24mm 3mm 1fr;
    align-items: baseline;
    margin-bottom: 1.1mm;
    line-height: 1.35;
}

.ml-label {
    width: auto;
    font-weight: 500;
    color: var(--ml-label, #222222);
}

.ml-col-right .ml-label {
    width: auto;
}

.ml-sep {
    width: auto;
    text-align: center;
    font-weight: 500;
    color: var(--ml-label, #222222);
}

.ml-value {
    flex: 1;
    color: var(--ml-text, #111111);
    font-weight: 500;
    word-break: break-word;
}

.font-bold {
    font-weight: 700;
}

/* 6. Doctor Banner & Vitals Measurements Table */
.ml-doctor-vitals-box {
    margin-top: 2.5mm;
    border-top: 1.2pt solid var(--ml-border, #555555);
    border-bottom: 1.2pt solid var(--ml-border, #555555);
    background: #ffffff;
    z-index: 2;
}

.ml-doc-header {
    text-align: center;
    padding: 1.2mm 3mm;
    border-bottom: 1pt solid var(--ml-border, #555555);
    background: #ffffff;
}

.ml-doctor-name {
    font-size: 11pt;
    font-weight: 800;
    color: var(--ml-primary, #000000);
    letter-spacing: 0.4px;
}

.ml-vitals-table {
    font-size: 8.8pt;
}

.ml-vitals-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    padding: 0;
}

.ml-vitals-row-2 {
    border-top: 0.8pt solid var(--ml-border, #555555);
}

.ml-v-cell {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: 1.4mm 2.8mm;
    border-right: none;
    box-sizing: border-box;
}

.ml-v-cell:last-child {
    border-right: none;
}

.ml-v-cell.ml-hide {
    visibility: hidden;
}

.v-lbl {
    font-weight: 600;
    color: var(--ml-label, #111111);
}

.v-unit {
    color: var(--ml-text, #111111);
    font-weight: 400;
}

/* Rx Table when items exist */
.ml-rx-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 3.5mm;
    font-size: 8.8pt;
    color: #111111;
}
.ml-rx-table th {
    border: none;
    border-bottom: 0.8pt solid var(--ml-border, #555555);
    background: transparent;
    color: #111111;
    font-weight: 700;
    padding: 1.8mm 2.5mm;
    text-align: left;
}
.ml-rx-table td {
    border: none;
    border-bottom: 0.5pt dashed var(--ml-border, #cccccc);
    padding: 1.8mm 2.5mm;
    color: #111111;
}

/* 7. Consultation Writing Area */
.ml-consultation-body {
    flex: 1 1 auto;
    min-height: 0;
    z-index: 2;
    position: relative;
}

.ml-rx-watermark {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 32pt;
    font-weight: 700;
    color: var(--ml-primary, #00783e);
    opacity: 0.15;
    line-height: 1;
    margin-top: 2.5mm;
    margin-left: 1mm;
    user-select: none;
    pointer-events: none;
}

.ml-consultation-page-2 {
    flex: 1 1 auto;
    min-height: 0;
    z-index: 2;
}

/* 8. Validity Note */
.ml-validity-section {
    margin-top: auto;
    margin-bottom: 1.2mm;
    flex-shrink: 0;
    z-index: 2;
    page-break-inside: avoid;
    break-inside: avoid;
}

.ml-validity-note {
    font-size: 8.5pt;
    color: var(--ml-text, #111111);
}

.ml-validity-note strong {
    font-weight: 700;
}

.ml-validity-note u em {
    font-style: italic;
    text-decoration: underline;
    font-weight: 600;
}

/* Footer Rule - Clean Emerald Accent Line */
.ml-footer-rule {
    border-top: 1.2pt solid var(--ml-accent, var(--ml-primary, #02872e));
    margin: 1.5mm 0 2mm 0;
    flex-shrink: 0;
    page-break-inside: avoid;
    break-inside: avoid;
}

/* 9. Footer Section - Modern 3-Column Hospital Grid without ugly vertical borders */
.ml-footer {
    display: grid;
    grid-template-columns: 1.35fr 1fr 1.05fr;
    column-gap: 5mm;
    font-size: 7.6pt;
    line-height: 1.35;
    color: var(--ml-text, #111111);
    z-index: 2;
    flex-shrink: 0;
    page-break-inside: avoid;
    break-inside: avoid;
}

.ml-footer-col {
    display: flex;
    flex-direction: column;
    gap: 1.6mm;
}

.ml-footer-contact {
    border-left: none;
    padding-left: 0;
}

.ml-footer-online {
    border-left: none;
    padding-left: 0;
}

.ml-footer-item {
    display: flex;
    align-items: flex-start;
    gap: 1.8mm;
}

.ml-icon {
    width: 3.5mm;
    height: 3.5mm;
    flex-shrink: 0;
    margin-top: 0.2mm;
    fill: var(--ml-icon, var(--ml-primary, #02872e));
}

.ml-footer-text {
    flex: 1;
}

.ml-reg-office {
    margin-top: 0.8mm;
    color: var(--ml-text, #222222);
}

/* Bottom Meta Row (Validity Note + Doctor Signature) */
.ml-bottom-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: auto;
    margin-bottom: 1.5mm;
    flex-shrink: 0;
    z-index: 2;
    page-break-inside: avoid;
    break-inside: avoid;
}

.ml-sign-section {
    width: 48mm;
    text-align: center;
    flex-shrink: 0;
}

.ml-sign-line {
    border-top: 0.9pt dashed #4b5563;
    margin-bottom: 1.5mm;
    width: 100%;
}

.ml-sign-text {
    font-size: 7.8pt;
    font-weight: 700;
    color: #1f2937;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.ml-page-indicator {
    font-size: 7.2pt;
    font-weight: 600;
    color: #64748b;
    margin-top: 1mm;
    text-align: right;
}

/* Page 2 Continuation Strip */
.ml-page2-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border-top: 1pt solid #cbd5e1;
    border-bottom: 1pt solid #cbd5e1;
    padding: 1.5mm 2mm;
    margin-top: 2mm;
    margin-bottom: 3mm;
    font-size: 8.2pt;
    z-index: 2;
}

.ml-page2-title {
    font-weight: 800;
    color: var(--ml-primary, #02872e);
    letter-spacing: 0.4px;
}

.ml-page2-patient {
    font-weight: 600;
    color: #334155;
}

/* Draggable Block styling for Layout Editor */
.draggable-block {
    cursor: move;
    user-select: none;
    transition: outline 0.15s ease;
}
.draggable-block:hover {
    outline: 1.5px dashed #18a96a;
    outline-offset: 2px;
}
.draggable-block.selected {
    outline: 2px solid #0e9b60 !important;
    outline-offset: 3px;
    background: rgba(234, 255, 244, 0.35);
}

/* Preview scaling */
.motherland-sheet.is-preview {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transform-origin: top left;
}

@media print {
    *, *::before, *::after {
        box-sizing: border-box !important;
    }
    .motherland-sheet {
        box-shadow: none !important;
        margin: 0 !important;
        width: var(--ml-page-width, 210mm) !important;
        height: var(--ml-page-height, 297mm) !important;
        max-height: var(--ml-page-height, 297mm) !important;
        padding: var(--ml-page-padding, 6mm 12mm 6mm 12mm) !important;
        page-break-after: always !important;
        break-after: page !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
    }
    .motherland-sheet:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }
    .motherland-sheet.page-2 {
        page-break-before: auto !important;
        break-before: auto !important;
        margin: 0 !important;
    }
}
CSS;
}
