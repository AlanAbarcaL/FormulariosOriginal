<?php
// Una sola base de datos: sae_escolar. Se inicializa automáticamente al entrar.
// En producción usa credenciales propias y desactiva la creación automática.
mysqli_report(MYSQLI_REPORT_OFF);
$host = getenv('SAE_DB_HOST') ?: 'localhost';
$usuario = getenv('SAE_DB_USER') ?: 'root';
$password = getenv('SAE_DB_PASSWORD') ?: '';
$nombre_bd = 'sae_escolar';
$conexion = @new mysqli($host, $usuario, $password);

function sae_error_pagina(string $detalle): void {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    $detalle = htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sistema Escolar</title><style>body{font:16px system-ui;background:#f3f6fb;color:#172b49;margin:0;padding:7vh 18px}.c{max-width:700px;margin:auto;background:white;padding:30px;border-radius:14px;box-shadow:0 4px 22px #0001}a{display:inline-block;padding:12px 17px;background:#1765b4;color:white;border-radius:8px;text-decoration:none}small{color:#666}</style></head><body><div class="c"><h1>Sistema Escolar</h1><p>El sistema se puede abrir, pero la base de datos todavía no está disponible.</p><p><strong>Para trabajar sin MySQL:</strong> abre la demostración, registra datos y consulta los cuatro reportes. Se guardan en este navegador.</p><p><a href="'.(str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/Entradas/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/Procesos/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/Salidas/') ? '../demo.html' : 'demo.html').'">Abrir sistema de demostración</a></p><p><strong>Para trabajar con MySQL:</strong> inicia MySQL desde XAMPP y vuelve a cargar esta página. El sistema creará automáticamente la base <code>sae_escolar</code> y sus tablas si tu usuario tiene permisos.</p><small>Detalle: '.$detalle.'</small></div></body></html>';
    exit;
}
if ($conexion->connect_errno) sae_error_pagina('No se pudo conectar a MySQL. Comprueba que esté iniciado en XAMPP.');
if (!$conexion->query("CREATE DATABASE IF NOT EXISTS `sae_escolar` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    sae_error_pagina('No hay permiso para crear la base de datos: '.$conexion->error);
}
if (!$conexion->select_db($nombre_bd)) sae_error_pagina('No se pudo seleccionar sae_escolar.');
$conexion->set_charset('utf8mb4');
// El SQL es un archivo local fijo del proyecto, nunca una entrada del usuario.
// CREATE TABLE IF NOT EXISTS no borra registros ya existentes.
$schema = file_get_contents(__DIR__.'/sae.sql');
if ($schema === false) sae_error_pagina('Falta el archivo sae.sql del proyecto.');
$schema = preg_replace('/^\s*--[^\r\n]*/m', '', $schema);
foreach (explode(';', $schema) as $sentencia) {
    $sentencia = trim($sentencia);
    if ($sentencia === '') continue;
    if (!$conexion->query($sentencia)) sae_error_pagina('Error al preparar las tablas: '.$conexion->error);
}
// Compatibilidad con instalaciones antiguas que aún no tienen calificacion.
$columna = $conexion->query("SELECT COUNT(*) n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='sae_escolar' AND TABLE_NAME='asignacion_materia' AND COLUMN_NAME='calificacion'");
if (!$columna) sae_error_pagina('No se pudo comprobar el esquema: '.$conexion->error);
if ((int)$columna->fetch_assoc()['n'] === 0) {
    if (!$conexion->query('ALTER TABLE `asignacion_materia` ADD COLUMN `calificacion` DECIMAL(5,2) NULL DEFAULT NULL')) sae_error_pagina('No se pudo actualizar la tabla de calificaciones: '.$conexion->error);
}
function h($dato): string { return htmlspecialchars((string)($dato ?? ''), ENT_QUOTES, 'UTF-8'); }
function consultar(string $sql): array {
    global $conexion;
    $resultado = $conexion->query($sql);
    if (!$resultado) throw new RuntimeException('Error al consultar los datos: '.$conexion->error);
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

// Protección de páginas PHP mediante sesiones del servidor.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['use_strict_mode'=>true, 'cookie_httponly'=>true, 'cookie_samesite'=>'Lax']);
}
if (!defined('SAE_AUTH_PUBLIC')) {
    if (empty($_SESSION['id_user'])) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $pos = strpos($script, '/Formularios/');
        $loginUrl = $pos !== false ? substr($script, 0, $pos).'/Formularios/login.php' : '/Formularios/login.php';
        header('Location: '.$loginUrl);
        exit;
    }
    $q = $conexion->prepare("SELECT u.nombreuser, p.descripcion_puesto FROM usuarios u JOIN puesto p ON p.id_puesto=u.puesto WHERE u.id_user=? AND u.status_user='ALTA' AND p.status_puesto='ALTA'");
    $q->bind_param('i', $_SESSION['id_user']);
    $q->execute();
    $activo = $q->get_result()->fetch_assoc();
    if (!$activo) {
        $_SESSION = [];
        session_destroy();
        header('Location: /Formularios/login.php');
        exit;
    }
    $_SESSION['nombreuser'] = $activo['nombreuser'];
    $_SESSION['puesto'] = $activo['descripcion_puesto'];
}

// Actualización no destructiva de los nombres de roles de versiones anteriores.
$conexion->query("UPDATE puesto SET descripcion_puesto='Maestro' WHERE descripcion_puesto='Capturista' AND NOT EXISTS (SELECT 1 FROM (SELECT id_puesto FROM puesto WHERE descripcion_puesto='Maestro') x)");
$conexion->query("UPDATE puesto SET descripcion_puesto='Alumno' WHERE descripcion_puesto='Consulta' AND NOT EXISTS (SELECT 1 FROM (SELECT id_puesto FROM puesto WHERE descripcion_puesto='Alumno') x)");
// Autorización del lado del servidor: nunca depender solo de enlaces ocultos.
function sae_requerir_roles(array $roles): void {
    if (!in_array($_SESSION['puesto'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso denegado</title><body style="font:16px Arial;padding:35px"><h2>Acceso denegado</h2><p>Tu cuenta no tiene permisos para esta sección.</p><a href="/Formularios/index.php">Volver al inicio</a></body></html>');
    }
}
function sae_id_alumno_actual(): int {
    global $conexion;
    sae_requerir_roles(['Alumno']);
    $stmt=$conexion->prepare('SELECT id_alumno FROM usuario_alumno WHERE id_user=?');
    $stmt->bind_param('i',$_SESSION['id_user']); $stmt->execute();
    return (int)($stmt->get_result()->fetch_assoc()['id_alumno'] ?? 0);
}
