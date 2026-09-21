(() => {
    const paper = document.getElementById('layoutPaper');
    const save = document.getElementById('saveLayout');
    const reset = document.getElementById('resetLayout');
    const size = document.getElementById('fontSize');
    const width = document.getElementById('fieldWidth');
    const sizeOut = document.getElementById('fontSizeValue');
    const widthOut = document.getElementById('fieldWidthValue');

    if (!paper) return;

    let selected = null;
    let drag = null;

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
                if (!data.ok) throw new Error(data.message || 'Save failed');
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