<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['create_user'])) {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$name || !$username || strlen($password) < 6) {
            flash('error', 'Name, username and a password of at least 6 characters are required.');
        } else {
            try {
                $st = db()->prepare("INSERT INTO users(name,username,password_hash,role,active) VALUES(?,?,?,'reception',1)");
                $st->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
                $uid = (int)db()->lastInsertId();
                $ins = db()->prepare('INSERT INTO user_field_permissions(user_id,field_key,visible,editable) VALUES(?,?,1,1)');
                foreach (FIELD_DEFS as $k => $v) $ins->execute([$uid, $k]);
                flash('success', 'Reception user created.');
            } catch (Throwable $e) {
                flash('error', 'Username already exists or could not be created.');
            }
        }
        header('Location: users.php');
        exit;
    }
    if (isset($_POST['save_permissions'])) {
        $uid = (int)$_POST['user_id'];
        $target = db()->prepare("SELECT * FROM users WHERE id=? AND role='reception'");
        $target->execute([$uid]);
        if ($target->fetch()) {
            $st = db()->prepare('INSERT INTO user_field_permissions(user_id,field_key,visible,editable) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE visible=VALUES(visible),editable=VALUES(editable)');
            foreach (FIELD_DEFS as $k => $label) {
                $visible = isset($_POST['visible'][$k]) ? 1 : 0;
                $editable = $visible && isset($_POST['editable'][$k]) ? 1 : 0;
                $st->execute([$uid, $k, $visible, $editable]);
            }
            flash('success', 'Field permissions updated.');
        }
        header('Location: users.php?user_id=' . $uid);
        exit;
    }
    if (isset($_POST['toggle_user'])) {
        $uid = (int)$_POST['toggle_user'];
        $st = db()->prepare("UPDATE users SET active=1-active WHERE id=? AND role='reception'");
        $st->execute([$uid]);
        flash('success', 'User status changed.');
        header('Location: users.php');
        exit;
    }
}

$users = db()->query("SELECT * FROM users WHERE role='reception' ORDER BY active DESC,name")->fetchAll();
$selected = (int)($_GET['user_id'] ?? ($users[0]['id'] ?? 0));
$selectedUser = null;
foreach ($users as $u) {
    if ((int)$u['id'] === $selected) $selectedUser = $u;
}
$sp = $selectedUser ? field_permissions($selected) : [];

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-title"><div><h2>Reception Users & Field Control</h2><p>Block/unblock each patient field and decide whether a receptionist can edit it.</p></div></div><div class="grid-2"><section class="card"><h2>Create reception user</h2><form method="post" class="stack"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="create_user" value="1"><label>Full name<input name="name" required></label><label>Username<input name="username" required></label><label>Temporary password<input type="password" name="password" minlength="6" required></label><button class="btn btn-primary">Create user</button></form><hr><div class="user-list"><?php foreach($users as $u):?><div class="user-row"><a href="users.php?user_id=<?=$u['id']?>"><strong><?=e($u['name'])?></strong><small>@<?=e($u['username'])?> · <?=$u['active']?'Active':'Blocked'?></small></a><form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><button class="mini" name="toggle_user" value="<?=$u['id']?>"><?=$u['active']?'Block':'Unblock'?></button></form></div><?php endforeach;?></div></section>
<section class="card"><h2>Field permissions<?= $selectedUser?' · '.e($selectedUser['name']):''?></h2><?php if($selectedUser):?><form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="save_permissions" value="1"><input type="hidden" name="user_id" value="<?=$selectedUser['id']?>"><div class="permission-table"><div class="permission-head"><span>Field</span><span>Visible</span><span>Editable</span></div><?php foreach(FIELD_DEFS as $k=>$label):?><div class="permission-row"><strong><?=e($label)?></strong><input type="checkbox" name="visible[<?=e($k)?>]" <?=!empty($sp[$k]['visible'])?'checked':''?>><input type="checkbox" name="editable[<?=e($k)?>]" <?=!empty($sp[$k]['editable'])?'checked':''?>></div><?php endforeach;?></div><button class="btn btn-primary">Save permissions</button></form><?php else:?><div class="empty">Create a reception user first.</div><?php endif;?></section></div>
<?php require __DIR__.'/includes/footer.php'; ?>
