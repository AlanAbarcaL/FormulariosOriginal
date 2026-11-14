<?php
require __DIR__ . '/../conexion.php';
sae_requerir_roles(['Administrador','Maestro']);
$alumno = filter_input(INPUT_GET, 'alumno', FILTER_VALIDATE_INT);
$alumnos = consultar("SELECT id_alumno, matricula, CONCAT(nombre,' ',apaterno,' ',amaterno) nombre_completo FROM alumnos ORDER BY apaterno,nombre");
$filas = [];
$nombre = '';
if ($alumno) {
    $stmt = $conexion->prepare("SELECT a.matricula, CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) alumno, m.descripcion materia, CONCAT(p.nombre,' ',p.apaterno) profesor, am.calificacion FROM asignacion_materia am JOIN alumnos a ON a.id_alumno=am.id_alumno JOIN materias m ON m.id_materia=am.id_materia JOIN profesores p ON p.id_profesor=am.id_profesor WHERE a.id_alumno=? ORDER BY m.descripcion");
    $stmt->bind_param('i', $alumno);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if($filas) $nombre = $filas[0]['alumno'];
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Boleta de calificaciones</title><link rel="stylesheet" href="../estilos.css"></head><body class="cesar">
<h1>Salida: boleta de calificaciones</h1><?php require __DIR__.'/../menu.php'; ?><hr>
<form method="get"><label for="alumno">Selecciona alumno</label> <select name="alumno" id="alumno" required><option value="">Selecciona</option><?php foreach($alumnos as $a): ?><option value="<?= h($a['id_alumno']) ?>" <?= (int)$alumno===(int)$a['id_alumno']?'selected':'' ?>><?= h($a['matricula'].' - '.$a['nombre_completo']) ?></option><?php endforeach; ?></select> <button type="submit">Ver boleta</button></form>
<?php if($alumno): ?><?php if(!$filas): ?><p>Este alumno no tiene materias inscritas.</p><?php else: ?>
<h2>Boleta de <?= h($nombre) ?></h2><p>Matrícula: <?= h($filas[0]['matricula']) ?></p>
<table border="1" cellpadding="7"><thead><tr><th>Materia</th><th>Profesor</th><th>Calificación</th><th>Resultado</th></tr></thead><tbody>
<?php $suma=0;$cantidad=0;foreach($filas as $f): $nota=$f['calificacion'];if($nota!==null){$suma+=(float)$nota;$cantidad++;} ?>
<tr><td><?= h($f['materia']) ?></td><td><?= h($f['profesor']) ?></td><td><?= $nota===null?'Pendiente':h($nota) ?></td><td><?= $nota===null?'Sin capturar':((float)$nota>=70?'Aprobado':'Reprobado') ?></td></tr>
<?php endforeach; ?></tbody></table>
<p><strong>Promedio de materias calificadas:</strong> <?= $cantidad ? number_format($suma/$cantidad,2) : 'Pendiente' ?></p>
<p>Nota: promedio calculado únicamente con materias que ya tienen calificación. Aprobación configurada desde 70 ya que asi esta en la uni profe;</p>
<button type="button" onclick="window.print()">Imprimir boleta</button>
<?php endif; ?><?php endif; ?></body></html>
