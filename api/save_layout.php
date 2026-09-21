<?php
require_once dirname(__DIR__).'/includes/auth.php';
header('Content-Type: application/json');$user=require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['ok'=>false]);exit;} verify_csrf();
$input=json_decode(file_get_contents('php://input'),true);$templateId=(int)($input['template_id']??0);$layout=$input['layout']??null;
if(!$templateId||!is_array($layout)){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'Invalid layout']);exit;}
$clean=[];
foreach($layout as $k=>$v){
    if(!is_array($v)) continue;
    if(!isset(FIELD_DEFS[$k]) && !str_starts_with($k, 'block_')) continue;
    $clean[$k]=[
        'x'=>max(0,min(98,(float)($v['x']??0))),
        'y'=>max(0,min(98,(float)($v['y']??0))),
        'fontSize'=>max(8,min(36,(int)($v['fontSize']??12))),
        'width'=>max(40,min(900,(int)($v['width']??220))),
        'fontWeight'=>'500'
    ];
}
$st=db()->prepare('INSERT INTO template_layouts(template_id,user_id,layout_json) VALUES(?,?,?) ON DUPLICATE KEY UPDATE layout_json=VALUES(layout_json),updated_at=NOW()');$st->execute([$templateId,$user['id'],json_encode($clean)]);echo json_encode(['ok'=>true]);
