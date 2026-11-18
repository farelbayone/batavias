<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$started=microtime(true);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)?:'/';$base=rtrim(dirname($_SERVER['SCRIPT_NAME']),'/');if($base && str_starts_with($path,$base))$path=substr($path,strlen($base));$path='/'.trim($path,'/');
if(str_starts_with($path,'/api/v1/')){require dirname(__DIR__).'/routes/api.php';exit;}
require dirname(__DIR__).'/routes/web.php';
