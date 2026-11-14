<?php
require __DIR__ . '/../conexion.php';
sae_requerir_roles(['Administrador','Maestro']);
$mensaje = '';
$ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'inscripcion', FILTER_VALIDATE_INT);
    $texto = trim((string)($_POST['calificacion'] ?? ''));
    if (!$id || $texto === '' || !is_numeric($texto) || (float)$texto < 0 || (float)$texto > 100) {
        $mensaje = 'Selecciona una inscripción y escribe una calificación entre 0 y 100.';
    } else {
        $calificacion = (float)$texto;
        $stmt = $conexion->prepare("UPDATE asignacion_materia am JOIN alumnos a ON a.id_alumno=am.id_alumno SET am.calificacion=? WHERE am.id_asignacion=? AND a.estatus='ALTA'");
        $stmt->bind_param('di', $calificacion, $id);
        $stmt->execute();
        // affected_rows=0 también puede significar que el valor ya era igual.
        $verificar = $conexion->prepare("SELECT am.id_asignacion FROM asignacion_materia am JOIN alumnos a ON a.id_alumno=am.id_alumno WHERE am.id_asignacion=? AND a.estatus='ALTA'");
        $verificar->bind_param('i', $id);
        $verificar->execute();
        $ok = $verificar->get_result()->num_rows === 1;
        $mensaje = $ok ? 'Calificación guardada correctamente.' : 'No se encontró una inscripción válida para un alumno de alta.';
    }
}
$inscripciones = consultar("SELECT am.id_asignacion, a.matricula, CONCAT(a.nombre,' ',a.apaterno) alumno, m.descripcion materia, am.calificacion FROM asignacion_materia am JOIN alumnos a ON a.id_alumno=am.id_alumno JOIN materias m ON m.id_materia=am.id_materia WHERE a.estatus='ALTA' ORDER BY a.apaterno,a.nombre,m.descripcion");
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Captura individual</title><link rel="stylesheet" href="../estilos.css"></head><body class="cesar">
<h1>Proceso: captura individual de calificaciones</h1><?php require __DIR__.'/../menu.php'; ?><hr>
<p>Primero inscribe al alumno en una materia. Después podrás capturar o cambiar su calificación (0 a 100).</p>
<?php if($mensaje): ?><p style="color:<?= $ok ? 'green' : 'darkred' ?>"><?= h($mensaje) ?></p><?php endif; ?>
<form method="post"><label for="inscripcion">Alumno y materia inscritos</label><br>
<select id="inscripcion" name="inscripcion" required><option value="">Selecciona una inscripción</option>
<?php foreach($inscripciones as $i): ?><option value="<?= h($i['id_asignacion']) ?>"><?= h($i['matricula'].' - '.$i['alumno'].' | '.$i['materia'].' | Actual: '.($i['calificacion'] === null ? 'Pendiente' : $i['calificacion'])) ?></option><?php endforeach; ?></select><br><br>
<label for="calificacion">Calificación</label><input id="calificacion" type="number" name="calificacion" min="0" max="100" step="0.01" required><br><br><button type="submit">Guardar calificación</button></form>
<p><a href="../Salidas/salida_boleta.php">Consultar boletas</a></p></body></html>
