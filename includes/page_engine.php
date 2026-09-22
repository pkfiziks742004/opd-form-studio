<?php
declare(strict_types=1);

/**
 * Universal Template Page Engine
 * Handles dynamic paper sizes, orientations, margins, and print CSS generation
 * for all OPD and prescription templates.
 */

if (!function_exists('get_paper_presets')) {

    /**
     * Standard built-in paper size presets with base dimensions in portrait (mm).
     */
    function get_paper_presets(): array {
        return [
            'A4' => [
                'name' => 'A4',
                'width' => 210.0,
                'height' => 297.0,
                'unit' => 'mm',
                'desc' => '210 × 297 mm (Standard OPD Consultation Sheet)',
                'css_keyword' => 'A4'
            ],
            'A5' => [
                'name' => 'A5',
                'width' => 148.0,
                'height' => 210.0,
                'unit' => 'mm',
                'desc' => '148 × 210 mm (Small / Clinic Prescription)',
                'css_keyword' => 'A5'
            ],
            'A6' => [
                'name' => 'A6',
                'width' => 105.0,
                'height' => 148.0,
                'unit' => 'mm',
                'desc' => '105 × 148 mm (Pocket Slip / Rx Memo)',
                'css_keyword' => 'A6'
            ],
            'Letter' => [
                'name' => 'Letter',
                'width' => 215.9,
                'height' => 279.4,
                'unit' => 'mm',
                'desc' => '8.5 × 11 inch (215.9 × 279.4 mm)',
                'css_keyword' => 'letter'
            ],
            'Legal' => [
                'name' => 'Legal',
                'width' => 215.9,
                'height' => 355.6,
                'unit' => 'mm',
                'desc' => '8.5 × 14 inch (215.9 × 355.6 mm)',
                'css_keyword' => 'legal'
            ],
            'Executive' => [
                'name' => 'Executive',
                'width' => 184.15,
                'height' => 266.7,
                'unit' => 'mm',
                'desc' => '7.25 × 10.5 inch (184.2 × 266.7 mm)',
                'css_keyword' => 'executive'
            ],
            'Custom' => [
                'name' => 'Custom',
                'width' => 210.0,
                'height' => 297.0,
                'unit' => 'mm',
                'desc' => 'User-defined Dimensions (Width × Height)',
                'css_keyword' => null
            ]
        ];
    }

    /**
     * Idempotent column check for templates table.
     */
    function ensure_template_page_columns(): void {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            $pdo = db();
            $st = $pdo->query("SHOW COLUMNS FROM templates");
            $existing = [];
            while ($r = $st->fetch()) {
                $existing[strtolower($r['Field'])] = true;
            }

            $add = [
                'page_size' => "VARCHAR(20) NOT NULL DEFAULT 'A4'",
                'orientation' => "VARCHAR(20) NOT NULL DEFAULT 'portrait'",
                'page_width' => "DECIMAL(8,2) NULL DEFAULT 210.00",
                'page_height' => "DECIMAL(8,2) NULL DEFAULT 297.00",
                'page_unit' => "VARCHAR(10) NOT NULL DEFAULT 'mm'",
                'margin_top' => "DECIMAL(8,2) NULL DEFAULT 6.00",
                'margin_right' => "DECIMAL(8,2) NULL DEFAULT 12.00",
                'margin_bottom' => "DECIMAL(8,2) NULL DEFAULT 6.00",
                'margin_left' => "DECIMAL(8,2) NULL DEFAULT 12.00",
                'page_config_json' => "TEXT NULL"
            ];

            foreach ($add as $col => $sqlDef) {
                if (!isset($existing[strtolower($col)])) {
                    $pdo->exec("ALTER TABLE templates ADD COLUMN $col $sqlDef");
                }
            }
        } catch (Throwable $e) {
            // Log or ignore in restricted permissions environment
        }
    }

    /**
     * Extract and normalize page configuration for a template.
     */
    function get_template_page_config(array $template): array {
        ensure_template_page_columns();

        $presets = get_paper_presets();

        $size = trim((string)($template['page_size'] ?? 'A4'));
        if ($size === '' || (!isset($presets[$size]) && strtolower($size) !== 'custom')) {
            $size = 'A4';
        }

        $orientation = strtolower(trim((string)($template['orientation'] ?? 'portrait')));
        if ($orientation !== 'landscape') {
            $orientation = 'portrait';
        }

        $unit = strtolower(trim((string)($template['page_unit'] ?? 'mm')));
        if (!in_array($unit, ['mm', 'cm', 'in', 'inch'], true)) {
            $unit = 'mm';
        }
        if ($unit === 'inch') $unit = 'in';

        // Base width & height from preset or custom values
        if ($size !== 'Custom' && isset($presets[$size])) {
            $baseWidth = (float)$presets[$size]['width'];
            $baseHeight = (float)$presets[$size]['height'];
            $baseUnit = $presets[$size]['unit'];
        } else {
            $baseWidth = (float)($template['page_width'] ?? 210.0);
            if ($baseWidth <= 0) $baseWidth = 210.0;
            $baseHeight = (float)($template['page_height'] ?? 297.0);
            if ($baseHeight <= 0) $baseHeight = 297.0;
            $baseUnit = $unit;
        }

        // Default margins
        $marginTop = isset($template['margin_top']) ? (float)$template['margin_top'] : 3.0;
        $marginRight = isset($template['margin_right']) ? (float)$template['margin_right'] : 12.0;
        $marginBottom = isset($template['margin_bottom']) ? (float)$template['margin_bottom'] : 3.0;
        $marginLeft = isset($template['margin_left']) ? (float)$template['margin_left'] : 12.0;

        // Overlay with page_config_json if present
        if (!empty($template['page_config_json'])) {
            $json = json_decode((string)$template['page_config_json'], true);
            if (is_array($json)) {
                if (!empty($json['pageSize'])) $size = $json['pageSize'];
                if (!empty($json['orientation'])) $orientation = strtolower($json['orientation']);
                if (isset($json['width'])) $baseWidth = (float)$json['width'];
                if (isset($json['height'])) $baseHeight = (float)$json['height'];
                if (!empty($json['unit'])) $baseUnit = $json['unit'];
                if (isset($json['marginTop'])) $marginTop = (float)$json['marginTop'];
                if (isset($json['marginRight'])) $marginRight = (float)$json['marginRight'];
                if (isset($json['marginBottom'])) $marginBottom = (float)$json['marginBottom'];
                if (isset($json['marginLeft'])) $marginLeft = (float)$json['marginLeft'];
            }
        }

        // Apply Orientation swap
        if ($orientation === 'landscape') {
            $effWidth = $baseHeight;
            $effHeight = $baseWidth;
        } else {
            $effWidth = $baseWidth;
            $effHeight = $baseHeight;
        }

        // Printable content bounds
        $contentWidth = max(20.0, $effWidth - $marginLeft - $marginRight);
        $contentHeight = max(20.0, $effHeight - $marginTop - $marginBottom);

        // Aspect ratio for preview containers
        $aspectRatio = round($effWidth / $effHeight, 4);

        // Conversion to mm for scaling reference (A4 is 210mm x 297mm)
        $mmFactor = ($baseUnit === 'in' || $baseUnit === 'inch') ? 25.4 : (($baseUnit === 'cm') ? 10.0 : 1.0);
        $widthInMm = $effWidth * $mmFactor;
        $heightInMm = $effHeight * $mmFactor;

        // Scale ratio compared to standard A4 (297mm)
        $scaleFactor = round($heightInMm / 297.0, 3);

        return [
            'pageSize' => $size,
            'orientation' => $orientation,
            'baseWidth' => $baseWidth,
            'baseHeight' => $baseHeight,
            'width' => $effWidth,
            'height' => $effHeight,
            'unit' => $baseUnit,
            'marginTop' => $marginTop,
            'marginRight' => $marginRight,
            'marginBottom' => $marginBottom,
            'marginLeft' => $marginLeft,
            'contentWidth' => $contentWidth,
            'contentHeight' => $contentHeight,
            'aspectRatio' => $aspectRatio,
            'widthInMm' => $widthInMm,
            'heightInMm' => $heightInMm,
            'scaleFactor' => $scaleFactor,
            'isCustom' => ($size === 'Custom'),
            'label' => ($size === 'Custom' ? "Custom ({$effWidth}×{$effHeight} {$baseUnit})" : $size) . ' ' . ucfirst($orientation)
        ];
    }

    /**
     * Generate dynamic print and preview CSS for a template.
     */
    function generate_template_page_css(array $template, bool $isModal = false): string {
        $p = get_template_page_config($template);

        $wStr = $p['width'] . $p['unit'];
        $hStr = $p['height'] . $p['unit'];
        $padStr = "{$p['marginTop']}{$p['unit']} {$p['marginRight']}{$p['unit']} {$p['marginBottom']}{$p['unit']} {$p['marginLeft']}{$p['unit']}";

        // Format @page declaration
        // Explicit dimensions ensures identical rendering across Chrome, PDF and physical print
        $pageDeclaration = "@page { size: {$wStr} {$hStr}; margin: 0; }";

        // Dynamic scaling rules for smaller paper formats (e.g. A5, A6)
        $fontSizePt = 9.2;
        $hospNamePt = 24.0;
        $titlePt = 13.5;
        $docHeaderPt = 11.0;
        $vitalsPt = 8.8;
        $footerPt = 7.6;
        $lineHeight = 1.4;

        if ($p['scaleFactor'] < 0.75) { // A5, A6 or compact custom
            $fontSizePt = round(9.2 * max(0.72, $p['scaleFactor']), 1);
            $hospNamePt = round(24.0 * max(0.70, $p['scaleFactor']), 1);
            $titlePt = round(13.5 * max(0.72, $p['scaleFactor']), 1);
            $docHeaderPt = round(11.0 * max(0.72, $p['scaleFactor']), 1);
            $vitalsPt = round(8.8 * max(0.72, $p['scaleFactor']), 1);
            $footerPt = round(7.6 * max(0.72, $p['scaleFactor']), 1);
            $lineHeight = 1.25;
        }

        return <<<CSS
/* ==========================================================================
   UNIVERSAL TEMPLATE-DRIVEN PAGE SIZE ENGINE
   Page Size: {$p['pageSize']} | Orientation: {$p['orientation']} | Dimensions: {$wStr} × {$hStr}
   ========================================================================== */

{$pageDeclaration}

body {
    overflow-x: hidden !important;
}

/* Unified Universal Sheet Container */
.motherland-sheet {
    position: relative !important;
    width: {$wStr} !important;
    max-width: 100% !important;
    height: {$hStr} !important;
    max-height: {$hStr} !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
    margin: 0 auto;
    background: #ffffff;
    padding: {$padStr} !important;
    font-size: {$fontSizePt}pt;
    line-height: {$lineHeight};
    display: flex;
    flex-direction: column;
}

/* Image template dynamic dimensions */
.sheet {
    position: relative !important;
    width: {$wStr} !important;
    height: {$hStr} !important;
    max-height: {$hStr} !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
    margin: 80px auto 40px;
    background: #ffffff center/100% 100% no-repeat;
    padding: 0 !important;
    display: block !important;
    box-shadow: 0 16px 50px rgba(0,0,0,0.15);
}

/* Screen Mode Wrapper */
.code-sheet-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 30px;
    margin: 80px auto 40px;
    background: transparent;
}

