(() => {
    const form = document.getElementById('patientForm');
    const paper = document.getElementById('paperPreview');
    const select = document.getElementById('templateSelect');
    const adjustLink = document.getElementById('adjustLayoutLink');

    if (!form || !paper) return;

    function getValues() {
        const dVal = form.visit_date?.value;
        const tVal = form.visit_time?.value;
        let dateStr = '';
        if (dVal) {
            const parts = dVal.split('-');
            if (parts.length === 3) {
                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                const mIdx = parseInt(parts[1], 10) - 1;
                dateStr = `${parts[2]}-${months[mIdx] || parts[1]}-${parts[0]}`;
            }
        }
        if (tVal) {
            const tParts = tVal.split(':');
            if (tParts.length >= 2) {
                let hours = parseInt(tParts[0], 10);
                const minutes = tParts[1];
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;
                const hStr = hours < 10 ? '0' + hours : hours;
                dateStr += (dateStr ? ' ' : '') + `${hStr}:${minutes} ${ampm}`;
            }
        }

        return {
            uhid: form.uhid?.value || '',
            name: form.name?.value || '',
            age_sex: [form.age?.value, form.sex?.value].filter(Boolean).join(' / '),
            guardian: form.guardian?.value || '',
            contact_number: form.contact_number?.value || '',
            address: form.address?.value || '',
            bill_no: form.bill_no?.value || '',
            date: dateStr,
            panel: form.panel?.value || '',
            doctor_dept: form.doctor_dept?.value || '',
            room_no: form.room_no?.value || '',
            app_no: form.app_no?.value || ''
        };
    }

    function scaleCodeSheet() {
        const sheet = paper.querySelector('.motherland-sheet');
        if (!sheet) return;
        const paperWidth = paper.clientWidth;
        if (paperWidth > 0) {
            const a4WidthPx = 793.7; // 210mm in standard CSS 96dpi
            const scale = paperWidth / a4WidthPx;
            sheet.style.transform = `scale(${scale})`;
        }
    }

    function refresh() {
        const v = getValues();
        const isCode = paper.dataset.mode === 'code' || !!paper.querySelector('.motherland-sheet');

        if (isCode) {
            const setVal = (sel, val) => {
                const el = paper.querySelector(sel);
                if (el) el.textContent = val;
            };
            setVal('.ml-val-uhid', v.uhid);
            setVal('.ml-val-name', v.name);
            setVal('.ml-val-age_sex', v.age_sex);
            setVal('.ml-val-guardian', v.guardian);
            setVal('.ml-val-contact', v.contact_number);
            setVal('.ml-val-address', v.address);
            setVal('.ml-val-bill', v.bill_no);
            setVal('.ml-val-date', v.date);
            setVal('.ml-val-panel', v.panel);
            if (v.doctor_dept) setVal('.ml-val-dept', v.doctor_dept);
            setVal('.ml-val-room', v.room_no);
            setVal('.ml-val-app', v.app_no);
            scaleCodeSheet();
        } else {
            paper.querySelectorAll('[data-field]').forEach(el => {
                el.textContent = v[el.dataset.field] || '';
            });
        }
    }

    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    window.addEventListener('resize', scaleCodeSheet);
    window.addEventListener('load', scaleCodeSheet);

    if (window.ResizeObserver) {
        new ResizeObserver(() => scaleCodeSheet()).observe(paper);
    }

    // Initial scale and refresh
    scaleCodeSheet();
    refresh();
    setTimeout(scaleCodeSheet, 50);

    if (select) {
        select.addEventListener('change', async () => {
            try {
                const r = await fetch('api/template_layout.php?id=' + encodeURIComponent(select.value));
                const d = await r.json();
                if (!d.ok) return;

                if (d.type === 'code') {
                    paper.dataset.mode = 'code';
                    paper.style.backgroundImage = 'none';
                    paper.innerHTML = d.html || '';
                    if (adjustLink) {
                        adjustLink.href = 'templates.php';
                        adjustLink.textContent = 'Manage template';
                    }
                    scaleCodeSheet();
                    refresh();
                } else {
                    paper.dataset.mode = 'image';
                    paper.style.backgroundImage = `url('${d.file}')`;
                    paper.innerHTML = '';
                    if (adjustLink) {
                        adjustLink.href = 'template_editor.php?template_id=' + d.id;
                        adjustLink.textContent = 'Adjust layout';
                    }
                    Object.entries(d.layout || {}).forEach(([k, p]) => {
                        const s = document.createElement('span');
                        s.className = 'preview-field';
                        s.dataset.field = k;
                        s.style.left = (p.x || 0) + '%';
                        s.style.top = (p.y || 0) + '%';
                        s.style.fontSize = (p.fontSize || 12) + 'px';
                        s.style.maxWidth = (p.width || 220) + 'px';
                        paper.appendChild(s);
                    });
                    refresh();
                }
            } catch (e) {
                console.error(e);
            }
        });
    }

    // Auto generate UHID and Bill buttons from server numbers API
    document.querySelectorAll('[data-generate]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const type = btn.dataset.generate;
            const dVal = form.visit_date?.value || '';
            try {
                const r = await fetch('api/next_numbers.php?date=' + encodeURIComponent(dVal));
                const d = await r.json();
                if (d.ok) {
                    if (type === 'uhid' && form.uhid) {
                        form.uhid.value = d.uhid;
                        form.uhid.dispatchEvent(new Event('input'));
                    } else if (type === 'bill' && form.bill_no) {
                        form.bill_no.value = d.bill_no;
                        form.bill_no.dispatchEvent(new Event('input'));
                    }
                }
            } catch (e) {
                console.error(e);
            }
        });
    });

    // When visit_date changes, automatically fetch and update the daily Appointment No
    if (form.visit_date) {
        form.visit_date.addEventListener('change', async () => {
            const dVal = form.visit_date.value;
            if (!dVal) return;
            try {
                const r = await fetch('api/next_numbers.php?date=' + encodeURIComponent(dVal));
                const d = await r.json();
                if (d.ok && form.app_no) {
                    form.app_no.value = d.app_no;
                    form.app_no.dispatchEvent(new Event('input'));
                }
            } catch (e) {
                console.error(e);
            }
        });
    }

    const btnNowTime = document.getElementById('btnNowTime');
    if (btnNowTime) {
        btnNowTime.addEventListener('click', () => {
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            const timeInput = document.getElementById('visit_time');
            if (timeInput) {
                timeInput.value = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
                timeInput.dispatchEvent(new Event('input'));
            }
        });
    }
})();