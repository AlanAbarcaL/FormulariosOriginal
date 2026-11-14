<?php
require __DIR__ . '/../conexion.php';
sae_requerir_roles(['Administrador','Maestro']);
$mensaje = '';
$tipo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno = filter_input(INPUT_POST, 'alumno', FILTER_VALIDATE_INT);
    $selecciones = $_POST['materias'] ?? [];
    $profesoresSeleccionados = $_POST['profesores'] ?? [];
    if (!is_array($selecciones)) $selecciones = [];
    $pares = [];
    foreach ($selecciones as $i => $materiaId) {
        $materia = filter_var($materiaId, FILTER_VALIDATE_INT);
        $profesor = filter_var($profesoresSeleccionados[$i] ?? null, FILTER_VALIDATE_INT);
        if (!$materia && !$profesor) continue;
        if (!$materia || !$profesor) { $pares = []; break; }
        if (isset($pares[$materia])) { $pares = []; break; }
        $pares[$materia] = $profesor;
    }
    if (!$alumno || !$pares) {
        $mensaje = 'Selecciona un alumno y completa cada pareja de materia y profesor. No repitas una materia.';
    } else {
        try {
            $conexion->begin_transaction();
            $verificar = $conexion->prepare("SELECT ag.id_grupo, g.descripcion FROM alumnos a INNER JOIN asignacion_grupo ag ON ag.id_alumno=a.id_alumno INNER JOIN grupos g ON g.id_grupo=ag.id_grupo WHERE a.id_alumno=? AND a.estatus='ALTA' AND g.estatus='ACTIVO'");
            $verificar->bind_param('i', $alumno);
            $verificar->execute();
            $grupo = $verificar->get_result()->fetch_assoc();
            $verificar->close();
            if (!$grupo) throw new RuntimeException('El alumno debe estar activo y tener un grupo activo asignado antes de inscribir materias.');
            $validar = $conexion->prepare('SELECT m.id_materia FROM materias m INNER JOIN profesores p ON p.id_profesor=? WHERE m.id_materia=?');
            $insertar = $conexion->prepare('INSERT INTO asignacion_materia (id_alumno,id_materia,id_profesor) VALUES (?,?,?) ON DUPLICATE KEY UPDATE id_asignacion=id_asignacion');
            $nuevas=0; $repetidas=0;
            foreach ($pares as $materia=>$profesor) {
                $validar->bind_param('ii',$profesor,$materia);
                $validar->execute();
                if (!$validar->get_result()->fetch_assoc()) throw new RuntimeException('Una materia o profesor seleccionado no existe.');
                $insertar->bind_param('iii',$alumno,$materia,$profesor);
                $insertar->execute();
                if ($insertar->affected_rows === 1) $nuevas++; else $repetidas++;
            }
            $validar->close(); $insertar->close();
            $conexion->commit();
            $mensaje = 'Grupo conservado: '.$grupo['descripcion'].'. Materias agregadas: '.$nuevas.'. Ya inscritas: '.$repetidas.'.';
            $tipo='ok';
        } catch (Throwable $e) {
            $conexion->rollback();
            $mensaje = 'No se guardaron las inscripciones: '.$e->getMessage();
        }
    }
}
$alumnos = consultar("SELECT id_alumno, matricula, nombre, apaterno FROM alumnos WHERE estatus = 'ALTA' ORDER BY apaterno, nombre");
$materiasDisponibles = consultar('SELECT id_materia, descripcion FROM materias ORDER BY descripcion');
$profesores = consultar('SELECT id_profesor, nombre, apaterno FROM profesores ORDER BY apaterno, nombre');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SAE | Inscripción a materias</title>
<link rel="stylesheet" href="../estilos.css">
<style>
.inscripcion-panel{max-width:900px;margin:24px auto;padding:24px;background:white;border:1px solid #dce3ed;border-radius:8px}
.inscripcion-panel label{display:block;font-weight:600;margin:12px 0 6px}
.inscripcion-panel select{width:100%;padding:10px;border:1px solid #bbc7d7;border-radius:5px;background:white}
.fila-inscripcion{display:grid;grid-template-columns:1fr 1fr auto;align-items:end;gap:12px;margin:12px 0;padding:12px;background:#f5f7fa;border:1px solid #e0e6ed;border-radius:6px}
.acciones{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}
.inscripcion-panel button{padding:10px 15px;border:0;border-radius:5px;cursor:pointer;background:#276ca6;color:white}
.inscripcion-panel button.secundario{background:#e6edf5;color:#234b70}
.inscripcion-panel button.quitar{background:#eee;color:#933}
@media(max-width:650px){.fila-inscripcion{grid-template-columns:1fr}.inscripcion-panel{padding:15px}}
</style>
</head>
<body class="cesar">
<h1>Inscripción a materias</h1>
<?php require __DIR__ . '/../menu.php'; ?>
<main class="inscripcion-panel">
<h2>Inscribir materias y profesores</h2>
<p>El alumno conserva su grupo actual. Selecciona un profesor distinto para cada materia que quieras agregar.</p>
<?php if ($mensaje !== ''): ?><p role="status" style="font-weight:600;color:<?= $tipo === 'ok' ? '#17652c' : '#9c2424' ?>"><?= h($mensaje) ?></p><?php endif; ?>
<form method="post">
<label class="campo" for="alumno">Alumno</label>
<select name="alumno" id="alumno" required><option value="">Selecciona un alumno</option>
<?php foreach ($alumnos as $a): ?><option value="<?= h($a['id_alumno']) ?>"><?= h($a['matricula'] . ' - ' . $a['nombre'] . ' ' . $a['apaterno']) ?></option><?php endforeach; ?></select>
<p><small>El grupo asignado no se modifica al inscribir materias. Si el alumno no tiene grupo, asígnalo primero en Procesos → Asignar grupo.</small></p>
<h3>Materias y profesores</h3>
<p>Agrega una fila por materia. Cada fila puede tener un profesor diferente. El grupo del alumno se conserva.</p>
<div id="filas"></div>
<div class="acciones">
<button type="button" class="secundario" id="agregar">+ Agregar otra materia</button>
<button type="submit">Guardar inscripciones</button>
</div>
<p><small>Puedes regresar después y agregar más materias al mismo alumno. No se reemplazan las anteriores.</small></p>
</form>
<p><a href="../Salidas/salida_inscripciones_materias.php">Ver inscripciones registradas</a></p>
</main>
<template id="plantilla-fila">
<div class="fila-inscripcion">
<div><label>Materia</label><select name="materias[]" required><option value="">Selecciona materia</option>
<?php foreach ($materiasDisponibles as $m): ?><option value="<?= (int)$m['id_materia'] ?>"><?= h($m['descripcion']) ?></option><?php endforeach; ?>
</select></div>
<div><label>Profesor</label><select name="profesores[]" required><option value="">Selecciona profesor</option>
<?php foreach ($profesores as $p): ?><option value="<?= (int)$p['id_profesor'] ?>"><?= h($p['nombre'].' '.$p['apaterno']) ?></option><?php endforeach; ?>
</select></div>
<button type="button" class="quitar">Quitar</button>
</div>
</template>
<script>
const filas=document.getElementById('filas');
function agregarFila(){
 const fragmento=document.getElementById('plantilla-fila').content.cloneNode(true);
 fragmento.querySelector('.quitar').addEventListener('click',e=>{
  if(filas.children.length>1)e.target.closest('.fila-inscripcion').remove();
  else {e.target.closest('.fila-inscripcion').querySelectorAll('select').forEach(s=>s.value='');}
 });
 filas.appendChild(fragmento);
}
document.getElementById('agregar').addEventListener('click',agregarFila);
agregarFila();
document.querySelector('form').addEventListener('submit',e=>{
 const ids=[...document.querySelectorAll('select[name="materias[]"]')].map(s=>s.value);
 if(new Set(ids).size!==ids.length){e.preventDefault();alert('No repitas la misma materia para este alumno.');}
});
</script>
</body></html>
