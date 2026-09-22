<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/code_template.php';

$user = require_login();
$id = (int)($_GET['id'] ?? 0);
$templateId = (int)($_GET['template_id'] ?? 0);
$pages = (int)($_GET['pages'] ?? 0);
$isModal = !empty($_GET['modal']);
$printBg = !empty($_GET['print_bg']) && $_GET['print_bg'] == '1';
$hideScanScreen = !empty($_GET['hide_scan']) && $_GET['hide_scan'] == '1';

if ($id > 0) {
    $st = db()->prepare('SELECT * FROM patients WHERE id=?');
    $st->execute([$id]);
    $p = $st->fetch();
    if (!$p) {
        http_response_code(404);
        exit('Patient not found');
    }
} else {
    // Sample preview mode (e.g. previewing template directly from admin templates page)
    $p = [
        'uhid' => 'UHID-' . date('Ymd') . '-0001',
        'name' => 'Sample Patient Name',
        'age' => '28',
        'sex' => 'Female',
        'age_sex' => '28 / Female',
        'guardian' => 'Guardian Name',
        'contact_number' => '+91 98765 43210',
        'address' => 'Sample Address, Sector 12, City',
        'bill_no' => 'BILL-' . date('Ymd') . '-001',
        'visit_date' => date('Y-m-d'),
        'visit_time' => date('H:i:s'),
        'panel' => 'CASH',
        'doctor_dept' => 'IVF',
        'room_no' => '102',
        'app_no' => '1'
    ];
}

$tpl = active_template($templateId ?: null);
if (!$tpl) {
    exit('No active template available.');
}

$isCode = is_code_template($tpl);
$layout = get_layout($tpl, (int)$user['id']);
$perm = field_permissions((int)$user['id']);
$pageConfig = get_template_page_config($tpl);
$theme = get_template_theme($tpl);

$cfg = get_motherland_config($tpl);
if ($pages <= 0) {
    if (!empty($cfg['default_print_pages']) && (int)$cfg['default_print_pages'] === 2) {
        $pages = 2;
    } elseif (!empty($cfg['enable_two_pages']) && (int)$cfg['enable_two_pages'] === 1) {
        $pages = 2;
    } else {
        $pages = 1;
    }
}

// All active templates for the switch dropdown
$allTemplates = db()->query('SELECT id, name, template_type, file_path FROM templates WHERE active=1 ORDER BY id ASC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print OPD · <?= e($p['name']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #dfe6e5;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            color: #111;
        }
        .toolbar {
            position: fixed;
            left: 0;
            right: 0;
            top: 0;
            height: 60px;
            background: #0f2e2a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px;
            z-index: 100;
        }
        .toolbar-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .toolbar-info strong {
            font-size: 15px;
        }
        .toolbar .hint {
            opacity: .8;
            font-size: 13px;
        }
        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toolbar select {
            background: #173d37;
            color: #fff;
            border: 1px solid #28574f;
            border-radius: 4px;
            padding: 7px 11px;
            font-size: 12.5px;
            outline: none;
            cursor: pointer;
        }
        .toolbar a, .toolbar button {
            border: 1px solid transparent;
            border-radius: 4px;
            padding: 7px 14px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 700;
            font-size: 12.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .toolbar a.btn-toolbar-nav {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.18);
        }
        .toolbar a.btn-toolbar-nav:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }
        .toolbar a.btn-toolbar-white {
            background: #ffffff;
            color: #064e3b;
            border-color: #cbd5e1;
        }
        .toolbar a.btn-toolbar-white:hover {
            background: #f0fdf4;
            color: #047857;
        }
        .toolbar a.btn-next-patient {
            background: #10b981;
            color: #ffffff;
            border-color: #059669;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
        }
        .toolbar a.btn-next-patient:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        .toolbar button.btn-print-action {
            background: #3b82f6;
            color: #fff;
            border-color: #2563eb;
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
        }
        .toolbar button.btn-print-action:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        /* Page toggle buttons */
        .page-toggle-group {
            display: flex;
            background: #173d37;
            border-radius: 4px;
            padding: 2px;
            border: 1px solid #28574f;
        }
        .page-toggle-group a {
            background: transparent;
            color: #a7c9c0;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 11.5px;
        }
        .page-toggle-group a.btn-active {
            background: #10b981;
            color: #fff;
        }

        /* Pre-Printed Pad Scan Sheet */
        .sheet {
            position: relative;
            background: #fff center/100% 100% no-repeat;
            box-shadow: 0 16px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            box-sizing: border-box;
        }
        .sheet.hide-scan {
            background-image: none !important;
            background: #ffffff !important;
            border: 1px dashed #94a3b8;
        }
        .field {
            position: absolute;
            white-space: pre-wrap;
            line-height: 1.15;
            color: #111;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            box-sizing: border-box;
        }

        @media print {
            <?php if (!$isCode && !$printBg): ?>
            /* Physical stationery mode: prints text only to prevent toner waste on pre-printed pads */
            .sheet {
                background-image: none !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
            }
            <?php endif; ?>
        }

        /* Screen Wrapper for Code Template */
        .code-sheet-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            margin: 80px auto 40px;
            background: transparent;
        }

        <?= get_motherland_opd_css() ?>
        <?= generate_template_page_css($tpl, $isModal) ?>

        <?php if ($isModal): ?>
        @media screen {
            body {
                background: #eef2f6 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .toolbar {
                display: none !important;
            }
            .code-sheet-wrapper {
                margin: 18px auto 36px !important;
                gap: 22px !important;
            }
            .sheet {
                margin: 18px auto 36px !important;
            }
        }
        <?php endif; ?>
    </style>
