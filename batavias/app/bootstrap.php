<?php
declare(strict_types=1);
$root = dirname(__DIR__);
foreach ([$root.'/.env', $root.'/config/.env'] as $envFile) {
 if (is_file($envFile)) { foreach (file($envFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) { $line=trim($line); if ($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue; [$k,$v]=explode('=',$line,2); $_ENV[trim($k)]=trim($v," \t\"'"); putenv(trim($k).'='.trim($v," \t\"'")); } break; }
}
$app=require $root.'/config/app.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_name($app['session_name']); session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']),'samesite'=>'Lax']); session_start(); }
require_once $root.'/app/Helpers/functions.php';
require_once $root.'/app/Services/Database.php';
