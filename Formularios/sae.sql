-- BASE DE DATOS UNICA: sae_escolar
-- Incluye todas las tablas y la columna calificacion.
-- El sistema la crea automaticamente cuando MySQL esta disponible.
CREATE DATABASE IF NOT EXISTS sae_escolar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sae_escolar;

CREATE TABLE IF NOT EXISTS alumnos (
 id_alumno INT AUTO_INCREMENT PRIMARY KEY,
 matricula VARCHAR(20) NOT NULL UNIQUE,
 nombre VARCHAR(60) NOT NULL,
 apaterno VARCHAR(60) NOT NULL,
 amaterno VARCHAR(60) DEFAULT '',
 domicilio VARCHAR(150) DEFAULT '',
 correo VARCHAR(100) DEFAULT '',
 telefono VARCHAR(20) DEFAULT '',
 estatus ENUM('ALTA','BAJA') NOT NULL DEFAULT 'ALTA'
);
CREATE TABLE IF NOT EXISTS grupos (
 id_grupo INT AUTO_INCREMENT PRIMARY KEY,
 descripcion VARCHAR(50) NOT NULL UNIQUE,
 estatus ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
);
CREATE TABLE IF NOT EXISTS materias (
 id_materia INT AUTO_INCREMENT PRIMARY KEY,
 descripcion VARCHAR(100) NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS profesores (
 id_profesor INT AUTO_INCREMENT PRIMARY KEY,
 matricula VARCHAR(20) NOT NULL UNIQUE,
 numero_empleado VARCHAR(20) NOT NULL UNIQUE,
 nombre VARCHAR(60) NOT NULL,
 apaterno VARCHAR(60) NOT NULL,
 amaterno VARCHAR(60) DEFAULT ''
);
CREATE TABLE IF NOT EXISTS asignacion_grupo (
 id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
 id_grupo INT NOT NULL,
 id_alumno INT NOT NULL UNIQUE,
 id_profesor INT NOT NULL,
 FOREIGN KEY (id_grupo) REFERENCES grupos(id_grupo),
 FOREIGN KEY (id_alumno) REFERENCES alumnos(id_alumno),
 FOREIGN KEY (id_profesor) REFERENCES profesores(id_profesor)
);
CREATE TABLE IF NOT EXISTS asignacion_materia (
 id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
 id_materia INT NOT NULL,
 id_alumno INT NOT NULL,
 id_profesor INT NOT NULL,
 calificacion DECIMAL(5,2) NULL DEFAULT NULL,
 UNIQUE KEY alumno_materia (id_alumno, id_materia),
 FOREIGN KEY (id_materia) REFERENCES materias(id_materia),
 FOREIGN KEY (id_alumno) REFERENCES alumnos(id_alumno),
 FOREIGN KEY (id_profesor) REFERENCES profesores(id_profesor)
);

-- Usuarios y puestos: pertenecen a la misma base sae_escolar.
CREATE TABLE IF NOT EXISTS puesto (
 id_puesto INT AUTO_INCREMENT PRIMARY KEY,
 descripcion_puesto VARCHAR(80) NOT NULL UNIQUE,
 status_puesto ENUM('ALTA','BAJA') NOT NULL DEFAULT 'ALTA'
);
CREATE TABLE IF NOT EXISTS usuarios (
 id_user INT AUTO_INCREMENT PRIMARY KEY,
 nombreuser VARCHAR(80) NOT NULL UNIQUE,
 pwuser VARCHAR(255) NOT NULL,
 puesto INT NOT NULL,
 status_user ENUM('ALTA','BAJA') NOT NULL DEFAULT 'ALTA',
 CONSTRAINT fk_usuario_puesto FOREIGN KEY (puesto) REFERENCES puesto(id_puesto)
);
INSERT IGNORE INTO puesto (id_puesto, descripcion_puesto, status_puesto) VALUES (1,'Administrador','ALTA'),(2,'Maestro','ALTA'),(3,'Alumno','ALTA');

-- Vinculación opcional del usuario alumno con su expediente escolar.
CREATE TABLE IF NOT EXISTS usuario_alumno (
 id_user INT PRIMARY KEY, id_alumno INT NOT NULL UNIQUE,
 FOREIGN KEY (id_user) REFERENCES usuarios(id_user) ON DELETE CASCADE,
 FOREIGN KEY (id_alumno) REFERENCES alumnos(id_alumno) ON DELETE CASCADE
);