</head>
<body>

<?php if (!$isModal): ?>
<div class="toolbar">
    <div class="toolbar-info">
        <div>
            <strong>OPD Print Preview</strong>
            <div class="hint"><?= e($p['name']) ?> · <?= e($p['uhid']) ?></div>
        </div>
        <?php if(count($allTemplates) > 1): ?>
        <select onchange="location='print_opd.php?id=<?= $id ?>&template_id='+this.value+'&pages=<?= $pages ?>'">
            <?php foreach($allTemplates as $at): 
                $isCodeT = is_code_template($at);
            ?>
            <option value="<?= $at['id'] ?>" <?= (int)$at['id'] === (int)$tpl['id'] ? 'selected' : '' ?>>
                <?= $isCodeT ? '🩺 Digital Code: ' : '🖼️ Uploaded Pad: ' ?><?= e($at['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <span style="font-size:11.5px;background:rgba(255,255,255,0.12);padding:4px 9px;border-radius:4px;color:#cbd5e1;border:1px solid rgba(255,255,255,0.18);white-space:nowrap;" title="Template Paper Size">
            📄 <?= e($pageConfig['label']) ?> (<?= $pageConfig['width'] ?>×<?= $pageConfig['height'] ?> <?= $pageConfig['unit'] ?>)
        </span>
        <span style="font-size:11.5px;background:rgba(255,255,255,0.12);padding:4px 9px;border-radius:4px;color:#cbd5e1;border:1px solid rgba(255,255,255,0.18);white-space:nowrap;display:inline-flex;align-items:center;gap:6px;" title="Template Theme">
            <span style="width:9px;height:9px;border-radius:50%;background:<?= htmlspecialchars($theme['primary']) ?>;display:inline-block;box-shadow:0 0 0 1px rgba(255,255,255,0.5);"></span>
            🎨 <?= e($theme['name'] ?? 'Theme') ?>
        </span>
    </div>
    <div class="toolbar-actions">
        <?php if ($isCode): ?>
            <!-- 1 Page / 2 Pages Toggle for Code Template -->
            <div class="page-toggle-group">
                <a href="print_opd.php?id=<?= $id ?>&template_id=<?= $tpl['id'] ?>&pages=1" class="<?= $pages === 1 ? 'btn-active' : '' ?>" title="Print 1 Page (Prescription only)">1 Page</a>
                <a href="print_opd.php?id=<?= $id ?>&template_id=<?= $tpl['id'] ?>&pages=2" class="<?= $pages === 2 ? 'btn-active' : '' ?>" title="Print 2 Pages (with consultation notes)">2 Pages</a>
            </div>
        <?php else: ?>
            <!-- Pad Scan Tools for Physical Pre-Printed Stationery -->
            <button type="button" class="btn-toolbar-nav" id="btnToggleScan" onclick="togglePadScan()" title="Show/Hide scanned pad image on screen">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                Pad Scan: <span id="scanStateText"><?= $hideScanScreen ? 'Hidden' : 'Visible' ?></span>
            </button>
            <label class="btn-toolbar-nav" style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-size:12px;user-select:none;" title="Unchecked = Prints text only for pre-printed pads. Checked = Prints scanned pad image on plain blank paper.">
                <input type="checkbox" id="chkPrintBg" <?= $printBg ? 'checked' : '' ?> onchange="togglePrintBg(this.checked)" style="accent-color:#10b981;cursor:pointer;margin:0;">
                <span>Print Scan Bg</span>
            </label>
            <a href="template_editor.php?template_id=<?= $tpl['id'] ?>" class="btn-toolbar-white" title="Open Calibration Studio to fine-tune field coordinates">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Calibrate Pad
            </a>
        <?php endif; ?>

        <a href="patient_form.php" class="btn-next-patient" title="Register New Patient (Alt+N)">
            + + Register Next Patient
        </a>
        <button class="btn-print-action" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <?= $isCode ? "Print Slip ({$pages} Page" . ($pages > 1 ? 's' : '') . ")" : 'Print Pad Slip' ?>
        </button>
        <?php if ($id > 0): ?>
            <a href="patient_profile.php?id=<?= $id ?>" class="btn-toolbar-white" title="View Patient Profile">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Profile
            </a>
            <a href="patient_form.php?id=<?= $id ?>" class="btn-toolbar-nav" title="Edit Patient Info">Edit</a>
        <?php endif; ?>
        <a href="patients.php" class="btn-toolbar-nav" title="Patients Directory">Patients</a>
    </div>
</div>

<script>
document.addEventListener('keydown', function(e) {
    if ((e.altKey && (e.key === 'n' || e.key === 'N')) || (e.altKey && e.code === 'KeyN')) {
        e.preventDefault();
        window.location.href = 'patient_form.php';
    }
});
function togglePadScan() {
    var sheet = document.querySelector('.sheet');
    if (!sheet) return;
    sheet.classList.toggle('hide-scan');
    var isHidden = sheet.classList.contains('hide-scan');
    var txt = document.getElementById('scanStateText');
    if (txt) txt.textContent = isHidden ? 'Hidden' : 'Visible';
}
function togglePrintBg(checked) {
    var url = new URL(window.location.href);
    if (checked) {
        url.searchParams.set('print_bg', '1');
    } else {
        url.searchParams.delete('print_bg');
    }
    window.location.href = url.toString();
}
</script>
<?php endif; ?>

<?php if ($isCode): ?>
    <div class="code-sheet-wrapper">
        <?= render_motherland_opd($p, [], false, $layout, $pages, $tpl) ?>
    </div>
<?php else: ?>
    <div class="sheet <?= $hideScanScreen ? 'hide-scan' : '' ?>" style="background-image:url('<?= e($tpl['file_path']) ?>')">
        <?php foreach ($layout as $k => $pos):
            if (!isset(FIELD_DEFS[$k])) continue;
            if (isset($pos['visible']) && $pos['visible'] === false) continue;
            if (empty($perm[$k]['visible'])) continue;

            $val = patient_display($p, $k);
            if ($val === '' || $val === null) $val = '';

            $showLabel = isset($pos['showLabel']) ? (bool)$pos['showLabel'] : false;
            $labelPrefix = $showLabel ? (FIELD_DEFS[$k] . ': ') : '';
            $displayText = $labelPrefix . $val;

            $align = in_array($pos['align'] ?? '', ['left', 'center', 'right'], true) ? $pos['align'] : 'left';
            $fontWeight = in_array((string)($pos['fontWeight'] ?? ''), ['400', '500', '600', '700', '800'], true) ? $pos['fontWeight'] : '500';
            $color = !empty($pos['color']) ? htmlspecialchars($pos['color']) : '#111827';
            $fontSize = intval($pos['fontSize'] ?? 12);
            $width = intval($pos['width'] ?? 220);
            $x = floatval($pos['x'] ?? 5);
            $y = floatval($pos['y'] ?? 5);
        ?>
            <div class="field" style="left:<?= $x ?>%;top:<?= $y ?>%;font-size:<?= $fontSize ?>px;width:<?= $width ?>px;font-weight:<?= $fontWeight ?>;text-align:<?= $align ?>;color:<?= $color ?>;">
                <?= e($displayText) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>
