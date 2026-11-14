<?php
require __DIR__ . '/../conexion.php';
sae_requerir_roles(['Administrador','Maestro']);
$materia = filter_input(INPUT_GET, 'materia', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'materia', FILTER_VALIDATE_INT);
$mensaje = '';
$ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $materia) {
    $datos = $_POST['calificaciones'] ?? [];
    if (!is_array($datos)) $datos = [];
    $valores = [];
    foreach ($datos as $id => $nota) {
        if (!ctype_digit((string)$id)) { $mensaje='Identificador de inscripción inválido.'; break; }
        $nota = trim((string)$nota);
        if ($nota === '') continue; // vacío = conservar calificación anterior
        if (!is_numeric($nota) || (float)$nota < 0 || (float)$nota > 100) { $mensaje='Todas las calificaciones deben estar entre 0 y 100.'; break; }
        $valores[(int)$id] = (float)$nota;
    }
    if ($mensaje === '') {
        if (!$valores) $mensaje = 'Escribe al menos una calificación.';
        else {
            try {
                $conexion->begin_transaction();
                $verificar = $conexion->prepare("SELECT am.id_asignacion FROM asignacion_materia am JOIN alumnos a ON a.id_alumno=am.id_alumno WHERE am.id_asignacion=? AND am.id_materia=? AND a.estatus='ALTA'");
                $guardar = $conexion->prepare("UPDATE asignacion_materia SET calificacion=? WHERE id_asignacion=? AND id_materia=?");
                foreach ($valores as $id => $nota) {
                    $verificar->bind_param('ii', $id, $materia);
                    $verificar->execute();
                    if ($verificar->get_result()->num_rows !== 1) throw new Exception('Hay una inscripción inválida. No se guardó ninguna calificación.');
                    $guardar->bind_param('dii', $nota, $id, $materia);
                    $guardar->execute();
                }
                $conexion->commit();
                $mensaje = 'Se guardaron '.count($valores).' calificaciones correctamente.';
                $ok = true;
            } catch (Throwable $e) {
                $conexion->rollback();
                $mensaje = 'No se guardaron los cambios. '.$e->getMessage();
            }
        }
    }
}
$materias = consultar('SELECT id_materia, descripcion FROM materias ORDER BY descripcion');
$alumnos = [];
if ($materia) {
    $stmt = $conexion->prepare("SELECT am.id_asignacion, a.matricula, CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) alumno, am.calificacion FROM asignacion_materia am JOIN alumnos a ON a.id_alumno=am.id_alumno WHERE am.id_materia=? AND a.estatus='ALTA' ORDER BY a.apaterno,a.nombre");
    $stmt->bind_param('i', $materia);
    $stmt->execute();
    $alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Captura masiva</title><link rel="stylesheet" href="../estilos.css"></head><body class="cesar">
<h1>Proceso: captura masiva de calificaciones</h1><?php require __DIR__.'/../menu.php'; ?><hr>
<p>Selecciona una materia y captura las calificaciones de varios alumnos. Deja un campo vacío para conservar su calificación anterior.</p>
<form method="get"><label for="materia">Materia</label> <select name="materia" id="materia" required><option value="">Selecciona materia</option><?php foreach($materias as $m): ?><option value="<?= h($m['id_materia']) ?>" <?= (int)$materia===(int)$m['id_materia']?'selected':'' ?>><?= h($m['descripcion']) ?></option><?php endforeach; ?></select> <button type="submit">Mostrar alumnos</button></form>
<?php if($mensaje): ?><p style="color:<?= $ok?'green':'darkred' ?>"><?= h($mensaje) ?></p><?php endif; ?>
<?php if($materia): ?>
<?php if(!$alumnos): ?><p>No hay alumnos inscritos en esta materia. Primero inscríbelos en Procesos.</p>
<?php else: ?><form method="post"><input type="hidden" name="materia" value="<?= h($materia) ?>"><table border="1" cellpadding="7"><thead><tr><th>Matrícula</th><th>Alumno</th><th>Calificación actual</th><th>Nueva calificación</th></tr></thead><tbody>
<?php foreach($alumnos as $a): ?><tr><td><?= h($a['matricula']) ?></td><td><?= h($a['alumno']) ?></td><td><?= $a['calificacion']===null?'Pendiente':h($a['calificacion']) ?></td><td><input type="number" min="0" max="100" step="0.01" name="calificaciones[<?= (int)$a['id_asignacion'] ?>]" placeholder="0 a 100" style="width:110px"></td></tr><?php endforeach; ?>
</tbody></table><br><button type="submit">Guardar calificaciones</button></form><?php endif; ?>
<?php endif; ?><p><a href="../Salidas/salida_boleta.php">Consultar boletas</a></p></body></html>
