<?php
require_once __DIR__ . '/config.php';

function current_user(): ?array {
    static $cached = false;
    if ($cached !== false) return $cached;
    if (empty($_SESSION['user_id'])) return $cached = null;
    $st=db()->prepare('SELECT id,name,username,role,active FROM users WHERE id=? LIMIT 1');
    $st->execute([$_SESSION['user_id']]);
    $u=$st->fetch();
    if (!$u || !$u['active']) { logout_user(); return $cached=null; }
    return $cached=$u;
}
function require_login(): array {
    $u=current_user();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') {
        flash('error', 'Access denied. Administrator privileges are required.');
        if (!headers_sent()) {
            header('Location: dashboard.php');
            exit;
        } else {
            echo '<script>window.location.href="dashboard.php";</script>';
            exit;
        }
    }
    return $u;
}
function logout_user(): void {
    $_SESSION=[];
    if (ini_get('session.use_cookies')) {
        $p=session_get_cookie_params();
        setcookie(session_name(),'',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']);
    }
    session_destroy();
}
function field_permissions(int $userId): array {
    $all=[];
    foreach (FIELD_DEFS as $k=>$label) $all[$k]=['visible'=>true,'editable'=>true];
    $who=db()->prepare('SELECT role FROM users WHERE id=? LIMIT 1');
    $who->execute([$userId]);
    $target=$who->fetch();
    if ($target && $target['role']==='admin') return $all;
    $st=db()->prepare('SELECT field_key,visible,editable FROM user_field_permissions WHERE user_id=?');
    $st->execute([$userId]);
    foreach($st->fetchAll() as $r) {
        $all[$r['field_key']]=['visible'=>(bool)$r['visible'],'editable'=>(bool)$r['editable']];
    }
    return $all;
}
function patient_display(array $p, string $key): string {
    return match($key) {
        'age_sex' => trim(($p['age'] ?? '') . (($p['age'] ?? '') !== '' && ($p['sex'] ?? '') !== '' ? ' / ' : '') . ($p['sex'] ?? '')),
        'date' => !empty($p['visit_date']) 
            ? date('d-M-Y', strtotime($p['visit_date'])) . (!empty($p['visit_time']) ? ' ' . date('h:i A', strtotime($p['visit_time'])) : ' ' . date('h:i A')) 
            : date('d-M-Y h:i A'),
        default => (string)($p[$key] ?? '')
    };
}
