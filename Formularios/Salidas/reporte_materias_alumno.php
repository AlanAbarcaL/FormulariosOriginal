<?php
require __DIR__ . '/../conexion.php';
sae_requerir_roles(['Administrador','Maestro']);

$alumnos=consultar("SELECT id_alumno,matricula,CONCAT_WS(' ',nombre,apaterno,NULLIF(amaterno,'')) alumno FROM alumnos ORDER BY apaterno,amaterno,nombre");
$id=filter_input(INPUT_GET,'alumno',FILTER_VALIDATE_INT);$filas=[];$elegido=null;
if($id){foreach($alumnos as $a){if((int)$a['id_alumno']===$id){$elegido=$a;break;}}
if($elegido){$st=$conexion->prepare("SELECT m.descripcion materia,CONCAT_WS(' ',p.nombre,p.apaterno,NULLIF(p.amaterno,'')) profesor,am.calificacion FROM asignacion_materia am JOIN materias m ON m.id_materia=am.id_materia JOIN profesores p ON p.id_profesor=am.id_profesor WHERE am.id_alumno=? ORDER BY m.descripcion");$st->bind_param('i',$id);$st->execute();$filas=$st->get_result()->fetch_all(MYSQLI_ASSOC);}}

?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Lista de materias por alumno</title><link rel="stylesheet" href="../estilos.css"><link rel="stylesheet" href="reportes.css"></head><body class="cesar">
<h1>Lista de materias por alumno</h1><?php require __DIR__ . '/../menu.php'; ?>
<main class="reporte"><h2>Lista de materias por alumno</h2>
<form method="get" class="filtros"><label for="alumno">Alumno</label><select id="alumno" name="alumno" required><option value="">Selecciona un alumno</option><?php foreach($alumnos as $a): ?><option value="<?= h($a['id_alumno']) ?>" <?= (int)$id===(int)$a['id_alumno']?'selected':'' ?>><?= h($a['matricula'].' - '.$a['alumno']) ?></option><?php endforeach; ?></select><button>Consultar</button></form>
<?php if($elegido): ?><h3><?= h($elegido['alumno']) ?></h3><p>Matrícula: <?= h($elegido['matricula']) ?> | Total de materias: <?= count($filas) ?></p><?php if($filas): ?><div class="tabla-scroll"><table><thead><tr><th>No.</th><th>Materia</th><th>Profesor</th><th>Calificación</th></tr></thead><tbody><?php foreach($filas as $n=>$f): ?><tr><td><?= $n+1 ?></td><td><?= h($f['materia']) ?></td><td><?= h($f['profesor']) ?></td><td><?= $f['calificacion']===null?'Pendiente':h($f['calificacion']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>El alumno no tiene materias inscritas.</p><?php endif; ?><button type="button" class="imprimir" onclick="window.print()">Imprimir reporte</button><?php elseif($id): ?><p>El alumno seleccionado no existe.</p><?php endif; ?>
</main></body></html>
