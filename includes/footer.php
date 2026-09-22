</section></main></div>
<?php require_once __DIR__ . '/patient_modal.php'; ?>
<?php require_once __DIR__ . '/print_modal.php'; ?>
<?php require_once __DIR__ . '/notice_modal.php'; ?>
<?php $appJsVer = file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : '2.2'; ?>
<script src="assets/js/app.js?v=<?= $appJsVer ?>"></script>
</body></html>