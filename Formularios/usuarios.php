<?php require __DIR__.'/conexion.php';
if(($_SESSION['puesto']??'')!=='Administrador'){http_response_code(403);exit('Acceso exclusivo para administradores.');}
$mensaje='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(403);exit('Solicitud no válida.');}
 $accion=$_POST['accion']??'';
 try{
 if($accion==='puesto'){
  $nombre=trim($_POST['descripcion']??'');if($nombre==='')throw new Exception('Escribe el nombre del puesto.');
  $q=$conexion->prepare("INSERT INTO puesto(descripcion_puesto,status_puesto) VALUES (?,'ALTA')");$q->bind_param('s',$nombre);$q->execute();$mensaje='Puesto creado.';
 }elseif($accion==='usuario'){
  $nombre=trim($_POST['nombre']??'');$clave=$_POST['clave']??'';$puesto=(int)($_POST['puesto']??0);$alumnoId=(int)($_POST['id_alumno']??0);
  if(strlen($nombre)<3||strlen($clave)<10||$puesto<1)throw new Exception('Verifica usuario, puesto y contraseña de 10 caracteres mínimo.');
  $roleQ=$conexion->prepare('SELECT descripcion_puesto FROM puesto WHERE id_puesto=? AND status_puesto="ALTA"');$roleQ->bind_param('i',$puesto);$roleQ->execute();$rol=$roleQ->get_result()->fetch_assoc()['descripcion_puesto']??'';
  if(!in_array($rol,['Administrador','Maestro','Alumno'],true))throw new Exception('Selecciona un puesto válido.');
  if($rol==='Alumno' && $alumnoId<1)throw new Exception('Para un usuario Alumno, selecciona su matrícula.');
  $hash=password_hash($clave,PASSWORD_DEFAULT);
  $conexion->begin_transaction();
  $q=$conexion->prepare("INSERT INTO usuarios(nombreuser,pwuser,puesto,status_user) VALUES (?, ?, ?, 'ALTA')");$q->bind_param('ssi',$nombre,$hash,$puesto);$q->execute();
  if($rol==='Alumno'){
    $newId=$conexion->insert_id;
    $check=$conexion->prepare('SELECT id_alumno FROM alumnos WHERE id_alumno=? AND estatus="ALTA"');$check->bind_param('i',$alumnoId);$check->execute();
    if(!$check->get_result()->fetch_assoc())throw new Exception('El alumno no existe o está dado de baja.');
    $link=$conexion->prepare('INSERT INTO usuario_alumno(id_user,id_alumno) VALUES (?,?)');$link->bind_param('ii',$newId,$alumnoId);$link->execute();
  }
  $conexion->commit();$mensaje='Usuario creado.';
 }elseif($accion==='estado_usuario'){
  $id=(int)($_POST['id']??0);$estado=$_POST['estado']??'';
  if(!in_array($estado,['ALTA','BAJA'],true)||$id===(int)$_SESSION['id_user'])throw new Exception('No puedes cambiar tu propio estado.');
  $q=$conexion->prepare('UPDATE usuarios SET status_user=? WHERE id_user=?');$q->bind_param('si',$estado,$id);$q->execute();$mensaje='Estado actualizado.';
 }elseif($accion==='estado_puesto'){
  $id=(int)($_POST['id']??0);$estado=$_POST['estado']??'';
  if(!in_array($estado,['ALTA','BAJA'],true)||$id===1)throw new Exception('No se permite desactivar el puesto Administrador.');
  $q=$conexion->prepare('UPDATE puesto SET status_puesto=? WHERE id_puesto=?');$q->bind_param('si',$estado,$id);$q->execute();$mensaje='Estado del puesto actualizado.';
 }
 }catch(Throwable $e){if($conexion->errno===0){ /* la transacción puede haberse iniciado */ } try{$conexion->rollback();}catch(Throwable $ignored){} $error='No se pudo realizar la operación: '.$e->getMessage();}
}
$_SESSION['csrf']??=bin2hex(random_bytes(32));
$puestos=consultar('SELECT * FROM puesto ORDER BY id_puesto');
$alumnosDisponibles=consultar('SELECT a.id_alumno,a.matricula,a.nombre,a.apaterno FROM alumnos a LEFT JOIN usuario_alumno ua ON ua.id_alumno=a.id_alumno WHERE ua.id_user IS NULL AND a.estatus=\'ALTA\' ORDER BY a.apaterno,a.nombre');
$usuarios=consultar('SELECT u.id_user,u.nombreuser,u.status_user,u.puesto,p.descripcion_puesto FROM usuarios u JOIN puesto p ON p.id_puesto=u.puesto ORDER BY u.id_user');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuarios y puestos</title><link rel="stylesheet" href="estilos.css"><link rel="stylesheet" href="auth.css"></head><body class="cesar sistema-moderno"><header class="encabezado-sistema"><h1>Administración de usuarios</h1></header><?php require __DIR__.'/menu.php'; ?><main class="auth-main"><h2>Usuarios y puestos</h2><?php if($mensaje):?><p class="ok"><?=h($mensaje)?></p><?php endif;?><?php if($error):?><p class="error"><?=h($error)?></p><?php endif;?><div class="auth-grid"><section class="auth-panel"><h3>Nuevo puesto</h3><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="accion" value="puesto"><label>Descripción<input name="descripcion" required maxlength="80"></label><button>Guardar puesto</button></form></section><section class="auth-panel"><h3>Nuevo usuario</h3><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="accion" value="usuario"><label>Nombre de usuario<input name="nombre" required minlength="3"></label><label>Contraseña<input type="password" name="clave" required minlength="10"></label><label>Puesto<select name="puesto" required><?php foreach($puestos as $p):if($p['status_puesto']!=='ALTA')continue;?><option value="<?=h($p['id_puesto'])?>"><?=h($p['descripcion_puesto'])?></option><?php endforeach;?></select></label><label>Alumno vinculado (solo si el puesto es Alumno)<select name="id_alumno"><option value="">No aplica</option><?php foreach($alumnosDisponibles as $a): ?><option value="<?=h($a['id_alumno'])?>"><?=h($a['matricula'].' - '.$a['nombre'].' '.$a['apaterno'])?></option><?php endforeach; ?></select></label><p>Para las cuentas de Alumno, selecciona su matrícula. Así solo podrá consultar sus propios datos.</p><button>Guardar usuario</button></form></section></div><section class="auth-panel"><h3>Usuarios registrados</h3><div class="auth-table"><table><tr><th>ID</th><th>Usuario</th><th>Puesto</th><th>Estado</th><th>Acciones</th></tr><?php foreach($usuarios as $u):?><tr><td><?=h($u['id_user'])?></td><td><?=h($u['nombreuser'])?></td><td><?=h($u['descripcion_puesto'])?></td><td><?=h($u['status_user'])?></td><td><?php if((int)$u['id_user']!==(int)$_SESSION['id_user']):?><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="accion" value="estado_usuario"><input type="hidden" name="id" value="<?=h($u['id_user'])?>"><input type="hidden" name="estado" value="<?=$u['status_user']==='ALTA'?'BAJA':'ALTA'?>"><button><?=$u['status_user']==='ALTA'?'Dar de baja':'Dar de alta'?></button></form><?php endif;?></td></tr><?php endforeach;?></table></div></section><section class="auth-panel"><h3>Puestos registrados</h3><div class="auth-table"><table><tr><th>ID</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr><?php foreach($puestos as $p):?><tr><td><?=h($p['id_puesto'])?></td><td><?=h($p['descripcion_puesto'])?></td><td><?=h($p['status_puesto'])?></td><td><?php if((int)$p['id_puesto']!==1):?><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="accion" value="estado_puesto"><input type="hidden" name="id" value="<?=h($p['id_puesto'])?>"><input type="hidden" name="estado" value="<?=$p['status_puesto']==='ALTA'?'BAJA':'ALTA'?>"><button><?=$p['status_puesto']==='ALTA'?'Dar de baja':'Dar de alta'?></button></form><?php endif;?></td></tr><?php endforeach;?></table></div></section></main></body></html>
