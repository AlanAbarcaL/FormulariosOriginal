<?php
// Menú desplegable original, con rutas que se adaptan a la carpeta del proyecto.
$rutaScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/Formularios/index.php');
$base = dirname($rutaScript);
foreach (['/Entradas/', '/Procesos/', '/Salidas/'] as $seccion) {
    if (strpos($rutaScript, $seccion) !== false) {
        $base = substr($rutaScript, 0, strpos($rutaScript, $seccion));
        break;
    }
}
$base = rtrim($base, '/');
$opciones = [
 'Inicio'=>['Inicio'=>'index.php'],
 'Catálogos'=>[
    'Catálogo de alumnos'=>'Entradas/crud_alumnos.php',
    'Catálogo de grupos'=>'Entradas/crud_grupos.php',
    'Catálogo de materias'=>'Entradas/crud_materias.php',
    'Catálogo de profesores'=>'Entradas/crud_profesores.php'
 ],
 'Procesos'=>[
    'Asignar grupo'=>'Procesos/proceso_asignar_grupo.php',
    'Inscripción a materias'=>'Procesos/proceso_asignar_materia.php',
    'Captura individual de calificaciones'=>'Procesos/captura_individual.php',
    'Captura masiva de calificaciones'=>'Procesos/captura_masiva.php',
    'Dar de baja alumno'=>'Procesos/proceso_baja_alumno.php'
 ],
 'Reportes'=>[
    'Alumnos por grupo'=>'Salidas/reporte_alumnos_grupo.php',
    'Materias por alumno'=>'Salidas/reporte_materias_alumno.php',
    'Calificaciones por materia y grupo'=>'Salidas/reporte_calificaciones_grupo.php',
    'Lista de alumnos'=>'Salidas/salida_alumnos.php',
    'Lista de materias'=>'Salidas/salida_materias.php',
    'Materias inscritas'=>'Salidas/salida_inscripciones_materias.php',
    'Lista de grupos'=>'Salidas/salida_grupos.php',
    'Grupos asignados'=>'Salidas/salida_grupos_asignados.php',
    'Boletas'=>'Salidas/salida_boleta.php',
    'Alumnos de baja'=>'Salidas/salida_alumnos_baja.php'
 ]
];
if (($_SESSION['puesto'] ?? '') === 'Alumno') {
    $opciones = ['Inicio'=>['Inicio'=>'index.php'], 'Mi información'=>['Mi perfil'=>'alumno_perfil.php','Mis materias'=>'alumno_materias.php','Mi boleta'=>'alumno_boleta.php']];
}
if (($_SESSION['puesto'] ?? '') === 'Administrador') {
    $opciones['Administración'] = ['Usuarios y puestos'=>'usuarios.php'];
}
$paginaActual = basename($rutaScript);
?>
<div class="contenedor-menu-visible">
  <nav class="menu-visible menu-barras" aria-label="Navegación principal">
    <?php foreach ($opciones as $grupo => $rutas): ?>
      <div class="fila-navegacion">
        <span class="titulo-fila"><?= h($grupo) ?></span>
        <div class="enlaces-fila">
          <?php foreach ($rutas as $etiqueta => $ruta): ?>
            <a class="<?= basename($ruta) === $paginaActual ? 'actual' : '' ?>" href="<?= h($base.'/'.$ruta) ?>"><?= h($etiqueta) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </nav>
</div>

<div class="auth-session" style="background:#214c77;text-align:right">Sesión: <?=h($_SESSION['nombreuser']??'')?> (<?=h($_SESSION['puesto']??'')?>) <a href="<?=h($base.'/cerrar_sesion.php')?>">Cerrar sesión</a></div>
