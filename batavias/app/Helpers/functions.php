<?php
function db(): PDO { return Database::connection(); }
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path=''): string { $base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'); return ($base==='/'?'':$base).'/'.ltrim($path,'/'); }
function redirect(string $path): never { header('Location: '.url($path)); exit; }
function csrf_token(): string { if(empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { if($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['_csrf']??'', $_POST['_csrf']??'')) { http_response_code(419); exit('CSRF token tidak valid. Muat ulang halaman.'); } }
function user(): ?array { return $_SESSION['user']??null; }
function require_auth(): void { if(!user()) redirect('login'); }
function require_role(string ...$roles): void { require_auth(); if(!in_array(user()['role'],$roles,true)){http_response_code(403); exit('403 — Akses ditolak.');} }
function flash(string $key, ?string $value=null): ?string { if($value!==null){$_SESSION['_flash'][$key]=$value;return null;} $v=$_SESSION['_flash'][$key]??null;unset($_SESSION['_flash'][$key]);return $v; }
function log_event(string $type,string $message,?int $actor=null): void { try { $s=db()->prepare('INSERT INTO system_logs (level,category,message,user_id,created_at) VALUES (?,?,?,?,NOW())');$s->execute(['info',$type,$message,$actor]); } catch(Throwable $e){} }
