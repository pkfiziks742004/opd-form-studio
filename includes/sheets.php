<?php
require_once __DIR__ . '/settings.php';

function patient_payload(int $patientId): ?array {
    $st=db()->prepare('SELECT p.*,u.name AS created_by_name FROM patients p LEFT JOIN users u ON u.id=p.created_by WHERE p.id=?');
    $st->execute([$patientId]);
    $p=$st->fetch();
    if (!$p) return null;
    return [
        'patient_id'=>$p['id'],'uhid'=>$p['uhid'],'name'=>$p['name'],'age'=>$p['age'],'sex'=>$p['sex'],
        'guardian'=>$p['guardian'],'contact_number'=>$p['contact_number'],'address'=>$p['address'],
        'bill_no'=>$p['bill_no'],'visit_date'=>$p['visit_date'],'visit_time'=>$p['visit_time'],
        'panel'=>$p['panel'],'doctor_dept'=>$p['doctor_dept'],'room_no'=>$p['room_no'],'app_no'=>$p['app_no'],
        'created_by'=>$p['created_by_name'],'created_at'=>$p['created_at'],'updated_at'=>$p['updated_at']
    ];
}
function queue_sheet_sync(int $patientId): void {
    $payload=patient_payload($patientId); if(!$payload) return;
    $st=db()->prepare("INSERT INTO sheet_sync_queue(patient_id,payload_json,status,attempts) VALUES(?,?,'pending',0)
        ON DUPLICATE KEY UPDATE payload_json=VALUES(payload_json),status='pending'");
    $st->execute([$patientId,json_encode($payload,JSON_UNESCAPED_UNICODE)]);
    attempt_sheet_sync($patientId);
}
function http_post_json(string $url,array $payload): array {
    $json=json_encode($payload,JSON_UNESCAPED_UNICODE);
    if (function_exists('curl_init')) {
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$json,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>true]);
        $body=curl_exec($ch); $err=curl_error($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
        return [$code,$body ?: '',$err];
    }
    $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json
",'content'=>$json,'timeout'=>12,'ignore_errors'=>true]]);
    $body=@file_get_contents($url,false,$ctx); $code=0;
    $headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($GLOBALS['http_response_header'] ?? null);
    if(is_array($headers) && isset($headers[0]) && preg_match('/\s(\d{3})\s/',$headers[0],$m)) $code=(int)$m[1];
    return [$code,$body ?: '',''];
}
function attempt_sheet_sync(int $patientId): bool {
    $url=trim(setting('google_sheets_web_app_url')); $token=trim(setting('google_sheets_token'));
    if(!$url || !$token) return false;
    $st=db()->prepare('SELECT * FROM sheet_sync_queue WHERE patient_id=?'); $st->execute([$patientId]); $q=$st->fetch();
    if(!$q) return false;
    $payload=['token'=>$token,'action'=>'upsert_patient','patient'=>json_decode($q['payload_json'],true)];
    [$code,$body,$err]=http_post_json($url,$payload);
    $ok=$code>=200 && $code<300;
    $upd=db()->prepare('UPDATE sheet_sync_queue SET status=?,attempts=attempts+1,last_error=?,last_attempt_at=NOW() WHERE patient_id=?');
    $upd->execute([$ok?'synced':'failed',$ok?'':mb_substr($err ?: $body,0,500),$patientId]);
    return $ok;
}
