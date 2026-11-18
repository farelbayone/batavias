<?php
final class AuthController {
 public static function login(): void {
  if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$email=filter_var(trim($_POST['email']??''),FILTER_VALIDATE_EMAIL);$pass=$_POST['password']??'';
   $q=db()->prepare('SELECT id,name,email,password,role,status FROM users WHERE email=? LIMIT 1');$q->execute([$email?:'']);$u=$q->fetch();
   if($u && $u['status']==='active' && password_verify($pass,$u['password'])){session_regenerate_id(true);unset($u['password']);$_SESSION['user']=$u;log_event('auth','User login',$u['id']);redirect(match($u['role']){'admin'=>'admin','developer'=>'developer',default=>'user'}.'/dashboard');}
   flash('error','Email atau password salah, atau akun tidak aktif.');redirect('login');
  }
  require dirname(__DIR__,2).'/views/auth/login.php';
 }
 public static function register(): void {
  if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$name=trim($_POST['name']??'');$email=filter_var(trim($_POST['email']??''),FILTER_VALIDATE_EMAIL);$pass=$_POST['password']??'';
   if(strlen($name)<2||!$email||strlen($pass)<8){flash('error','Nama, email valid, dan password minimal 8 karakter wajib diisi.');redirect('register');}
   try{$s=db()->prepare("INSERT INTO users(name,email,password,role,status,created_at) VALUES(?,?,?,'user','active',NOW())");$s->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);flash('success','Akun berhasil dibuat. Silakan masuk.');redirect('login');}catch(PDOException $e){flash('error','Email sudah terdaftar atau data tidak valid.');redirect('register');}
  } require dirname(__DIR__,2).'/views/auth/register.php';
 }
 public static function logout(): void { verify_csrf();$id=user()['id']??null;$_SESSION=[];session_regenerate_id(true);if($id)log_event('auth','User logout',(int)$id);redirect('login'); }
}
