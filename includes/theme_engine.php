<?php
declare(strict_types=1);

/**
 * Universal Template Theme Engine
 * Manages template-specific healthcare color palettes, custom colors,
 * live CSS variables, and WCAG accessibility contrast checks.
 */

if (!function_exists('get_theme_presets')) {

    /**
     * Predefined professional healthcare color palettes.
     */
    function get_theme_presets(): array {
        return [
            'green' => [
                'id' => 'green',
                'name' => 'Medical Green',
                'primary' => '#087F6C',
                'secondary' => '#075E54',
                'accent' => '#10B981',
                'header' => '#FFFFFF',
                'footer' => '#FFFFFF',
                'border' => '#222222',
                'text' => '#111827',
                'heading' => '#075E54',
                'label' => '#374151',
                'icon' => '#087F6C',
                'watermark' => '#087F6C',
                'watermarkOpacity' => 0.08
            ],
            'blue' => [
                'id' => 'blue',
                'name' => 'Medical Blue',
                'primary' => '#2563EB',
                'secondary' => '#1E40AF',
                'accent' => '#60A5FA',
                'header' => '#FFFFFF',
                'footer' => '#FFFFFF',
                'border' => '#1E40AF',
                'text' => '#111827',
                'heading' => '#1E40AF',
                'label' => '#374151',
                'icon' => '#2563EB',
                'watermark' => '#2563EB',
                'watermarkOpacity' => 0.08
            ],
            'teal' => [
                'id' => 'teal',
                'name' => 'Teal Medical',
                'primary' => '#0F766E',
                'secondary' => '#115E59',
                'accent' => '#14B8A6',
                'header' => '#FFFFFF',
                'footer' => '#FFFFFF',
                'border' => '#115E59',
                'text' => '#111827',
                'heading' => '#115E59',
                'label' => '#374151',
                'icon' => '#0F766E',
                'watermark' => '#0F766E',
                'watermarkOpacity' => 0.08
            ],
            'purple' => [
                'id' => 'purple',
                'name' => 'Purple Medical',
                'primary' => '#7C3AED',
                'secondary' => '#5B21B6',
                'accent' => '#A78BFA',
                'header' => '#FFFFFF',
                'footer' => '#FFFFFF',
                'border' => '#5B21B6',
                'text' => '#111827',
                'heading' => '#5B21B6',
                'label' => '#374151',
                'icon' => '#7C3AED',
                'watermark' => '#7C3AED',
                'watermarkOpacity' => 0.08
            ],
            'navy' => [
                'id' => 'navy',
                'name' => 'Navy Medical',
                'primary' => '#1E3A8A',
                'secondary' => '#172554',
                'accent' => '#3B82F6',
                'header' => '#FFFFFF',
                'footer' => '#FFFFFF',
                'border' => '#172554',
                'text' => '#111827',
                'heading' => '#172554',
                'label' => '#374151',
                'icon' => '#1E3A8A',
                'watermark' => '#1E3A8A',
                'watermarkOpacity' => 0.08
            ],
            'rose' => [
                'id' => 'rose',
                'name' => 'Rose Medical',
                'primary' => '#BE185D',
                'secondary' => '#831843',
                'accent' => '#F472B6',
                'header' => '#FFFFFF',
                'footer' => '#FFFFFF',
                'border' => '#831843',
                'text' => '#111827',
                'heading' => '#831843',
                'label' => '#374151',
                'icon' => '#BE185D',
                'watermark' => '#BE185D',
                'watermarkOpacity' => 0.08
            ],
            'orange' => [
                'id' => 'orange',
                'name' => 'Orange Medical',
                'primary' => '#EA580C',
                'secondary' => '#9A3412',
                'accent' => '#FB923C',
                'header' => '#FFFFFF',
                'footer' => '#FFFFFF',
                'border' => '#9A3412',
                'text' => '#111827',
                'heading' => '#9A3412',
                'label' => '#374151',
                'icon' => '#EA580C',
                'watermark' => '#EA580C',
                'watermarkOpacity' => 0.08
            ],
            'custom' => [
                'id' => 'custom',
                'name' => 'Custom Theme',
                'primary' => '#087F6C',
                'secondary' => '#075E54',
                'accent' => '#10B981',
                'header' => '#FFFFFF',
                'footer' => '#FFFFFF',
                'border' => '#222222',
                'text' => '#111827',
                'heading' => '#075E54',
                'label' => '#374151',
                'icon' => '#087F6C',
                'watermark' => '#087F6C',
                'watermarkOpacity' => 0.08
            ]
        ];
    }

    /**
     * Resolve effective color theme for a template.
     */
    function get_template_theme(?array $template): array {
        $presets = get_theme_presets();
        $defaultTheme = $presets['green'];

        if (!$template) return $defaultTheme;

        $presetKey = strtolower(trim((string)($template['theme_preset'] ?? 'green')));
        $base = $presets[$presetKey] ?? $defaultTheme;

        // Custom config override if present
        if (!empty($template['theme_config_json'])) {
            $custom = json_decode((string)$template['theme_config_json'], true);
            if (is_array($custom)) {
                $base = array_merge($base, $custom);
                if (!empty($custom['preset'])) {
                    $base['id'] = $custom['preset'];
                }
            }
        }

        // Validate required color fields
        $fields = ['primary', 'secondary', 'accent', 'header', 'footer', 'border', 'text', 'heading', 'label', 'icon', 'watermark'];
        foreach ($fields as $f) {
            $val = trim((string)($base[$f] ?? ''));
            if (!preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $val)) {
                $base[$f] = $defaultTheme[$f] ?? '#087F6C';
            }
        }

        $wmOpacity = (float)($base['watermarkOpacity'] ?? 0.08);
        if ($wmOpacity <= 0) $wmOpacity = 0.08;
        if ($wmOpacity > 1.0) $wmOpacity = round($wmOpacity / 100, 2);
        $base['watermarkOpacity'] = max(0.01, min(0.30, $wmOpacity));

        return $base;
    }

    /**
     * Generate inline CSS custom property string for a theme.
     */
    function generate_theme_style_attr(array $theme): string {
        return sprintf(
            '--ml-primary:%s;--ml-secondary:%s;--ml-accent:%s;--ml-border:%s;--ml-text:%s;--ml-heading:%s;--ml-label:%s;--ml-icon:%s;--ml-wm-color:%s;--ml-wm-opacity:%.2f;',
            $theme['primary'],
            $theme['secondary'],
            $theme['accent'],
            $theme['border'],
            $theme['text'],
            $theme['heading'],
            $theme['label'],
            $theme['icon'],
            $theme['watermark'],
            $theme['watermarkOpacity']
        );
    }

    /**
     * Calculate WCAG 2.1 relative luminance and contrast ratio between two hex colors.
     */
    function calculate_contrast_ratio(string $hex1, string $hex2): float {
        $lum = function(string $hex): float {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            if (strlen($hex) !== 6) return 0.5;
            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;

            $fn = function($c) {
                return ($c <= 0.03928) ? ($c / 12.92) : pow(($c + 0.055) / 1.055, 2.4);
            };

            return 0.2126 * $fn($r) + 0.7152 * $fn($g) + 0.0722 * $fn($b);
        };

        $l1 = $lum($hex1);
        $l2 = $lum($hex2);
        $brightest = max($l1, $l2);
        $darkest = min($l1, $l2);

        return round(($brightest + 0.05) / ($darkest + 0.05), 2);
    }
}
