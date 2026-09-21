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
        if (badgeDot) badgeDot.style.background = c.primary;
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

    // Initialize initial canvas geometry & theme
    updatePageGeometry();
    applyThemeLive();

    // --- DRAG & DROP FIELD/BLOCK LOGIC ---
    function pick(el) {
        paper.querySelectorAll('.draggable-field').forEach(x => x.classList.remove('selected'));
        selected = el;
        if (el) {
            el.classList.add('selected');
            if (size) {
                const s = parseInt(el.style.fontSize) || 12;
                size.value = s;
                if (sizeOut) sizeOut.textContent = s + ' px';
            }
            if (width) {
                const w = parseInt(el.style.width) || el.offsetWidth || 220;
                width.value = w;
                if (widthOut) widthOut.textContent = w + ' px';
            }
        }
    }

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
    });

    paper.addEventListener('pointerup', () => {
        drag = null;
    });

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

    if (size) {
        size.addEventListener('input', () => {
            if (sizeOut) sizeOut.textContent = size.value + ' px';
            if (selected) selected.style.fontSize = size.value + 'px';
        });
    }

    if (width) {
        width.addEventListener('input', () => {
            if (widthOut) widthOut.textContent = width.value + ' px';
            if (selected) selected.style.width = width.value + 'px';
        });
    }

    function collect() {
        const out = {};
        paper.querySelectorAll('.draggable-field').forEach(el => {
            const fieldKey = el.dataset.field;
            if (!fieldKey) return;
            out[fieldKey] = {
                x: parseFloat(el.style.left) || 0,
                y: parseFloat(el.style.top) || 0,
                fontSize: parseInt(el.style.fontSize) || 12,
                width: parseInt(el.style.width) || el.offsetWidth || 220,
                fontWeight: '500'
            };
        });
        return out;
    }

    if (save) {
        save.addEventListener('click', async () => {
            save.disabled = true;
            save.textContent = 'Saving...';
            try {
                // Save layout positions
                const res = await fetch('api/save_layout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.EDITOR_DATA.csrf
                    },
                    body: JSON.stringify({
                        template_id: window.EDITOR_DATA.templateId,
                        layout: collect()
                    })
                });
                const data = await res.json();
                if (!data.ok) throw new Error(data.message || 'Save layout failed');

                // Also save current page and theme settings
                await savePageSettings(false);
                await saveThemeSettings(false);

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
            const defs = window.EDITOR_DATA.defaultLayout || {};
            paper.querySelectorAll('.draggable-field').forEach(el => {
                const p = defs[el.dataset.field];
                if (!p) return;
                el.style.left = (p.x || 0) + '%';
                el.style.top = (p.y || 0) + '%';
                if (p.fontSize) el.style.fontSize = p.fontSize + 'px';
                if (p.width) el.style.width = p.width + 'px';
            });
        });
    }
})();