<?php
const SAE_AUTH_PUBLIC = true;
require __DIR__.'/conexion.php';
$total=(int)$conexion->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0];
if ($total>0) { header('Location: login.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $nombre=trim($_POST['usuario']??'');$clave=$_POST['clave']??'';
  if(strlen($nombre)<3 || strlen($clave)<10) $error='Usa al menos 3 caracteres en usuario y 10 en contraseña.';
  else {
    $hash=password_hash($clave,PASSWORD_DEFAULT);
    $q=$conexion->prepare("INSERT INTO usuarios(nombreuser,pwuser,puesto,status_user) VALUES (?, ?, 1, 'ALTA')");
    $q->bind_param('ss',$nombre,$hash);
    if($q->execute()){header('Location: login.php');exit;}
    $error='No se pudo crear el usuario. Revisa si ya existe.';
  }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Primer acceso</title><link rel="stylesheet" href="auth.css"></head><body><main class="auth-card"><h1>Configuración inicial</h1><p>Crea el primer administrador. Esta pantalla se desactiva cuando existe un usuario.</p><?php if($error):?><p class="error"><?=h($error)?></p><?php endif;?><form method="post"><label>Usuario administrador<input name="usuario" required minlength="3"></label><label>Contraseña segura<input name="clave" type="password" required minlength="10"></label><button>Crear administrador</button></form></main></body></html>
