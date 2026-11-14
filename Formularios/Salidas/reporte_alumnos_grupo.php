<?php
require __DIR__ . '/../conexion.php';
sae_requerir_roles(['Administrador','Maestro']);

$grupos=consultar("SELECT id_grupo,descripcion FROM grupos ORDER BY descripcion");
$id=filter_input(INPUT_GET,'grupo',FILTER_VALIDATE_INT);
$filas=[];$grupoNombre='';
if($id){
 $st=$conexion->prepare("SELECT g.descripcion grupo,a.matricula,CONCAT_WS(' ',a.nombre,a.apaterno,NULLIF(a.amaterno,'')) alumno,a.estatus,CONCAT_WS(' ',pr.nombre,pr.apaterno) profesor FROM asignacion_grupo ag JOIN grupos g ON g.id_grupo=ag.id_grupo JOIN alumnos a ON a.id_alumno=ag.id_alumno JOIN profesores pr ON pr.id_profesor=ag.id_profesor WHERE ag.id_grupo=? ORDER BY a.apaterno,a.amaterno,a.nombre");
 $st->bind_param('i',$id);$st->execute();$filas=$st->get_result()->fetch_all(MYSQLI_ASSOC);
 foreach($grupos as $g){if((int)$g['id_grupo']===$id){$grupoNombre=$g['descripcion'];break;}}
}

?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Lista de alumnos por grupo</title><link rel="stylesheet" href="../estilos.css"><link rel="stylesheet" href="reportes.css"></head><body class="cesar">
<h1>Lista de alumnos por grupo</h1><?php require __DIR__ . '/../menu.php'; ?>
<main class="reporte"><h2>Lista de alumnos por grupo</h2>
<form method="get" class="filtros"><label for="grupo">Grupo</label><select id="grupo" name="grupo" required><option value="">Selecciona un grupo</option><?php foreach($grupos as $g): ?><option value="<?= h($g['id_grupo']) ?>" <?= (int)$id===(int)$g['id_grupo']?'selected':'' ?>><?= h($g['descripcion']) ?></option><?php endforeach; ?></select><button>Consultar</button></form>
<?php if($id && $grupoNombre!==''): ?><h3>Grupo: <?= h($grupoNombre) ?></h3><p>Total de alumnos: <?= count($filas) ?></p><?php if($filas): ?><div class="tabla-scroll"><table><thead><tr><th>No.</th><th>Matrícula</th><th>Alumno</th><th>Profesor asignado</th><th>Estatus</th></tr></thead><tbody><?php foreach($filas as $n=>$f): ?><tr><td><?= $n+1 ?></td><td><?= h($f['matricula']) ?></td><td><?= h($f['alumno']) ?></td><td><?= h($f['profesor']) ?></td><td><?= h($f['estatus']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Este grupo todavía no tiene alumnos asignados.</p><?php endif; ?><button type="button" class="imprimir" onclick="window.print()">Imprimir reporte</button><?php elseif($id): ?><p>El grupo seleccionado no existe.</p><?php endif; ?>
</main></body></html>
