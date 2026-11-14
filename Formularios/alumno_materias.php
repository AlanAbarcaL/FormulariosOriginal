<?php
require __DIR__.'/conexion.php';
$id=sae_id_alumno_actual();
$alumno=null;
if ($id) {
 $q=$conexion->prepare('SELECT id_alumno,matricula,nombre,apaterno,amaterno,domicilio,correo,telefono,estatus FROM alumnos WHERE id_alumno=?');
 $q->bind_param('i',$id);$q->execute();$alumno=$q->get_result()->fetch_assoc();
}

$filas=[];
if($alumno){
 $q=$conexion->prepare('SELECT m.descripcion materia, CONCAT_WS(" ",p.nombre,p.apaterno) profesor, am.calificacion FROM asignacion_materia am JOIN materias m ON m.id_materia=am.id_materia JOIN profesores p ON p.id_profesor=am.id_profesor WHERE am.id_alumno=? ORDER BY m.descripcion');
 $q->bind_param('i',$id);$q->execute();$filas=$q->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portal del alumno</title><link rel="stylesheet" href="estilos.css"></head><body class="cesar"><main><h1>Portal del alumno</h1><?php require __DIR__.'/menu.php'; ?><?php if(!$alumno): ?><section class="alan"><h2>Expediente no vinculado</h2><p>Tu cuenta de alumno todavía no está asociada a una matrícula. Solicita al administrador que la vincule en Usuarios y puestos.</p></section><?php else: ?><section class="alan"><h2>Mis materias</h2><p><strong>Alumno:</strong> <?=h($alumno["nombre"]." ".$alumno["apaterno"])?></p><p><strong>Matrícula:</strong> <?=h($alumno["matricula"])?></p><div class="tabla-scroll"><table><thead><tr><th>Materia</th><th>Profesor</th><th>Calificación</th></tr></thead><tbody><?php foreach($filas as $f): ?><tr><td><?=h($f["materia"])?></td><td><?=h($f["profesor"])?></td><td><?= $f["calificacion"]===null?"Pendiente":h($f["calificacion"]) ?></td></tr><?php endforeach; ?></tbody></table></div><?php if(!$filas): ?><p>No tienes materias registradas.</p><?php endif; ?></section><?php endif; ?></main></body></html>