<?php
require __DIR__ . '/../conexion.php';
sae_requerir_roles(['Administrador','Maestro']);

$grupos=consultar("SELECT id_grupo,descripcion FROM grupos ORDER BY descripcion");
$materias=consultar("SELECT id_materia,descripcion FROM materias ORDER BY descripcion");
$grupo=filter_input(INPUT_GET,'grupo',FILTER_VALIDATE_INT);$materia=filter_input(INPUT_GET,'materia',FILTER_VALIDATE_INT);
$filas=[];$gn='';$mn='';
if($grupo && $materia){
foreach($grupos as $g){if((int)$g['id_grupo']===$grupo){$gn=$g['descripcion'];break;}}
foreach($materias as $m){if((int)$m['id_materia']===$materia){$mn=$m['descripcion'];break;}}
if($gn!=='' && $mn!==''){$st=$conexion->prepare("SELECT a.matricula,CONCAT_WS(' ',a.nombre,a.apaterno,NULLIF(a.amaterno,'')) alumno,am.calificacion,CONCAT_WS(' ',p.nombre,p.apaterno) profesor FROM asignacion_grupo ag JOIN alumnos a ON a.id_alumno=ag.id_alumno LEFT JOIN asignacion_materia am ON am.id_alumno=a.id_alumno AND am.id_materia=? LEFT JOIN profesores p ON p.id_profesor=am.id_profesor WHERE ag.id_grupo=? ORDER BY a.apaterno,a.amaterno,a.nombre");$st->bind_param('ii',$materia,$grupo);$st->execute();$filas=$st->get_result()->fetch_all(MYSQLI_ASSOC);}}

?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Lista de calificaciones por materia y grupo</title><link rel="stylesheet" href="../estilos.css"><link rel="stylesheet" href="reportes.css"></head><body class="cesar">
<h1>Lista de calificaciones por materia y grupo</h1><?php require __DIR__ . '/../menu.php'; ?>
<main class="reporte"><h2>Lista de calificaciones por materia y grupo</h2>
<form method="get" class="filtros"><label for="grupo">Grupo</label><select id="grupo" name="grupo" required><option value="">Selecciona un grupo</option><?php foreach($grupos as $g): ?><option value="<?= h($g['id_grupo']) ?>" <?= (int)$grupo===(int)$g['id_grupo']?'selected':'' ?>><?= h($g['descripcion']) ?></option><?php endforeach; ?></select><label for="materia">Materia</label><select id="materia" name="materia" required><option value="">Selecciona una materia</option><?php foreach($materias as $m): ?><option value="<?= h($m['id_materia']) ?>" <?= (int)$materia===(int)$m['id_materia']?'selected':'' ?>><?= h($m['descripcion']) ?></option><?php endforeach; ?></select><button>Consultar</button></form>
<?php if($gn!=='' && $mn!==''): ?><h3>Grupo: <?= h($gn) ?> | Materia: <?= h($mn) ?></h3><p>Alumnos del grupo: <?= count($filas) ?></p><?php if($filas): ?><div class="tabla-scroll"><table><thead><tr><th>No.</th><th>Matrícula</th><th>Alumno</th><th>Profesor</th><th>Calificación</th><th>Resultado</th></tr></thead><tbody><?php $suma=0;$total=0;foreach($filas as $n=>$f): $nota=$f['calificacion'];if($nota!==null){$suma+=(float)$nota;$total++;} ?><tr><td><?= $n+1 ?></td><td><?= h($f['matricula']) ?></td><td><?= h($f['alumno']) ?></td><td><?= h($f['profesor']??'') ?></td><td><?= $nota===null?($f['profesor']===null?'No inscrito':'Pendiente'):h($nota) ?></td><td><?= $nota===null?'Sin calificar':((float)$nota>=70?'Aprobado':'Reprobado') ?></td></tr><?php endforeach; ?></tbody></table></div><p>Calificaciones capturadas: <?= $total ?> | Promedio de calificaciones capturadas: <?= $total?number_format($suma/$total,2):'Pendiente' ?></p><p class="nota">El promedio solo incluye calificaciones capturadas. Criterio de aprobación: 70/100.</p><?php else: ?><p>No hay alumnos asignados a este grupo.</p><?php endif; ?><button type="button" class="imprimir" onclick="window.print()">Imprimir reporte</button><?php elseif($grupo || $materia): ?><p>Selecciona un grupo y una materia válidos.</p><?php endif; ?>
</main></body></html>
