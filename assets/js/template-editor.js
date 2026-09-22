(() => {
    const paper = document.getElementById('layoutPaper');
    const save = document.getElementById('saveLayout');
    const reset = document.getElementById('resetLayout');
    const size = document.getElementById('fontSize');
    const width = document.getElementById('fieldWidth');
    const sizeOut = document.getElementById('fontSizeValue');
    const widthOut = document.getElementById('fieldWidthValue');

    // Page Settings Elements
    const pageSizeSelect = document.getElementById('pageSizeSelect');
    const btnOrientPortrait = document.getElementById('btnOrientPortrait');
    const btnOrientLandscape = document.getElementById('btnOrientLandscape');
    const customDimensionsWrap = document.getElementById('customDimensionsWrap');
    const customWidthInput = document.getElementById('customWidth');
    const customHeightInput = document.getElementById('customHeight');
    const customUnitSelect = document.getElementById('customUnit');
    const marginTopInput = document.getElementById('marginTop');
    const marginRightInput = document.getElementById('marginRight');
    const marginBottomInput = document.getElementById('marginBottom');
    const marginLeftInput = document.getElementById('marginLeft');
    const marginUnitLabel = document.getElementById('marginUnitLabel');
    const metricDimensions = document.getElementById('metricDimensions');
    const metricPrintable = document.getElementById('metricPrintable');
    const badgePagePreset = document.getElementById('badgePagePreset');
    const btnSavePageSettings = document.getElementById('btnSavePageSettings');
    const previewBtn = document.getElementById('previewBtn');

    // Studio Sidebar Segmented Tab Switching
    const tabButtons = document.querySelectorAll('.studio-tab-btn');
    const tabPanes = document.querySelectorAll('.studio-tab-pane');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.tab;
            tabButtons.forEach(b => {
                const isActive = (b === btn);
                b.classList.toggle('active', isActive);
                b.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            tabPanes.forEach(pane => {
                pane.classList.toggle('active', pane.id === targetTab);
            });
        });
    });

    if (!paper) return;

    let selected = null;
    let drag = null;

    // --- PAGE SETTINGS STATE & LOGIC ---
    const presets = (window.EDITOR_DATA && window.EDITOR_DATA.paperPresets) ? window.EDITOR_DATA.paperPresets : {
        'A4': { width: 210, height: 297, unit: 'mm' },
        'A5': { width: 148, height: 210, unit: 'mm' },
        'A6': { width: 105, height: 148, unit: 'mm' },
        'Letter': { width: 215.9, height: 279.4, unit: 'mm' },
        'Legal': { width: 215.9, height: 355.6, unit: 'mm' },
        'Executive': { width: 184.15, height: 266.7, unit: 'mm' },
        'Custom': { width: 210, height: 297, unit: 'mm' }
    };

    let pageState = {
        pageSize: 'A4',
        orientation: 'portrait',
        baseWidth: 210,
        baseHeight: 297,
        unit: 'mm',
        marginTop: 6,
        marginRight: 12,
        marginBottom: 6,
        marginLeft: 12
    };

    if (window.EDITOR_DATA && window.EDITOR_DATA.pageConfig) {
        const c = window.EDITOR_DATA.pageConfig;
        pageState = {
            pageSize: c.pageSize || 'A4',
            orientation: c.orientation || 'portrait',
            baseWidth: parseFloat(c.baseWidth) || 210,
            baseHeight: parseFloat(c.baseHeight) || 297,
            unit: c.unit || 'mm',
            marginTop: parseFloat(c.marginTop) ?? 6,
            marginRight: parseFloat(c.marginRight) ?? 12,
            marginBottom: parseFloat(c.marginBottom) ?? 6,
            marginLeft: parseFloat(c.marginLeft) ?? 12
        };
    }

    function updatePageGeometry() {
        let effWidth, effHeight;
        if (pageState.orientation === 'landscape') {
            effWidth = pageState.baseHeight;
            effHeight = pageState.baseWidth;
        } else {
            effWidth = pageState.baseWidth;
            effHeight = pageState.baseHeight;
        }

        const printableWidth = Math.max(10, effWidth - pageState.marginLeft - pageState.marginRight);
        const printableHeight = Math.max(10, effHeight - pageState.marginTop - pageState.marginBottom);

        // Update Paper Canvas Live Styles
        paper.style.aspectRatio = `${effWidth} / ${effHeight}`;
        paper.style.padding = `${pageState.marginTop}${pageState.unit} ${pageState.marginRight}${pageState.unit} ${pageState.marginBottom}${pageState.unit} ${pageState.marginLeft}${pageState.unit}`;

        // Update UI Labels
        if (badgePagePreset) {
            badgePagePreset.textContent = pageState.pageSize + (pageState.orientation === 'landscape' ? ' ↔' : ' ↕');
        }
        if (metricDimensions) {
            metricDimensions.textContent = `${effWidth.toFixed(1)} × ${effHeight.toFixed(1)} ${pageState.unit}`;
        }
        if (metricPrintable) {
            metricPrintable.textContent = `${printableWidth.toFixed(1)} × ${printableHeight.toFixed(1)} ${pageState.unit}`;
        }
        if (marginUnitLabel) {
            marginUnitLabel.textContent = pageState.unit;
        }
        if (previewBtn) {
            previewBtn.textContent = `Preview (${pageState.pageSize})`;
        }
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', () => {
            const val = pageSizeSelect.value;
            pageState.pageSize = val;
            if (val === 'Custom') {
                if (customDimensionsWrap) customDimensionsWrap.style.display = 'block';
                if (customWidthInput) pageState.baseWidth = parseFloat(customWidthInput.value) || 210;
                if (customHeightInput) pageState.baseHeight = parseFloat(customHeightInput.value) || 297;
                if (customUnitSelect) pageState.unit = customUnitSelect.value || 'mm';
            } else {
                if (customDimensionsWrap) customDimensionsWrap.style.display = 'none';
                const p = presets[val] || presets['A4'];
                pageState.baseWidth = p.width;
                pageState.baseHeight = p.height;
                pageState.unit = p.unit;
                if (customWidthInput) customWidthInput.value = p.width;
                if (customHeightInput) customHeightInput.value = p.height;
                if (customUnitSelect) customUnitSelect.value = p.unit;
            }
            updatePageGeometry();
        });
    }

    if (btnOrientPortrait && btnOrientLandscape) {
        btnOrientPortrait.addEventListener('click', () => {
            pageState.orientation = 'portrait';
            btnOrientPortrait.className = 'btn btn-sm btn-primary';
            btnOrientLandscape.className = 'btn btn-sm btn-soft';
            updatePageGeometry();
        });

        btnOrientLandscape.addEventListener('click', () => {
            pageState.orientation = 'landscape';
            btnOrientPortrait.className = 'btn btn-sm btn-soft';
            btnOrientLandscape.className = 'btn btn-sm btn-primary';
            updatePageGeometry();
        });
    }

    if (customWidthInput) {
        customWidthInput.addEventListener('input', () => {
            pageState.baseWidth = parseFloat(customWidthInput.value) || 210;
            updatePageGeometry();
        });
    }
    if (customHeightInput) {
        customHeightInput.addEventListener('input', () => {
            pageState.baseHeight = parseFloat(customHeightInput.value) || 297;
            updatePageGeometry();
        });
    }
    if (customUnitSelect) {
        customUnitSelect.addEventListener('change', () => {
            pageState.unit = customUnitSelect.value || 'mm';
            updatePageGeometry();
        });
    }

    const marginInputs = [
        { el: marginTopInput, key: 'marginTop' },
        { el: marginRightInput, key: 'marginRight' },
        { el: marginBottomInput, key: 'marginBottom' },
        { el: marginLeftInput, key: 'marginLeft' }
    ];
    marginInputs.forEach(m => {
        if (m.el) {
            m.el.addEventListener('input', () => {
                pageState[m.key] = parseFloat(m.el.value) || 0;
                updatePageGeometry();
            });
        }
    });

    // --- COLOR THEME STATE & LOGIC ---
    const themePresets = (window.EDITOR_DATA && window.EDITOR_DATA.themePresets) ? window.EDITOR_DATA.themePresets : {};
    let themeState = {
        preset: (window.EDITOR_DATA && window.EDITOR_DATA.themePreset) || 'green',
        config: (window.EDITOR_DATA && window.EDITOR_DATA.templateTheme) ? Object.assign({}, window.EDITOR_DATA.templateTheme) : {
            primary: '#087F6C',
            secondary: '#075E54',
            accent: '#10B981',
            border: '#222222',
            text: '#111827',
            heading: '#075E54',
            label: '#374151',
            icon: '#087F6C',
            watermark: '#087F6C',
            watermarkOpacity: 0.08
        }
    };

    function getLuminance(hex) {
        hex = (hex || '#000000').replace('#', '');
        if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        if (hex.length !== 6) return 0.5;
        const r = parseInt(hex.substring(0, 2), 16) / 255;
        const g = parseInt(hex.substring(2, 4), 16) / 255;
        const b = parseInt(hex.substring(4, 6), 16) / 255;
        const fn = c => (c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4));
        return 0.2126 * fn(r) + 0.7152 * fn(g) + 0.0722 * fn(b);
    }

    function getContrast(hex1, hex2) {
        const l1 = getLuminance(hex1);
        const l2 = getLuminance(hex2);
        return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
    }

    function syncInputsFromThemeState() {
        const c = themeState.config;
        const map = {
            'Primary': c.primary,
            'Secondary': c.secondary,
            'Accent': c.accent,
            'Border': c.border,
            'Heading': c.heading,
            'Text': c.text,
            'Label': c.label,
            'Icon': c.icon,
            'Watermark': c.watermark
        };
        for (const [key, val] of Object.entries(map)) {
            const colorIn = document.getElementById('themeColor' + key);
            const hexIn = document.getElementById('themeHex' + key);
            if (colorIn && val) colorIn.value = val;
            if (hexIn && val) hexIn.value = val;
        }
        const wmSlider = document.getElementById('themeWmOpacity');
        const wmVal = document.getElementById('wmOpacityVal');
        if (wmSlider && c.watermarkOpacity !== undefined) {
            const pct = Math.round(c.watermarkOpacity * 100);
            wmSlider.value = pct;
            if (wmVal) wmVal.textContent = pct + '%';
        }
    }

    function applyThemeLive() {
        const c = themeState.config;
        paper.style.setProperty('--ml-primary', c.primary);
        paper.style.setProperty('--ml-secondary', c.secondary);
        paper.style.setProperty('--ml-accent', c.accent);
        paper.style.setProperty('--ml-border', c.border);
        paper.style.setProperty('--ml-heading', c.heading);
        paper.style.setProperty('--ml-text', c.text);
        paper.style.setProperty('--ml-label', c.label);
        paper.style.setProperty('--ml-icon', c.icon);
        paper.style.setProperty('--ml-wm-color', c.watermark);
        paper.style.setProperty('--ml-wm-opacity', c.watermarkOpacity);

        // Update badge
        const badgeDot = document.getElementById('badgeThemeDot');
        const badgeName = document.getElementById('badgeThemeName');
        const tabThemeDot = document.getElementById('tabThemeDot');
        if (badgeDot) badgeDot.style.background = c.primary;
        if (tabThemeDot) tabThemeDot.style.background = c.primary;
        if (badgeName) {
            const pName = themePresets[themeState.preset] ? themePresets[themeState.preset].name : 'Custom Theme';
            badgeName.textContent = pName;
        }

        // Update active preset button highlight
        document.querySelectorAll('.btn-theme-preset').forEach(btn => {
            const isMatch = btn.dataset.themeKey === themeState.preset;
            btn.style.borderColor = isMatch ? '#087F6C' : '#cbd5e1';
            btn.style.background = isMatch ? '#f0fdf4' : '#fff';
            btn.style.fontWeight = isMatch ? '700' : '500';
        });

        // Sync form inputs
        syncInputsFromThemeState();

        // Calculate WCAG contrast against white background
        const textContrast = getContrast(c.text || '#111827', '#FFFFFF');
        const contrastValEl = document.getElementById('contrastValue');
        const contrastBadgeEl = document.getElementById('contrastBadge');
        if (contrastValEl && contrastBadgeEl) {
            contrastValEl.textContent = textContrast.toFixed(1) + ':1';
            if (textContrast >= 7.0) {
                contrastBadgeEl.textContent = '✓ AAA High Print Legibility';
                contrastBadgeEl.style.color = '#16a34a';
            } else if (textContrast >= 4.5) {
                contrastBadgeEl.textContent = '✓ AA Normal Legibility';
                contrastBadgeEl.style.color = '#0284c7';
            } else {
                contrastBadgeEl.textContent = '⚠ Low Contrast Warning';
                contrastBadgeEl.style.color = '#dc2626';
            }
        }
    }

    // Bind theme preset buttons
    document.querySelectorAll('.btn-theme-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.themeKey;
            themeState.preset = key;
            if (themePresets[key]) {
                themeState.config = Object.assign({}, themePresets[key]);
            }
            applyThemeLive();
        });
    });

    // Bind custom color inputs
    ['Primary', 'Secondary', 'Accent', 'Border', 'Heading', 'Text', 'Label', 'Icon', 'Watermark'].forEach(key => {
        const colorIn = document.getElementById('themeColor' + key);
        const hexIn = document.getElementById('themeHex' + key);
        const propName = key.toLowerCase();

        if (colorIn) {
            colorIn.addEventListener('input', () => {
                const val = colorIn.value;
                if (hexIn) hexIn.value = val;
                themeState.config[propName] = val;
                themeState.preset = 'custom';
                applyThemeLive();
            });
        }
        if (hexIn) {
            hexIn.addEventListener('input', () => {
                let val = hexIn.value.trim();
                if (!val.startsWith('#')) val = '#' + val;
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    if (colorIn) colorIn.value = val;
                    themeState.config[propName] = val;
                    themeState.preset = 'custom';
                    applyThemeLive();
                }
            });
        }
    });

    const wmSlider = document.getElementById('themeWmOpacity');
    const wmVal = document.getElementById('wmOpacityVal');
    if (wmSlider) {
        wmSlider.addEventListener('input', () => {
            const pct = parseInt(wmSlider.value) || 8;
            if (wmVal) wmVal.textContent = pct + '%';
            themeState.config.watermarkOpacity = pct / 100;
            themeState.preset = 'custom';
            applyThemeLive();
        });
    }

    // Toggle custom colors accordion
    const toggleCustomBtn = document.getElementById('toggleCustomColorsBtn');
    const customContent = document.getElementById('customColorsContent');
    const customArrow = document.getElementById('customColorsArrow');
    if (toggleCustomBtn && customContent) {
        toggleCustomBtn.addEventListener('click', () => {
            const isHidden = customContent.style.display === 'none';
            customContent.style.display = isHidden ? 'grid' : 'none';
            if (customArrow) customArrow.textContent = isHidden ? '▲' : '▼';
        });
    }

    // Save Theme handler
    const btnSaveTheme = document.getElementById('btnSaveThemeSettings');
    async function saveThemeSettings(showNotice = true) {
        if (!window.EDITOR_DATA || !window.EDITOR_DATA.templateId) return;
        if (btnSaveTheme && showNotice) {
            btnSaveTheme.disabled = true;
            btnSaveTheme.textContent = 'Saving...';
        }
        try {
            const res = await fetch('api/save_theme_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.EDITOR_DATA.csrf
                },
                body: JSON.stringify({
                    template_id: window.EDITOR_DATA.templateId,
                    theme_preset: themeState.preset,
                    theme_config: themeState.config
                })
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.message || 'Failed to save theme');
            if (btnSaveTheme && showNotice) {
                btnSaveTheme.textContent = 'Saved ✓';
                setTimeout(() => { btnSaveTheme.textContent = 'Save Theme'; }, 1500);
            }
        } catch (err) {
            if (showNotice) alert('Error saving theme: ' + err.message);
            if (btnSaveTheme && showNotice) btnSaveTheme.textContent = 'Save Theme';
        } finally {
            if (btnSaveTheme && showNotice) btnSaveTheme.disabled = false;
        }
    }
    if (btnSaveTheme) {
        btnSaveTheme.addEventListener('click', () => saveThemeSettings(true));
    }

    async function savePageSettings(showNotice = true) {
        if (!window.EDITOR_DATA || !window.EDITOR_DATA.templateId) return;
        const payload = {
            template_id: window.EDITOR_DATA.templateId,
            page_size: pageState.pageSize,
            orientation: pageState.orientation,
            page_width: pageState.baseWidth,
            page_height: pageState.baseHeight,
            page_unit: pageState.unit,
            margin_top: pageState.marginTop,
            margin_right: pageState.marginRight,
            margin_bottom: pageState.marginBottom,
            margin_left: pageState.marginLeft,
            theme_preset: themeState.preset,
            theme_config: themeState.config
        };

        if (btnSavePageSettings && showNotice) {
            btnSavePageSettings.disabled = true;
            btnSavePageSettings.textContent = 'Saving...';
        }

        try {
            const res = await fetch('api/save_page_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.EDITOR_DATA.csrf
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.message || 'Failed to save page settings');

            if (btnSavePageSettings && showNotice) {
                btnSavePageSettings.textContent = 'Saved ✓';
                setTimeout(() => { btnSavePageSettings.textContent = 'Save Page Settings'; }, 1500);
            }
        } catch (err) {
            if (showNotice) alert('Error saving page settings: ' + err.message);
            if (btnSavePageSettings && showNotice) {
                btnSavePageSettings.textContent = 'Save Page Settings';
            }
        } finally {
            if (btnSavePageSettings && showNotice) {
                btnSavePageSettings.disabled = false;
            }
        }
    }

    if (btnSavePageSettings) {
        btnSavePageSettings.addEventListener('click', () => savePageSettings(true));
    }

    const isCode = !!(window.EDITOR_DATA && window.EDITOR_DATA.isCode);

    // Initialize initial canvas geometry & theme
    updatePageGeometry();
    applyThemeLive();

    // =========================================================================
    // CLINICAL CODE TEMPLATE & SECTION ADJUSTER ENGINE
    // =========================================================================
    const defaultSections = {
        header_mode: 'digital',
        header_height: 38,
        patient_top: 42,
        patient_style: 'divider',
        patient_density: 'normal',
        patient_font_size: 10,
        vitals_top: 82,
        show_vitals: 1,
        footer_mode: 'digital',
        footer_height: 28,
        show_signature: 1
    };

    let sectionState = Object.assign({}, defaultSections, (window.EDITOR_DATA && window.EDITOR_DATA.sections) ? window.EDITOR_DATA.sections : {});
    let codeConfig = Object.assign({}, (window.EDITOR_DATA && window.EDITOR_DATA.codeConfig) ? window.EDITOR_DATA.codeConfig : {});

    // DOM Elements - Sidebar Controls
    const btnHeaderDigital = document.getElementById('btnHeaderDigital');
    const btnHeaderBlank = document.getElementById('btnHeaderBlank');
    const headerHeightSlider = document.getElementById('headerHeightSlider');
    const headerHeightVal = document.getElementById('headerHeightVal');
    const badgeHeaderMode = document.getElementById('badgeHeaderMode');

    const patientTopSlider = document.getElementById('patientTopSlider');
    const patientTopVal = document.getElementById('patientTopVal');
    const patientStyleSelect = document.getElementById('patientStyleSelect');
    const patientFontSlider = document.getElementById('patientFontSlider');
    const patientFontVal = document.getElementById('patientFontVal');

    const chkShowVitals = document.getElementById('chkShowVitals');
    const chkShowSignature = document.getElementById('chkShowSignature');
    const chkShowHeader = document.getElementById('chkShowHeader');
    const chkShowTitle = document.getElementById('chkShowTitle');
    const chkShowPatientInfo = document.getElementById('chkShowPatientInfo');
    const chkShowDoctorBox = document.getElementById('chkShowDoctorBox');
    const chkShowValidityNote = document.getElementById('chkShowValidityNote');
    const chkShowFooter = document.getElementById('chkShowFooter');

    const btnFooterDigital = document.getElementById('btnFooterDigital');
    const btnFooterBlank = document.getElementById('btnFooterBlank');
    const footerHeightSlider = document.getElementById('footerHeightSlider');
    const footerHeightVal = document.getElementById('footerHeightVal');
    const badgeFooterMode = document.getElementById('badgeFooterMode');

    // Clinical Inputs
    const hospitalNameInput = document.getElementById('hospitalNameInput');
    const hospitalTaglineInput = document.getElementById('hospitalTaglineInput');
    const docTitleInput = document.getElementById('docTitleInput');
    const logoUploadInput = document.getElementById('logoUploadInput');
    const btnResetLogo = document.getElementById('btnResetLogo');
    const brandLogoPreview = document.getElementById('brandLogoPreview');
    const logoUploadStatus = document.getElementById('logoUploadStatus');

    const fontFamilySelect = document.getElementById('fontFamilySelect');
    const doctorNameInput = document.getElementById('doctorNameInput');
    const doctorDeptInput = document.getElementById('doctorDeptInput');
    const validityNoteInput = document.getElementById('validityNoteInput');
    const lblSignatureInput = document.getElementById('lblSignatureInput');

    const chkShowWatermark = document.getElementById('chkShowWatermark');
    const watermarkOpacitySlider = document.getElementById('watermarkOpacitySlider');
    const wmOpacityVal = document.getElementById('wmOpacityVal');
    const watermarkPosSelect = document.getElementById('watermarkPosSelect');

    const hospitalAddressInput = document.getElementById('hospitalAddressInput');
    const regOfficeInput = document.getElementById('regOfficeInput');
    const phoneWhatsappInput = document.getElementById('phoneWhatsappInput');
    const phoneLandlineInput = document.getElementById('phoneLandlineInput');
    const emailInput = document.getElementById('emailInput');
    const websiteInput = document.getElementById('websiteInput');

    // DOM Elements - Canvas Zones (Legacy placeholders)
    const canvasTopAccent = document.getElementById('canvasTopAccent');
    const canvasHeaderDigital = document.getElementById('canvasHeaderDigital');
    const canvasHeaderBlank = document.getElementById('canvasHeaderBlank');
    const canvasHeaderBlankLbl = document.getElementById('canvasHeaderBlankLbl');
    const canvasHeaderHBadge = document.getElementById('canvasHeaderHBadge');
    const canvasHeaderTBadge = document.getElementById('canvasHeaderTBadge');
    const knobHeaderVal = document.getElementById('knobHeaderVal');

    const canvasZonePatient = document.getElementById('canvasZonePatient');
    const canvasZoneVitals = document.getElementById('canvasZoneVitals');
    const canvasSignSection = document.getElementById('canvasSignSection');

    const canvasFooterDigital = document.getElementById('canvasFooterDigital');
    const canvasFooterBlank = document.getElementById('canvasFooterBlank');
    const canvasFooterBlankLbl = document.getElementById('canvasFooterBlankLbl');
    const canvasFooterHBadge = document.getElementById('canvasFooterHBadge');
    const canvasFooterTBadge = document.getElementById('canvasFooterTBadge');
    const knobFooterVal = document.getElementById('knobFooterVal');

    const handleHeaderBottom = document.getElementById('handleHeaderBottom');
    const handleFooterTop = document.getElementById('handleFooterTop');

    function escapeHtml(str) {
        return (str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function nl2br(str) {
        return escapeHtml(str).replace(/\n/g, '<br>');
    }

    function updateCodeFontStack(font) {
        const sheet = document.getElementById('motherlandSheet');
        if (!sheet) return;
        let stack = font;
        if (font === 'Arial') stack = 'Arial, "Helvetica Neue", Helvetica, sans-serif';
        else if (font === 'Cambria' || font === 'Times New Roman') stack = `${font}, Georgia, "Times New Roman", serif`;
        else stack = `${font}, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`;
        sheet.style.setProperty('--ml-font', stack);
    }

    function renderSectionsLive() {
        if (!isCode) return;

        const isHeadDigi = (sectionState.header_mode === 'digital');
        const isFootDigi = (sectionState.footer_mode === 'digital');

        // Apply CSS Variables on Paper
        paper.style.setProperty('--sec-header-height', sectionState.header_height + 'mm');
        paper.style.setProperty('--sec-footer-height', sectionState.footer_height + 'mm');

        // Header Mode & Height Badges/Sliders
        if (badgeHeaderMode) {
            badgeHeaderMode.textContent = isHeadDigi ? 'Digital Print' : 'Pre-printed Pad';
            badgeHeaderMode.className = 'badge-eka-mode ' + (isHeadDigi ? 'badge-mode-digital' : 'badge-mode-blank');
        }
        if (btnHeaderDigital) btnHeaderDigital.classList.toggle('active', isHeadDigi);
        if (btnHeaderBlank) btnHeaderBlank.classList.toggle('active', !isHeadDigi);
        if (headerHeightSlider) headerHeightSlider.value = sectionState.header_height;
        if (headerHeightVal) headerHeightVal.textContent = sectionState.header_height + ' mm';
        if (canvasHeaderHBadge) canvasHeaderHBadge.textContent = sectionState.header_height + 'mm';
        if (knobHeaderVal) knobHeaderVal.textContent = sectionState.header_height + 'mm';

        // Footer Mode & Height Badges/Sliders
        if (badgeFooterMode) {
            badgeFooterMode.textContent = isFootDigi ? 'Digital Print' : 'Pre-printed Pad';
            badgeFooterMode.className = 'badge-eka-mode ' + (isFootDigi ? 'badge-mode-digital' : 'badge-mode-blank');
        }
        if (btnFooterDigital) btnFooterDigital.classList.toggle('active', isFootDigi);
        if (btnFooterBlank) btnFooterBlank.classList.toggle('active', !isFootDigi);
        if (footerHeightSlider) footerHeightSlider.value = sectionState.footer_height;
        if (footerHeightVal) footerHeightVal.textContent = sectionState.footer_height + ' mm';
        if (canvasFooterHBadge) canvasFooterHBadge.textContent = sectionState.footer_height + 'mm';
        if (knobFooterVal) knobFooterVal.textContent = sectionState.footer_height + 'mm';

        // Doctor & Vitals switches
        if (chkShowVitals) chkShowVitals.checked = !!sectionState.show_vitals;
        if (chkShowSignature) chkShowSignature.checked = !!sectionState.show_signature;

        // Motherland Sheet Live DOM Binding
        const sheet = document.getElementById('motherlandSheet');
        if (sheet) {
            sheet.style.setProperty('--ml-header-height', sectionState.header_height + 'mm');
            sheet.style.setProperty('--ml-footer-height', sectionState.footer_height + 'mm');

            // Header Blank vs Digital
            const sheetHeadBlank = sheet.querySelector('.ml-header-blank-zone');
            const sheetHeadCont = sheet.querySelector('.ml-header-container');
            const sheetTopAccent = sheet.querySelector('.ml-top-accent');
            const blankTagH = sheet.querySelector('.ml-h-val-preview');

            if (isHeadDigi) {
                if (sheetHeadBlank) sheetHeadBlank.style.display = 'none';
                if (sheetHeadCont) {
                    sheetHeadCont.style.display = 'flex';
                    sheetHeadCont.style.height = sectionState.header_height + 'mm';
                }
                if (sheetTopAccent) sheetTopAccent.style.display = 'block';
            } else {
                if (sheetHeadCont) sheetHeadCont.style.display = 'none';
                if (sheetTopAccent) sheetTopAccent.style.display = 'none';
                if (sheetHeadBlank) {
                    sheetHeadBlank.style.display = 'flex';
                    sheetHeadBlank.style.height = sectionState.header_height + 'mm';
                }
                if (blankTagH) blankTagH.textContent = sectionState.header_height;
            }

            // Vitals Grid
            const sheetVitals = sheet.querySelector('.ml-vitals-table');
            if (sheetVitals) {
                sheetVitals.style.display = sectionState.show_vitals ? 'block' : 'none';
            }

            // Doctor Signature Line
            const sheetSign = sheet.querySelector('.ml-sign-section');
            if (sheetSign) {
                sheetSign.style.display = sectionState.show_signature ? 'block' : 'none';
            }

            // Footer Blank vs Digital
            const sheetFootBlank = sheet.querySelector('.ml-footer-blank-zone');
            const sheetFootWrap = sheet.querySelector('.ml-footer-wrapper');
            const blankTagF = sheet.querySelector('.ml-f-val-preview');

            if (isFootDigi) {
                if (sheetFootBlank) sheetFootBlank.style.display = 'none';
                if (sheetFootWrap) {
                    sheetFootWrap.style.display = 'block';
                    sheetFootWrap.style.minHeight = sectionState.footer_height + 'mm';
                }
            } else {
                if (sheetFootWrap) sheetFootWrap.style.display = 'none';
                if (sheetFootBlank) {
                    sheetFootBlank.style.display = 'flex';
                    sheetFootBlank.style.height = sectionState.footer_height + 'mm';
                }
                if (blankTagF) blankTagF.textContent = sectionState.footer_height;
            }
        }

        // Legacy Canvas element support
        if (canvasTopAccent) canvasTopAccent.style.display = isHeadDigi ? 'block' : 'none';
        if (canvasHeaderDigital) canvasHeaderDigital.style.display = isHeadDigi ? 'block' : 'none';
        if (canvasHeaderBlank) canvasHeaderBlank.style.display = isHeadDigi ? 'none' : 'flex';
        if (canvasZoneVitals) canvasZoneVitals.style.display = sectionState.show_vitals ? 'block' : 'none';
        if (canvasSignSection) canvasSignSection.style.display = sectionState.show_signature ? 'block' : 'none';
        if (canvasFooterDigital) canvasFooterDigital.style.display = isFootDigi ? 'block' : 'none';
        if (canvasFooterBlank) canvasFooterBlank.style.display = isFootDigi ? 'none' : 'flex';
    }

    // Interactive canvas boundary divider handles (if present)
    if (handleHeaderBottom) {
        let draggingHeader = false;
        let startY = 0;
        let startHeight = 38;

        handleHeaderBottom.addEventListener('pointerdown', e => {
            draggingHeader = true;
            startY = e.clientY;
            startHeight = sectionState.header_height;
            handleHeaderBottom.classList.add('is-dragging');
            try { handleHeaderBottom.setPointerCapture(e.pointerId); } catch(err) {}
            e.stopPropagation();
        });

        handleHeaderBottom.addEventListener('pointermove', e => {
            if (!draggingHeader) return;
            const pr = paper.getBoundingClientRect();
            const effHeightMm = (pageState.orientation === 'landscape') ? pageState.baseWidth : pageState.baseHeight;
            const pxPerMm = pr.height / effHeightMm;
            const deltaMm = (e.clientY - startY) / (pxPerMm || 3.78);
            sectionState.header_height = Math.max(15, Math.min(100, Math.round(startHeight + deltaMm)));
            renderSectionsLive();
        });

        const stopDragHeader = e => {
            if (draggingHeader) {
                draggingHeader = false;
                handleHeaderBottom.classList.remove('is-dragging');
                try { handleHeaderBottom.releasePointerCapture(e.pointerId); } catch(err) {}
            }
        };
        handleHeaderBottom.addEventListener('pointerup', stopDragHeader);
        handleHeaderBottom.addEventListener('pointercancel', stopDragHeader);
    }

    if (handleFooterTop) {
        let draggingFooter = false;
        let startY = 0;
        let startHeight = 28;

        handleFooterTop.addEventListener('pointerdown', e => {
            draggingFooter = true;
            startY = e.clientY;
            startHeight = sectionState.footer_height;
            handleFooterTop.classList.add('is-dragging');
            try { handleFooterTop.setPointerCapture(e.pointerId); } catch(err) {}
            e.stopPropagation();
        });

        handleFooterTop.addEventListener('pointermove', e => {
            if (!draggingFooter) return;
            const pr = paper.getBoundingClientRect();
            const effHeightMm = (pageState.orientation === 'landscape') ? pageState.baseWidth : pageState.baseHeight;
            const pxPerMm = pr.height / effHeightMm;
            const deltaMm = (startY - e.clientY) / (pxPerMm || 3.78);
            sectionState.footer_height = Math.max(15, Math.min(80, Math.round(startHeight + deltaMm)));
            renderSectionsLive();
        });

        const stopDragFooter = e => {
            if (draggingFooter) {
                draggingFooter = false;
                handleFooterTop.classList.remove('is-dragging');
                try { handleFooterTop.releasePointerCapture(e.pointerId); } catch(err) {}
            }
        };
        handleFooterTop.addEventListener('pointerup', stopDragFooter);
        handleFooterTop.addEventListener('pointercancel', stopDragFooter);
    }

    // Sidebar controls event listeners
    if (btnHeaderDigital) {
        btnHeaderDigital.addEventListener('click', () => {
            sectionState.header_mode = 'digital';
            renderSectionsLive();
        });
    }
    if (btnHeaderBlank) {
        btnHeaderBlank.addEventListener('click', () => {
            sectionState.header_mode = 'blank';
            renderSectionsLive();
        });
    }
    if (headerHeightSlider) {
        headerHeightSlider.addEventListener('input', () => {
            sectionState.header_height = parseInt(headerHeightSlider.value) || 38;
            renderSectionsLive();
        });
    }
    document.querySelectorAll('[data-set-hheight]').forEach(btn => {
        btn.addEventListener('click', () => {
            sectionState.header_height = parseInt(btn.dataset.setHheight) || 38;
            renderSectionsLive();
        });
    });

    if (chkShowVitals) {
        chkShowVitals.addEventListener('change', () => {
            sectionState.show_vitals = chkShowVitals.checked ? 1 : 0;
            renderSectionsLive();
        });
    }
    if (chkShowSignature) {
        chkShowSignature.addEventListener('change', () => {
            sectionState.show_signature = chkShowSignature.checked ? 1 : 0;
            renderSectionsLive();
        });
    }

    if (chkShowHeader) {
        chkShowHeader.addEventListener('change', () => {
            codeConfig.show_header = chkShowHeader.checked ? 1 : 0;
            const el = document.querySelector('#motherlandSheet .ml-header-container');
            const el2 = document.querySelector('#motherlandSheet .ml-top-accent');
            if (el) el.style.display = chkShowHeader.checked ? 'flex' : 'none';
            if (el2) el2.style.display = chkShowHeader.checked ? 'block' : 'none';
        });
    }
    if (chkShowTitle) {
        chkShowTitle.addEventListener('change', () => {
            codeConfig.show_title = chkShowTitle.checked ? 1 : 0;
            const el = document.querySelector('#motherlandSheet .ml-title-section');
            if (el) el.style.display = chkShowTitle.checked ? 'block' : 'none';
        });
    }
    if (chkShowPatientInfo) {
        chkShowPatientInfo.addEventListener('change', () => {
            codeConfig.show_patient_info = chkShowPatientInfo.checked ? 1 : 0;
            const el = document.querySelector('#motherlandSheet .ml-patient-section');
            if (el) el.style.display = chkShowPatientInfo.checked ? 'block' : 'none';
        });
    }
    if (chkShowDoctorBox) {
        chkShowDoctorBox.addEventListener('change', () => {
            codeConfig.show_doctor_box = chkShowDoctorBox.checked ? 1 : 0;
            const el = document.querySelector('#motherlandSheet .ml-doctor-vitals-box');
            if (el) el.style.display = chkShowDoctorBox.checked ? 'block' : 'none';
        });
    }
    if (chkShowValidityNote) {
        chkShowValidityNote.addEventListener('change', () => {
            codeConfig.show_validity_note = chkShowValidityNote.checked ? 1 : 0;
            const el = document.querySelector('#motherlandSheet .ml-validity-section');
            if (el) el.style.display = chkShowValidityNote.checked ? 'block' : 'none';
        });
    }
    if (chkShowFooter) {
        chkShowFooter.addEventListener('change', () => {
            codeConfig.show_footer = chkShowFooter.checked ? 1 : 0;
            const el = document.querySelector('#motherlandSheet .ml-footer-wrapper');
            if (el) el.style.display = chkShowFooter.checked ? 'block' : 'none';
        });
    }

    if (btnFooterDigital) {
        btnFooterDigital.addEventListener('click', () => {
            sectionState.footer_mode = 'digital';
            renderSectionsLive();
        });
    }
    if (btnFooterBlank) {
        btnFooterBlank.addEventListener('click', () => {
            sectionState.footer_mode = 'blank';
            renderSectionsLive();
        });
    }
    if (footerHeightSlider) {
        footerHeightSlider.addEventListener('input', () => {
            sectionState.footer_height = parseInt(footerHeightSlider.value) || 28;
            renderSectionsLive();
        });
    }
    document.querySelectorAll('[data-set-fheight]').forEach(btn => {
        btn.addEventListener('click', () => {
            sectionState.footer_height = parseInt(btn.dataset.setFheight) || 28;
            renderSectionsLive();
        });
    });

    // Clinical Input Live Event Listeners
    if (hospitalNameInput) {
        hospitalNameInput.addEventListener('input', () => {
            codeConfig.hospital_name = hospitalNameInput.value;
            const el = document.querySelector('#motherlandSheet .ml-hospital-name');
            if (el) el.textContent = hospitalNameInput.value;
        });
    }
    if (hospitalTaglineInput) {
        hospitalTaglineInput.addEventListener('input', () => {
            codeConfig.hospital_tagline = hospitalTaglineInput.value;
            const el = document.querySelector('#motherlandSheet .ml-hospital-tagline');
            if (el) el.textContent = hospitalTaglineInput.value ? '— ' + hospitalTaglineInput.value + ' —' : '';
        });
    }
    if (docTitleInput) {
        docTitleInput.addEventListener('input', () => {
            codeConfig.doc_title = docTitleInput.value;
            const el = document.querySelector('#motherlandSheet .ml-title');
            if (el) el.textContent = docTitleInput.value;
        });
    }
    if (fontFamilySelect) {
        fontFamilySelect.addEventListener('change', () => {
            codeConfig.font_family = fontFamilySelect.value;
            updateCodeFontStack(fontFamilySelect.value);
        });
    }
    if (doctorNameInput) {
        doctorNameInput.addEventListener('input', () => {
            codeConfig.doctor_name = doctorNameInput.value;
            const el = document.querySelector('#motherlandSheet .ml-doctor-name');
            if (el) el.textContent = doctorNameInput.value || 'Dr. ANVITI SARAF';
        });
    }
    if (doctorDeptInput) {
        doctorDeptInput.addEventListener('input', () => {
            codeConfig.doctor_dept = doctorDeptInput.value;
            const el = document.querySelector('#motherlandSheet [data-field="doctor_dept"] .ml-value');
            if (el) el.textContent = doctorDeptInput.value;
        });
    }
    if (validityNoteInput) {
        validityNoteInput.addEventListener('input', () => {
            codeConfig.validity_note = validityNoteInput.value;
            const el = document.querySelector('#motherlandSheet .ml-validity-note u em');
            if (el) el.textContent = validityNoteInput.value;
        });
    }
    if (lblSignatureInput) {
        lblSignatureInput.addEventListener('input', () => {
            codeConfig.lbl_signature = lblSignatureInput.value;
            const el = document.querySelector('#motherlandSheet .ml-sign-text');
            if (el) el.textContent = lblSignatureInput.value;
        });
    }
    if (chkShowWatermark) {
        chkShowWatermark.addEventListener('change', () => {
            codeConfig.show_watermark = chkShowWatermark.checked ? 1 : 0;
            const wm = document.querySelector('#motherlandSheet .ml-watermark');
            if (wm) wm.style.display = chkShowWatermark.checked ? 'block' : 'none';
        });
    }
    if (watermarkOpacitySlider) {
        watermarkOpacitySlider.addEventListener('input', () => {
            const op = (parseInt(watermarkOpacitySlider.value) / 100).toFixed(2);
            codeConfig.watermark_opacity = op;
            if (wmOpacityVal) wmOpacityVal.textContent = watermarkOpacitySlider.value + '%';
            const sheet = document.getElementById('motherlandSheet');
            if (sheet) sheet.style.setProperty('--ml-wm-opacity', op);
        });
    }
    if (watermarkPosSelect) {
        watermarkPosSelect.addEventListener('change', () => {
            codeConfig.watermark_position = watermarkPosSelect.value;
            const wm = document.querySelector('#motherlandSheet .ml-watermark');
            if (wm) {
                wm.className = 'ml-watermark ml-wm-' + watermarkPosSelect.value;
            }
        });
    }
    if (hospitalAddressInput) {
        hospitalAddressInput.addEventListener('input', () => {
            codeConfig.hospital_address = hospitalAddressInput.value;
            const el = document.querySelector('#motherlandSheet .ml-footer-address .ml-footer-text > div:first-child');
            if (el) el.innerHTML = nl2br(hospitalAddressInput.value);
        });
    }
    if (regOfficeInput) {
        regOfficeInput.addEventListener('input', () => {
            codeConfig.reg_office = regOfficeInput.value;
            const el = document.querySelector('#motherlandSheet .ml-reg-office');
            if (el) el.innerHTML = nl2br(regOfficeInput.value);
        });
    }
    if (phoneWhatsappInput) {
        phoneWhatsappInput.addEventListener('input', () => {
            codeConfig.phone_whatsapp = phoneWhatsappInput.value;
            const el = document.querySelector('#motherlandSheet .ml-footer-contact .ml-footer-item:first-child span');
            if (el) el.textContent = phoneWhatsappInput.value;
        });
    }
    if (phoneLandlineInput) {
        phoneLandlineInput.addEventListener('input', () => {
            codeConfig.phone_landline = phoneLandlineInput.value;
            const el = document.querySelector('#motherlandSheet .ml-footer-contact .ml-footer-item:last-child span');
            if (el) el.textContent = phoneLandlineInput.value;
        });
    }
    if (emailInput) {
        emailInput.addEventListener('input', () => {
            codeConfig.email = emailInput.value;
            const el = document.querySelector('#motherlandSheet .ml-footer-online .ml-footer-item:first-child span');
            if (el) el.textContent = emailInput.value;
        });
    }
    if (websiteInput) {
        websiteInput.addEventListener('input', () => {
            codeConfig.website = websiteInput.value;
            const el = document.querySelector('#motherlandSheet .ml-footer-online .ml-footer-item:last-child span');
            if (el) el.textContent = websiteInput.value;
        });
    }

    // Logo Upload handler
    if (logoUploadInput) {
        logoUploadInput.addEventListener('change', async () => {
            if (!logoUploadInput.files || !logoUploadInput.files[0]) return;
            const file = logoUploadInput.files[0];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('template_id', window.EDITOR_DATA.templateId);
            formData.append('asset_type', 'logo');
            if (logoUploadStatus) {
                logoUploadStatus.textContent = 'Uploading...';
                logoUploadStatus.style.background = '#fef3c7';
                logoUploadStatus.style.color = '#b45309';
            }
            try {
                const res = await fetch('api/upload_template_asset.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': window.EDITOR_DATA.csrf },
                    body: formData
                });
                const data = await res.json();
                if (!data.ok) throw new Error(data.message || 'Upload failed');
                if (brandLogoPreview) brandLogoPreview.src = data.url;
                const brandIcon = document.querySelector('#motherlandSheet .ml-brand-icon');
                if (brandIcon) brandIcon.src = data.url;
                const wmImg = document.querySelector('#motherlandSheet .ml-wm-img');
                if (wmImg) wmImg.src = data.url;
                codeConfig.icon_path = data.url;
                if (logoUploadStatus) {
                    logoUploadStatus.textContent = 'Uploaded ✓';
                    logoUploadStatus.style.background = '#dcfce7';
                    logoUploadStatus.style.color = '#15803d';
                }
            } catch (err) {
                alert('Logo upload failed: ' + err.message);
                if (logoUploadStatus) {
                    logoUploadStatus.textContent = 'Error';
                    logoUploadStatus.style.background = '#fee2e2';
                    logoUploadStatus.style.color = '#b91c1c';
                }
            }
        });
    }

    // Reset Logo button
    if (btnResetLogo) {
        btnResetLogo.addEventListener('click', () => {
            codeConfig.icon_path = 'assets/motherland-icon.png';
            if (brandLogoPreview) brandLogoPreview.src = 'assets/motherland-icon.png';
            const brandIcon = document.querySelector('#motherlandSheet .ml-brand-icon');
            if (brandIcon) brandIcon.src = 'assets/motherland-icon.png';
            const wmImg = document.querySelector('#motherlandSheet .ml-wm-img');
            if (wmImg) wmImg.src = 'assets/motherland-icon.png';
            if (logoUploadStatus) {
                logoUploadStatus.textContent = 'Reset to Default';
                logoUploadStatus.style.background = '#e0f2fe';
                logoUploadStatus.style.color = '#0284c7';
            }
        });
    }

    // Zoom Buttons
    document.querySelectorAll('.btn-zoom').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.btn-zoom').forEach(b => {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-soft');
            });
            btn.classList.remove('btn-soft');
            btn.classList.add('active', 'btn-primary');
            const zoom = parseFloat(btn.dataset.zoom) || 1.0;
            const sheet = document.getElementById('motherlandSheet');
            if (sheet) {
                sheet.style.transform = `scale(${zoom})`;
                sheet.style.transformOrigin = 'top center';
            }
        });
    });

    // Initialize Eka Care / Motherland section layout live
    renderSectionsLive();

    // =========================================================================
    // IMAGE SCAN DRAG & DROP FIELD LOGIC (Production Calibration Studio)
    // =========================================================================
    const inspFieldTitle = document.getElementById('inspFieldTitle');
    const inspFieldKeyBadge = document.getElementById('inspFieldKeyBadge');
    const btnModeValOnly = document.getElementById('btnModeValOnly');
    const btnModeLabelVal = document.getElementById('btnModeLabelVal');
    const chkFieldVisible = document.getElementById('chkFieldVisible');
    const fieldCoordX = document.getElementById('fieldCoordX');
    const fieldCoordY = document.getElementById('fieldCoordY');
    const nudgeUp = document.getElementById('nudgeUp');
    const nudgeDown = document.getElementById('nudgeDown');
    const nudgeLeft = document.getElementById('nudgeLeft');
    const nudgeRight = document.getElementById('nudgeRight');
    const fieldFontWeight = document.getElementById('fieldFontWeight');
    const fieldAlign = document.getElementById('fieldAlign');
    const fieldColorPicker = document.getElementById('fieldColorPicker');
    const fieldColorHex = document.getElementById('fieldColorHex');

    const padScanLayer = document.getElementById('padScanLayer');
    const padOpacitySlider = document.getElementById('padOpacitySlider');
    const padOpacityVal = document.getElementById('padOpacityVal');
    const chkCalibrationGrid = document.getElementById('chkCalibrationGrid');
    const calibrationGridLayer = document.getElementById('calibrationGridLayer');

    function updateFieldBadge(fieldKey) {
        const el = paper ? paper.querySelector(`[data-field="${CSS.escape(fieldKey)}"]`) : null;
        const b = document.getElementById('badgeFStatus_' + fieldKey);
        if (!el || !b) return;
        const isVis = (el.dataset.visible !== '0');
        const isLbl = (el.dataset.showLabel === '1');
        if (!isVis) {
            b.textContent = 'Hidden';
            b.style.background = '#fee2e2';
            b.style.color = '#ef4444';
        } else if (isLbl) {
            b.textContent = 'Lbl+Val';
            b.style.background = '#e0e7ff';
            b.style.color = '#4338ca';
        } else {
            b.textContent = 'Val';
            b.style.background = '#e0f2fe';
            b.style.color = '#0369a1';
        }
    }

    function syncFieldContent(el) {
        if (!el) return;
        const isLbl = (el.dataset.showLabel === '1');
        const box = el.querySelector('.field-content-box') || el;
        const sample = el.dataset.sample || 'Sample';
        const label = el.dataset.label || el.dataset.field;
        if (isLbl) {
            box.innerHTML = `<span class="field-lbl-part">${label}: </span><span class="field-val-part">${sample}</span>`;
        } else {
            box.innerHTML = `<span class="field-val-part">${sample}</span>`;
        }
    }

    function updateCoordBadge(el) {
        if (!el) return;
        let b = el.querySelector('.field-coord-badge');
        if (!b) {
            b = document.createElement('span');
            b.className = 'field-coord-badge';
            el.appendChild(b);
        }
        const x = parseFloat(el.style.left) || 0;
        const y = parseFloat(el.style.top) || 0;
        b.textContent = `X: ${x.toFixed(1)}% Y: ${y.toFixed(1)}%`;
    }

    function pick(el) {
        paper.querySelectorAll('.draggable-field').forEach(x => x.classList.remove('selected'));
        document.querySelectorAll('.palette-item').forEach(b => b.classList.remove('is-active-field'));
        selected = el;
        if (!el) return;

        el.classList.add('selected');
        const fieldKey = el.dataset.field;

        // Highlight button in list
        const navBtn = document.getElementById('navFieldBtn_' + fieldKey);
        if (navBtn) navBtn.classList.add('is-active-field');

        // Update Inspector Title & Badge
        if (inspFieldTitle) inspFieldTitle.textContent = (el.dataset.label || fieldKey);
        if (inspFieldKeyBadge) inspFieldKeyBadge.textContent = fieldKey;

        // Mode
        const isLbl = (el.dataset.showLabel === '1');
        if (btnModeValOnly) btnModeValOnly.classList.toggle('active', !isLbl);
        if (btnModeLabelVal) btnModeLabelVal.classList.toggle('active', isLbl);

        // Visibility
        const isVis = (el.dataset.visible !== '0');
        if (chkFieldVisible) chkFieldVisible.checked = isVis;

        // Coordinates
        const x = parseFloat(el.style.left) || 0;
        const y = parseFloat(el.style.top) || 0;
        if (fieldCoordX) fieldCoordX.value = x.toFixed(1);
        if (fieldCoordY) fieldCoordY.value = y.toFixed(1);
        updateCoordBadge(el);

        // Typography
        const s = parseInt(el.dataset.fontSize || el.style.fontSize) || 11;
        if (size) size.value = s;
        if (sizeOut) sizeOut.textContent = s + ' px';

        const w = parseInt(el.dataset.width || el.style.width) || el.offsetWidth || 220;
        if (width) width.value = w;
        if (widthOut) widthOut.textContent = w + ' px';

        if (fieldFontWeight) fieldFontWeight.value = el.dataset.fontWeight || el.style.fontWeight || '600';
        if (fieldAlign) fieldAlign.value = el.dataset.align || el.style.textAlign || 'left';
        
        const col = el.dataset.color || el.style.color || '#111827';
        if (fieldColorPicker) fieldColorPicker.value = col;
        if (fieldColorHex) fieldColorHex.value = col;
    }

    function nudgeField(dx, dy) {
        if (!selected) return;
        const currentX = parseFloat(selected.style.left) || 0;
        const currentY = parseFloat(selected.style.top) || 0;
        const newX = Math.max(0, Math.min(98.5, currentX + dx));
        const newY = Math.max(0, Math.min(98.5, currentY + dy));
        selected.style.left = newX.toFixed(2) + '%';
        selected.style.top = newY.toFixed(2) + '%';
        if (fieldCoordX) fieldCoordX.value = newX.toFixed(1);
        if (fieldCoordY) fieldCoordY.value = newY.toFixed(1);
        updateCoordBadge(selected);
    }

    if (!isCode) {
        // Pointer events for smooth mouse & touch dragging
        paper.addEventListener('pointerdown', e => {
            const el = e.target.closest('.draggable-field');
            if (!el) return;
            pick(el);
            const pr = paper.getBoundingClientRect();
            const er = el.getBoundingClientRect();
            drag = {
                el,
                dx: e.clientX - er.left,
                dy: e.clientY - er.top,
                pr
            };
            try {
                el.setPointerCapture(e.pointerId);
            } catch (err) {}
        });

        paper.addEventListener('pointermove', e => {
            if (!drag) return;
            const x = Math.max(0, Math.min(drag.pr.width - drag.el.offsetWidth, e.clientX - drag.pr.left - drag.dx));
            const y = Math.max(0, Math.min(drag.pr.height - drag.el.offsetHeight, e.clientY - drag.pr.top - drag.dy));
            drag.el.style.left = ((x / drag.pr.width) * 100).toFixed(2) + '%';
            drag.el.style.top = ((y / drag.pr.height) * 100).toFixed(2) + '%';
            if (fieldCoordX) fieldCoordX.value = ((x / drag.pr.width) * 100).toFixed(1);
            if (fieldCoordY) fieldCoordY.value = ((y / drag.pr.height) * 100).toFixed(1);
            updateCoordBadge(drag.el);
        });

        paper.addEventListener('pointerup', () => {
            drag = null;
        });

        // Quick list button focus
        document.querySelectorAll('[data-focus-field]').forEach(btn => {
            btn.addEventListener('click', () => {
                const fieldKey = btn.dataset.focusField;
                const el = paper.querySelector(`[data-field="${CSS.escape(fieldKey)}"]`);
                if (el) {
                    pick(el);
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });

        // Mode Toggles
        if (btnModeValOnly) {
            btnModeValOnly.addEventListener('click', () => {
                if (!selected) return;
                selected.dataset.showLabel = '0';
                btnModeValOnly.classList.add('active');
                if (btnModeLabelVal) btnModeLabelVal.classList.remove('active');
                syncFieldContent(selected);
                updateFieldBadge(selected.dataset.field);
            });
        }
        if (btnModeLabelVal) {
            btnModeLabelVal.addEventListener('click', () => {
                if (!selected) return;
                selected.dataset.showLabel = '1';
                btnModeLabelVal.classList.add('active');
                if (btnModeValOnly) btnModeValOnly.classList.remove('active');
                syncFieldContent(selected);
                updateFieldBadge(selected.dataset.field);
            });
        }

        // Visibility
        if (chkFieldVisible) {
            chkFieldVisible.addEventListener('change', () => {
                if (!selected) return;
                const vis = chkFieldVisible.checked;
                selected.dataset.visible = vis ? '1' : '0';
                selected.classList.toggle('is-hidden-field', !vis);
                updateFieldBadge(selected.dataset.field);
            });
        }

        // Exact Coordinate Inputs
        if (fieldCoordX) {
            fieldCoordX.addEventListener('input', () => {
                if (!selected) return;
                const val = Math.max(0, Math.min(99, parseFloat(fieldCoordX.value) || 0));
                selected.style.left = val.toFixed(2) + '%';
                updateCoordBadge(selected);
            });
        }
        if (fieldCoordY) {
            fieldCoordY.addEventListener('input', () => {
                if (!selected) return;
                const val = Math.max(0, Math.min(99, parseFloat(fieldCoordY.value) || 0));
                selected.style.top = val.toFixed(2) + '%';
                updateCoordBadge(selected);
            });
        }

        // Nudge Buttons (0.2% fine step, 1.0% with shift)
        if (nudgeUp) nudgeUp.addEventListener('click', e => nudgeField(0, e.shiftKey ? -1.0 : -0.2));
        if (nudgeDown) nudgeDown.addEventListener('click', e => nudgeField(0, e.shiftKey ? 1.0 : 0.2));
        if (nudgeLeft) nudgeLeft.addEventListener('click', e => nudgeField(e.shiftKey ? -1.0 : -0.2, 0));
        if (nudgeRight) nudgeRight.addEventListener('click', e => nudgeField(e.shiftKey ? 1.0 : 0.2, 0));

        // Keyboard Arrow Nudge
        window.addEventListener('keydown', e => {
            if (!selected) return;
            const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

            const step = e.shiftKey ? 1.0 : 0.2;
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                nudgeField(0, -step);
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                nudgeField(0, step);
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                nudgeField(-step, 0);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                nudgeField(step, 0);
            }
        });

        // Font Size
        if (size) {
            size.addEventListener('input', () => {
                if (sizeOut) sizeOut.textContent = size.value + ' px';
                if (selected) {
                    selected.style.fontSize = size.value + 'px';
                    selected.dataset.fontSize = size.value;
                }
            });
        }

        // Field Width
        if (width) {
            width.addEventListener('input', () => {
                if (widthOut) widthOut.textContent = width.value + ' px';
                if (selected) {
                    selected.style.width = width.value + 'px';
                    selected.dataset.width = width.value;
                }
            });
        }

        // Weight
        if (fieldFontWeight) {
            fieldFontWeight.addEventListener('change', () => {
                if (!selected) return;
                selected.style.fontWeight = fieldFontWeight.value;
                selected.dataset.fontWeight = fieldFontWeight.value;
            });
        }

        // Alignment
        if (fieldAlign) {
            fieldAlign.addEventListener('change', () => {
                if (!selected) return;
                selected.style.textAlign = fieldAlign.value;
                selected.dataset.align = fieldAlign.value;
            });
        }

        // Colors
        if (fieldColorPicker) {
            fieldColorPicker.addEventListener('input', () => {
                if (fieldColorHex) fieldColorHex.value = fieldColorPicker.value;
                if (selected) {
                    selected.style.color = fieldColorPicker.value;
                    selected.dataset.color = fieldColorPicker.value;
                }
            });
        }
        if (fieldColorHex) {
            fieldColorHex.addEventListener('input', () => {
                const hex = fieldColorHex.value.trim();
                if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                    if (fieldColorPicker) fieldColorPicker.value = hex;
                    if (selected) {
                        selected.style.color = hex;
                        selected.dataset.color = hex;
                    }
                }
            });
        }

        // Pad Scan Opacity
        if (padOpacitySlider) {
            padOpacitySlider.addEventListener('input', () => {
                const pct = parseInt(padOpacitySlider.value) || 100;
                if (padOpacityVal) padOpacityVal.textContent = pct + '%';
                if (padScanLayer) {
                    padScanLayer.style.opacity = (pct / 100).toFixed(2);
                }
            });
        }

        // Calibration Metric Grid
        if (chkCalibrationGrid) {
            chkCalibrationGrid.addEventListener('change', () => {
                if (calibrationGridLayer) {
                    calibrationGridLayer.style.display = chkCalibrationGrid.checked ? 'block' : 'none';
                }
            });
        }

        // Initialize badges on load & focus first available field
        paper.querySelectorAll('.draggable-field').forEach(el => {
            updateFieldBadge(el.dataset.field);
            updateCoordBadge(el);
        });

        const firstField = paper.querySelector('.draggable-field');
        if (firstField) pick(firstField);
    }

    function collect() {
        const out = {};
        paper.querySelectorAll('.draggable-field').forEach(el => {
            const fieldKey = el.dataset.field;
            if (!fieldKey) return;
            out[fieldKey] = {
                x: parseFloat(el.style.left) || 0,
                y: parseFloat(el.style.top) || 0,
                fontSize: parseInt(el.dataset.fontSize || el.style.fontSize) || 11,
                width: parseInt(el.dataset.width || el.style.width) || el.offsetWidth || 220,
                fontWeight: el.dataset.fontWeight || el.style.fontWeight || '600',
                align: el.dataset.align || el.style.textAlign || 'left',
                color: el.dataset.color || el.style.color || '#111827',
                showLabel: (el.dataset.showLabel === '1'),
                visible: (el.dataset.visible !== '0')
            };
        });
        return out;
    }

    if (save) {
        save.addEventListener('click', async () => {
            save.disabled = true;
            save.textContent = 'Saving...';
            try {
                // Build payload: sections + layout + code_config
                const payload = {
                    template_id: window.EDITOR_DATA.templateId,
                    sections: sectionState,
                    code_config: isCode ? codeConfig : null,
                    layout: isCode ? { sections: sectionState, code_config: codeConfig } : collect()
                };

                const res = await fetch('api/save_layout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.EDITOR_DATA.csrf
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.ok) throw new Error(data.message || 'Save layout failed');

                // Also save current page settings
                if (typeof savePageSettings === 'function') {
                    await savePageSettings(false);
                }

                save.textContent = 'Saved ✓';
                setTimeout(() => { save.textContent = 'Save my layout'; }, 1400);
            } catch (err) {
                alert(err.message);
                save.textContent = 'Save my layout';
            } finally {
                save.disabled = false;
            }
        });
    }

    if (reset) {
        reset.addEventListener('click', () => {
            if (isCode) {
                sectionState = Object.assign({}, defaultSections);
                renderSectionsLive();
            } else {
                const defs = window.EDITOR_DATA.defaultLayout || {};
                paper.querySelectorAll('.draggable-field').forEach(el => {
                    const p = defs[el.dataset.field];
                    if (!p) return;
                    el.style.left = (p.x || 0) + '%';
                    el.style.top = (p.y || 0) + '%';
                    if (p.fontSize) {
                        el.style.fontSize = p.fontSize + 'px';
                        el.dataset.fontSize = p.fontSize;
                    }
                    if (p.width) {
                        el.style.width = p.width + 'px';
                        el.dataset.width = p.width;
                    }
                    if (p.fontWeight) {
                        el.style.fontWeight = p.fontWeight;
                        el.dataset.fontWeight = p.fontWeight;
                    }
                    if (p.align) {
                        el.style.textAlign = p.align;
                        el.dataset.align = p.align;
                    }
                    if (p.color) {
                        el.style.color = p.color;
                        el.dataset.color = p.color;
                    }
                    if (p.showLabel !== undefined) {
                        el.dataset.showLabel = p.showLabel ? '1' : '0';
                        syncFieldContent(el);
                    }
                    if (p.visible !== undefined) {
                        el.dataset.visible = p.visible ? '1' : '0';
                        el.classList.toggle('is-hidden-field', !p.visible);
                    }
                    updateFieldBadge(el.dataset.field);
                    updateCoordBadge(el);
                });
                if (selected) pick(selected);
            }
        });
    }
})();