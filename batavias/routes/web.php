<?php
require_once dirname(__DIR__).'/app/Controllers/AuthController.php';require_once dirname(__DIR__).'/app/Controllers/EventController.php';require_once dirname(__DIR__).'/app/Controllers/DashboardController.php';
verify_csrf();
if($path==='/'||$path==='/home'){ $events=db()->query("SELECT id,title,slug,city,start_date,cover_image FROM events WHERE status='published' AND start_date>=NOW() ORDER BY start_date LIMIT 6")->fetchAll();require dirname(__DIR__).'/views/public/home.php';}
elseif($path==='/login')AuthController::login();elseif($path==='/register')AuthController::register();elseif($path==='/logout'&&$_SERVER['REQUEST_METHOD']==='POST')AuthController::logout();elseif($path==='/events')EventController::index();elseif(preg_match('#^/events/([a-z0-9-]+)$#',$path,$m))EventController::detail($m[1]);elseif(preg_match('#^/events/(\d+)/register$#',$path,$m)&&$_SERVER['REQUEST_METHOD']==='POST')EventController::register((int)$m[1]);
elseif(preg_match('#^/(user|admin|developer)/dashboard(?:/([a-z]+))?$#',$path,$m))DashboardController::dispatch($m[1],$m[2]??'overview');
elseif($path==='/user/orders'){require_role('user');DashboardController::dispatch('user','orders');}
else{http_response_code(404);require dirname(__DIR__).'/views/errors/404.php';}
