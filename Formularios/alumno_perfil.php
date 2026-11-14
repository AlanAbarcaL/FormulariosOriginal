<?php
require __DIR__.'/conexion.php';
$id=sae_id_alumno_actual();
$alumno=null;
if ($id) {
 $q=$conexion->prepare('SELECT id_alumno,matricula,nombre,apaterno,amaterno,domicilio,correo,telefono,estatus FROM alumnos WHERE id_alumno=?');
 $q->bind_param('i',$id);$q->execute();$alumno=$q->get_result()->fetch_assoc();
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portal del alumno</title><link rel="stylesheet" href="estilos.css"></head><body class="cesar"><main><h1>Portal del alumno</h1><?php require __DIR__.'/menu.php'; ?><?php if(!$alumno): ?><section class="alan"><h2>Expediente no vinculado</h2><p>Tu cuenta de alumno todavía no está asociada a una matrícula. Solicita al administrador que la vincule en Usuarios y puestos.</p></section><?php else: ?><section class="alan"><h2>Mi información</h2><p><strong>Matrícula:</strong> <?=h($alumno['matricula'])?></p><p><strong>Nombre:</strong> <?=h($alumno['nombre'].' '.$alumno['apaterno'].' '.$alumno['amaterno'])?></p><p><strong>Correo:</strong> <?=h($alumno['correo'])?></p><p><strong>Teléfono:</strong> <?=h($alumno['telefono'])?></p><p><strong>Estado:</strong> <?=h($alumno['estatus'])?></p><p>Para corregir tus datos, comunícate con administración.</p></section><?php endif; ?></main></body></html>