/* Specific elements scaling */
.motherland-sheet .ml-hospital-name {
    font-size: {$hospNamePt}pt;
}
.motherland-sheet .ml-title {
    font-size: {$titlePt}pt;
}
.motherland-sheet .ml-doctor-name {
    font-size: {$docHeaderPt}pt;
}
.motherland-sheet .ml-vitals-table {
    font-size: {$vitalsPt}pt;
}
.motherland-sheet .ml-footer {
    font-size: {$footerPt}pt;
}

/* Doctor consultation canvas flex-grow */
.motherland-sheet .ml-consultation-body {
    flex: 1 1 auto !important;
    min-height: 0 !important;
}

/* Footer and Signature pinned to bottom */
.motherland-sheet .ml-bottom-meta-row {
    margin-top: auto !important;
    flex-shrink: 0 !important;
}
.motherland-sheet .ml-footer-rule,
.motherland-sheet .ml-footer {
    flex-shrink: 0 !important;
}

/* ==========================================================================
   PRINT ENGINE MEDIA QUERY
   ========================================================================== */
@media print {
    *, *::before, *::after {
        box-sizing: border-box !important;
    }

    html, body {
        width: {$wStr} !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        font-size: 0 !important;
        line-height: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .toolbar,
    .print-modal-header,
    .editor-toolbar {
        display: none !important;
    }

    .code-sheet-wrapper {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
        font-size: 0 !important;
        line-height: 0 !important;
        gap: 0 !important;
    }

    .motherland-sheet {
        position: relative !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: none !important;
        width: {$wStr} !important;
        height: {$hStr} !important;
        max-height: {$hStr} !important;
        padding: {$padStr} !important;
        font-size: {$fontSizePt}pt !important;
        line-height: {$lineHeight} !important;
        page-break-after: always !important;
        break-after: page !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .sheet {
        position: relative !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: none !important;
        width: {$wStr} !important;
        height: {$hStr} !important;
        max-height: {$hStr} !important;
        padding: 0 !important;
        page-break-after: always !important;
        break-after: page !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
        display: block !important;
    }

    .motherland-sheet:last-child,
    .sheet:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
    }

    .motherland-sheet.page-2 {
        page-break-before: auto !important;
        break-before: auto !important;
        margin: 0 !important;
    }

    .motherland-sheet .ml-consultation-body {
        flex: 1 1 auto !important;
        min-height: 20mm !important;
    }

    .motherland-sheet .ml-bottom-meta-row {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-end !important;
        margin-top: auto !important;
        margin-bottom: 1.5mm !important;
        flex-shrink: 0 !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .motherland-sheet .ml-footer-rule {
        border-top: 1.2pt solid #02872e !important;
        margin: 1.5mm 0 2mm 0 !important;
        flex-shrink: 0 !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .motherland-sheet .ml-footer {
        flex-shrink: 0 !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .motherland-sheet .ml-footer-contact,
    .motherland-sheet .ml-footer-online {
        border-left: none !important;
        padding-left: 0 !important;
    }
}
CSS;
    }
}
