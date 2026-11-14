<?php
const SAE_AUTH_PUBLIC = true;
require __DIR__.'/conexion.php';
if (!empty($_SESSION['id_user'])) { header('Location: index.php'); exit; }
$total = (int)$conexion->query('SELECT COUNT(*) FROM usuarios')->fetch_row()[0];
if ($total === 0) { header('Location: instalar_admin.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nombre=trim($_POST['usuario']??''); $clave=$_POST['clave']??''; $rol=$_POST['rol']??'';
    if (!in_array($rol, ['Alumno','Maestro','Administrador'], true)) { $error='Selecciona tu tipo de usuario.'; }
    $q=$conexion->prepare("SELECT u.id_user,u.pwuser,u.nombreuser,p.descripcion_puesto FROM usuarios u JOIN puesto p ON p.id_puesto=u.puesto WHERE u.nombreuser=? AND p.descripcion_puesto=? AND u.status_user='ALTA' AND p.status_puesto='ALTA'");
    $q->bind_param('ss',$nombre,$rol);$q->execute();$user=$q->get_result()->fetch_assoc();
    if ($rol !== '' && $user && password_verify($clave,$user['pwuser'])) {
        session_regenerate_id(true);
        $_SESSION['id_user']=(int)$user['id_user'];
        $_SESSION['nombreuser']=$user['nombreuser'];
        $_SESSION['puesto']=$user['descripcion_puesto'];
        header('Location: index.php');exit;
    }
    $error='Revisa el tipo de usuario, el nombre y la contraseña, o verifica que la cuenta esté activa.';
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso · SAE</title><link rel="stylesheet" href="auth.css"></head><body><main class="auth-card"><h1>Sistema Escolar</h1><p>Iniciar sesión</p><?php if($error): ?><p class="error"><?=h($error)?></p><?php endif; ?><form method="post"><label>Tipo de usuario<select name="rol" required><option value="">Selecciona una opción</option><?php foreach (['Alumno','Maestro','Administrador'] as $opcion): ?><option value="<?=h($opcion)?>" <?=($_POST['rol']??'')===$opcion?'selected':''?>><?=h($opcion)?></option><?php endforeach; ?></select></label><label>Usuario<input name="usuario" required autocomplete="username"></label><label>Contraseña<input name="clave" type="password" required autocomplete="current-password"></label><button>Ingresar</button></form></main></body></html>
