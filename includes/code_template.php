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
        'watermark_size' => '105',
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

function get_motherland_config(): array {
    $defs = get_motherland_defaults();
    $cfg = [];
    foreach ($defs as $k => $def) {
        $val = setting('motherland_tpl_' . $k, '');
        $cfg[$k] = ($val !== '') ? $val : $def;
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

function render_motherland_opd(array $patient, array $config = [], bool $isPreview = false, array $layout = [], int $pages = 0, ?array $template = null): string {
    $cfg = array_merge(get_motherland_config(), $config);
    $pageConfig = get_template_page_config($template ?: []);
    $theme = get_template_theme($template);
    $themeStyle = generate_theme_style_attr($theme);
    $pageWidthStr = $pageConfig['width'] . $pageConfig['unit'];
    $pageHeightStr = $pageConfig['height'] . $pageConfig['unit'];
    $pagePadStr = "{$pageConfig['marginTop']}{$pageConfig['unit']} {$pageConfig['marginRight']}{$pageConfig['unit']} {$pageConfig['marginBottom']}{$pageConfig['unit']} {$pageConfig['marginLeft']}{$pageConfig['unit']}";

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
    $doc_name = !empty($cfg['doctor_name']) ? $cfg['doctor_name'] : 'Dr. ANVITI SARAF';

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

    ob_start();
    ?>
    <!-- ========================================== -->
    <!-- PAGE 1: FULL OPD CONSULTATION PAPER        -->
    <!-- ========================================== -->
    <div class="motherland-sheet page-1 <?= $isPreview ? 'is-preview' : '' ?>" id="motherlandSheet" style="<?= $themeStyle ?>--ml-wm-opacity: <?= $wmOpacity ?>; --ml-wm-size: <?= $wmSize ?>mm; --ml-page-width: <?= $pageWidthStr ?>; --ml-page-height: <?= $pageHeightStr ?>; --ml-page-padding: <?= $pagePadStr ?>;">
        <!-- Top Left Accent Bar -->
        <?php if (!empty($cfg['show_header'])): ?>
            <div class="ml-top-accent" style="height: <?= $accentHeight ?>mm;"></div>
        <?php endif; ?>

        <!-- Watermark (Customizable Position & Size) -->
        <?php if (!empty($cfg['show_watermark'])): ?>
            <div class="ml-watermark ml-wm-<?= htmlspecialchars($wmPos, ENT_QUOTES, 'UTF-8') ?>">
                <img src="<?= $iconSrc ?>" alt="" class="ml-watermark-img">
            </div>
        <?php endif; ?>

        <!-- 1. Header & Logo Area -->
        <?php if (!empty($cfg['show_header'])): ?>
            <div class="ml-header-section ml-block" data-block="block_logo" style="<?= $getPos('block_logo') ?>">
                <div class="ml-brand">
                    <img src="<?= $iconSrc ?>" alt="Logo" class="ml-brand-icon">
                    <div class="ml-brand-text">
                        <div class="ml-hospital-name"><?= htmlspecialchars($cfg['hospital_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="ml-hospital-tagline">— <?= htmlspecialchars($cfg['hospital_tagline'], ENT_QUOTES, 'UTF-8') ?> —</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. Document Title (Centered on its own line below logo) -->
        <?php if (!empty($cfg['show_title'])): ?>
            <div class="ml-title-section ml-block" data-block="block_title" style="<?= $getPos('block_title') ?>">
                <h1 class="ml-title"><?= htmlspecialchars($cfg['doc_title'], ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <div class="ml-divider-rule"></div>
        <?php endif; ?>

        <!-- 3. Patient Information & Billing (Two Columns - No Vertical Divider) -->
        <?php if (!empty($cfg['show_patient_info'])): ?>
            <div class="ml-meta-grid ml-block" data-block="block_meta" style="<?= $getPos('block_meta') ?>">
                <!-- Left Column -->
                <div class="ml-meta-col ml-col-left">
                    <?php if (!empty($cfg['show_uhid'])): ?>
                        <div class="ml-field-row" data-field="uhid">
                            <span class="ml-label"><?= htmlspecialchars($cfg['lbl_uhid'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="ml-sep">:</span>
                            <span class="ml-value ml-val-uhid"><?= htmlspecialchars($uhid, ENT_QUOTES, 'UTF-8') ?></span>
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
                            <span class="ml-value ml-val-bill"><?= htmlspecialchars($bill_no, ENT_QUOTES, 'UTF-8') ?></span>
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
        <?php endif; ?>

        <!-- 4. Combined Doctor Banner & Vitals Box -->
        <?php if (!empty($cfg['show_doctor_box']) || !empty($cfg['show_vitals'])): ?>
            <div class="ml-doctor-vitals-box ml-block" data-block="block_doctor_vitals" style="<?= $getPos('block_doctor_vitals') ?>">
                <!-- Doctor Name Header -->
                <?php if (!empty($cfg['show_doctor_box'])): ?>
                    <div class="ml-doc-header" style="<?= empty($cfg['show_vitals']) ? 'border-bottom:none;' : '' ?>">
                        <span class="ml-doctor-name"><?= htmlspecialchars($doc_name, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Vitals Measurements Table -->
                <?php if (!empty($cfg['show_vitals'])): ?>
                    <div class="ml-vitals-table">
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
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 5. Consultation Writing Canvas (Doctors Rx & Notes) -->
        <div class="ml-consultation-body"></div>

        <!-- 6. Bottom Meta Row: Validity Notice & Doctor's Signature -->
        <div class="ml-bottom-meta-row">
            <div class="ml-validity-section ml-block" data-block="block_validity" style="<?= $getPos('block_validity') ?>">
                <?php if (!empty($cfg['show_validity_note'])): ?>
                    <div class="ml-validity-note">
                        <strong>Note :</strong> <u><em><?= htmlspecialchars($validity, ENT_QUOTES, 'UTF-8') ?></em></u>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($cfg['show_signature_box'])): ?>
                <div class="ml-sign-section">
                    <div class="ml-sign-line"></div>
                    <div class="ml-sign-text"><?= htmlspecialchars($cfg['lbl_signature'] ?? "Doctor's Signature / Stamp", ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Separator Line -->
        <?php if (!empty($cfg['show_footer'])): ?>
            <div class="ml-divider-rule ml-footer-rule"></div>

            <!-- 7. 3-Column Footer -->
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
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- PAGE 2: CONTINUATION SHEET (ONLY HEADER & FOOTER, NO PATIENT / VITALS)    -->
    <!-- ========================================================================= -->
    <?php if ($pages >= 2 && !$isPreview): ?>
        <div class="motherland-sheet page-2" style="<?= $themeStyle ?>--ml-wm-opacity: <?= $wmOpacity ?>; --ml-wm-size: <?= $wmSize ?>mm; --ml-page-width: <?= $pageWidthStr ?>; --ml-page-height: <?= $pageHeightStr ?>; --ml-page-padding: <?= $pagePadStr ?>;">
            <!-- Top Left Accent Bar -->
            <?php if (!empty($cfg['show_header'])): ?>
                <div class="ml-top-accent" style="height: <?= $accentHeight ?>mm;"></div>
            <?php endif; ?>

            <!-- Watermark (Customizable Position & Size) -->
            <?php if (!empty($cfg['show_watermark'])): ?>
                <div class="ml-watermark ml-wm-<?= htmlspecialchars($wmPos, ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= $iconSrc ?>" alt="" class="ml-watermark-img">
                </div>
            <?php endif; ?>

            <!-- Header & Logo Area -->
            <?php if (!empty($cfg['show_header'])): ?>
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

            <!-- Footer Separator Line -->
            <?php if (!empty($cfg['show_footer'])): ?>
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
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
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
    filter: grayscale(10%);
}

/* 3. Brand / Logo Header */
.ml-header-section {
    display: flex;
    align-items: center;
    margin-top: 1mm;
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

/* 4. Document Title (Centered on its own line) */
.ml-title-section {
    text-align: center;
    margin-top: 5mm;
    margin-bottom: 2mm;
    z-index: 2;
}

.ml-title {
    margin: 0;
    font-size: 13.5pt;
    font-weight: 700;
    color: var(--ml-heading, #111111);
    letter-spacing: 0.2px;
}

/* Divider Rules */
.ml-divider-rule {
    border-top: 1pt solid var(--ml-border, #222222);
    margin: 1.5mm 0 2.5mm 0;
    width: 100%;
    z-index: 2;
}

.ml-meta-bottom-rule {
    margin: 2.5mm 0 2.5mm 0;
}

.ml-footer-rule {
    border-top: 0.8pt solid var(--ml-border, #666666);
    margin: 2mm 0 3mm 0;
}

/* 5. Patient Information (2 Columns) */
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
    padding-left: 6mm;
}

.ml-field-row {
    display: flex;
    align-items: baseline;
    margin-bottom: 1.2mm;
}

.ml-label {
    width: 25mm;
    font-weight: 500;
    color: var(--ml-label, #222222);
    flex-shrink: 0;
}

.ml-col-right .ml-label {
    width: 28mm;
}

.ml-sep {
    width: 4mm;
    text-align: center;
    font-weight: 500;
    flex-shrink: 0;
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
    border: 1.2pt solid var(--ml-border, #222222);
    background: #ffffff;
    z-index: 2;
}

.ml-doc-header {
    text-align: center;
    padding: 1.2mm 3mm;
    border-bottom: 1pt solid var(--ml-border, #222222);
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
    grid-template-columns: 1fr 1fr 1fr 1fr;
    padding: 1.4mm 3mm;
}

.ml-vitals-row-2 {
    border-top: 0.8pt solid var(--ml-border, #555555);
}

.ml-v-cell {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding-right: 3mm;
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

/* 7. Consultation Writing Area */
.ml-consultation-body {
    flex: 1 1 auto;
    min-height: 0;
    z-index: 2;
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
