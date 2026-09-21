<?php
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/code_template.php';

$user = require_login();
$id = (int)($_GET['id'] ?? 0);
$templateId = (int)($_GET['template_id'] ?? 0);
$pages = (int)($_GET['pages'] ?? 0);
$isModal = !empty($_GET['modal']);

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

$cfg = get_motherland_config();
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

        /* Legacy Image Template Sheet */
        .sheet {
            position: relative;
            width: 210mm;
            height: 297mm;
            margin: 80px auto 30px;
            background: #fff center/100% 100% no-repeat;
            box-shadow: 0 16px 50px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .field {
            position: absolute;
            white-space: pre-wrap;
            line-height: 1.15;
            color: #111;
            font-family: Arial, sans-serif;
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

        @page {
            size: A4 portrait;
            margin: 0mm !important;
        }

        @media print {
            html, body {
                width: 210mm !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .toolbar {
                display: none !important;
            }
            .sheet {
                margin: 0 !important;
                box-shadow: none !important;
                width: 210mm !important;
                height: 295mm !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .code-sheet-wrapper {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
                gap: 0 !important;
            }
            .motherland-sheet {
                margin: 0 !important;
                box-shadow: none !important;
                width: 210mm !important;
                height: 295mm !important;
                max-height: 295mm !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                overflow: hidden !important;
            }
            .motherland-sheet:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .motherland-sheet.page-2 {
                page-break-before: always !important;
                break-before: page !important;
                margin: 0 !important;
            }
        }

        <?php if ($isModal): ?>
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
            <?php foreach($allTemplates as $at): ?>
            <option value="<?= $at['id'] ?>" <?= (int)$at['id'] === (int)$tpl['id'] ? 'selected' : '' ?>>
                <?= e($at['name']) ?><?= is_code_template($at) ? ' (Code)' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
    <div class="toolbar-actions">
        <!-- 1 Page / 2 Pages Toggle -->
        <div class="page-toggle-group">
            <a href="print_opd.php?id=<?= $id ?>&template_id=<?= $tpl['id'] ?>&pages=1" class="<?= $pages === 1 ? 'btn-active' : '' ?>" title="Print 1 Page (Prescription only)">1 Page</a>
            <a href="print_opd.php?id=<?= $id ?>&template_id=<?= $tpl['id'] ?>&pages=2" class="<?= $pages === 2 ? 'btn-active' : '' ?>" title="Print 2 Pages (with consultation notes)">2 Pages</a>
        </div>

        <a href="patient_form.php" class="btn-next-patient" title="Register New Patient (Alt+N)">
            + + Register Next Patient
        </a>
        <button class="btn-print-action" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print Slip (<?= $pages ?> Page<?= $pages > 1 ? 's' : '' ?>)
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
</script>
<?php endif; ?>

<?php if ($isCode): ?>
    <div class="code-sheet-wrapper">
        <?= render_motherland_opd($p, [], false, $layout, $pages) ?>
    </div>
<?php else: ?>
    <div class="sheet" style="background-image:url('<?= e($tpl['file_path']) ?>')">
        <?php foreach ($layout as $k => $pos):
            if (!isset(FIELD_DEFS[$k]) || empty($perm[$k]['visible'])) continue;
            $val = patient_display($p, $k);
        ?>
            <div class="field" style="left:<?= floatval($pos['x'] ?? 5) ?>%;top:<?= floatval($pos['y'] ?? 5) ?>%;font-size:<?= intval($pos['fontSize'] ?? 12) ?>px;width:<?= intval($pos['width'] ?? 220) ?>px;font-weight:<?= e($pos['fontWeight'] ?? '500') ?>">
                <?= e($val) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>
