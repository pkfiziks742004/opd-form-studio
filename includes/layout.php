<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/code_template.php';
require_once __DIR__ . '/page_engine.php';
require_once __DIR__ . '/theme_engine.php';

function is_code_template(?array $template): bool {
    if (!$template) return false;
    return (($template['template_type'] ?? '') === 'code') || str_starts_with((string)($template['file_path'] ?? ''), 'code:');
}

function active_template(?int $templateId=null): ?array {
    if ($templateId) {
        $st=db()->prepare('SELECT * FROM templates WHERE id=? AND active=1');
        $st->execute([$templateId]);
        $res = $st->fetch();
        if ($res) return $res;
    }
    $defId = (int)setting('default_template_id', '0');
    if ($defId > 0) {
        $st = db()->prepare('SELECT * FROM templates WHERE id=? AND active=1');
        $st->execute([$defId]);
        $res = $st->fetch();
        if ($res) return $res;
    }
    $st=db()->query('SELECT * FROM templates WHERE active=1 ORDER BY id ASC LIMIT 1');
    return $st->fetch() ?: null;
}

function get_layout(array $template,int $userId): array {
    $st=db()->prepare('SELECT layout_json FROM template_layouts WHERE template_id=? AND user_id=?');
    $st->execute([$template['id'],$userId]); $r=$st->fetch();
    $json=$r['layout_json'] ?? ($template['default_layout_json'] ?? '{}');
    $arr=json_decode((string)$json,true); return is_array($arr)?$arr:[];
}

