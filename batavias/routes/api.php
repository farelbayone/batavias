<?php
header('Content-Type: application/json; charset=utf-8');
function api_out(int $code,array $data): never {http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
$started=microtime(true);$method=$_SERVER['REQUEST_METHOD'];$route=substr($path,8);
try{
 if($route==='health'&&$method==='GET'){db()->query('SELECT 1');api_out(200,['success'=>true,'application'=>'Batavias','database'=>'connected','php'=>PHP_VERSION]);}
 if($route==='events'&&$method==='GET'){$rows=db()->query("SELECT id,title,slug,description,city,start_date,end_date FROM events WHERE status='published' ORDER BY start_date")->fetchAll();api_out(200,['success'=>true,'data'=>$rows]);}
 if($route==='me'&&$method==='GET'){if(!user())api_out(401,['success'=>false,'message'=>'Unauthenticated']);$u=user();unset($u['password']);api_out(200,['success'=>true,'data'=>$u]);}
 api_out(404,['success'=>false,'message'=>'Endpoint not found']);
}catch(Throwable $e){try{$s=db()->prepare('INSERT INTO api_logs(method,path,status_code,duration_ms,created_at) VALUES(?,?,?,?,NOW())');$s->execute([$method,$path,500,(int)((microtime(true)-$started)*1000)]);}catch(Throwable $ignored){}api_out(500,['success'=>false,'message'=>'Internal server error']);}
