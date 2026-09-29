-- ============================================================
-- Centralizador de Notas - Instituto Tecnologico "PACCIOLI"
-- Esquema de base de datos (sin datos)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. TABLAS RAÍZ (sin dependencias de FK)
-- ============================================================

-- ------------------------------------------------------------
-- carreras
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `carreras`;

CREATE TABLE `carreras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `duracion` int(11) NOT NULL DEFAULT 3,
  `tipo` enum('anual','semestral') NOT NULL DEFAULT 'anual',
  `estado` enum('activa','inactiva') DEFAULT 'activa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- docentes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `docentes`;

CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ci` varchar(20) NOT NULL,
  `nombre_completo` varchar(200) NOT NULL,
  `anio_ingreso` year NOT NULL DEFAULT (YEAR(CURDATE())),
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ci` (`ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- estudiantes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `estudiantes`;

CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ci` varchar(20) NOT NULL,
  `nombre_completo` varchar(200) NOT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `anio_ingreso` year NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ci` (`ci`),
  UNIQUE KEY `matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- usuarios  (admin + docente/estudiante vinculados por FK)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','docente','estudiante') NOT NULL DEFAULT 'admin',
  `docente_id` int(11) DEFAULT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uq_docente` (`docente_id`),
  UNIQUE KEY `uq_estudiante` (`estudiante_id`),
  KEY `idx_docente` (`docente_id`),
  KEY `idx_estudiante` (`estudiante_id`),
  CONSTRAINT `usuarios_ibfk_doc` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usuarios_ibfk_est` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- gestiones  (año lectivo)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `gestiones`;

CREATE TABLE `gestiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anio` year NOT NULL,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'cerrada',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_anio` (`anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 2. TABLAS DE CATÁLOGO (dependen solo de carreras)
-- ============================================================

-- ------------------------------------------------------------
-- materias  (plan de estudios por carrera y año)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `materias`;

CREATE TABLE `materias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `carrera_id` int(11) NOT NULL,
  `anio_carrera` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `carrera_id` (`carrera_id`),
  KEY `idx_carrera_anio` (`carrera_id`,`anio_carrera`),
  CONSTRAINT `materias_ibfk_1` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_materias_anio_carrera` CHECK (`anio_carrera` BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- cursos  (catálogo fijo: año del plan + turno + paralelo)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cursos`;

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anio_carrera` tinyint(1) NOT NULL DEFAULT 1,
  `turno` enum('mañana','tarde') NOT NULL DEFAULT 'mañana',
  `paralelo` varchar(10) NOT NULL DEFAULT 'A',
  `carrera_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curso_catalogo` (`carrera_id`,`anio_carrera`,`turno`,`paralelo`),
  KEY `carrera_id` (`carrera_id`),
  CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. TABLAS DE MOVIMIENTO (dependen de catálogo + gestión)
-- ============================================================

-- ------------------------------------------------------------
-- estudiantes_secciones  (inscripción real del alumno en una gestión)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `estudiantes_secciones`;

CREATE TABLE `estudiantes_secciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `gestion_id` int(11) NOT NULL,
  `semestre` tinyint(1) DEFAULT 1,
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inscripcion` (`estudiante_id`,`curso_id`,`gestion_id`),
  KEY `curso_id` (`curso_id`),
  KEY `gestion_id` (`gestion_id`),
  CONSTRAINT `estsec_ibfk_est` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estsec_ibfk_cur` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estsec_ibfk_gest` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- docente_materia_seccion  (asignación docente‑materia en una gestión)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `docente_materia_seccion`;

CREATE TABLE `docente_materia_seccion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `gestion_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asignacion` (`docente_id`,`materia_id`,`curso_id`,`gestion_id`),
  KEY `materia_id` (`materia_id`),
  KEY `curso_id` (`curso_id`),
  KEY `gestion_id` (`gestion_id`),
  CONSTRAINT `dms_ibfk_doc` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dms_ibfk_mat` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dms_ibfk_cur` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dms_ibfk_gest` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- parcial_periodo  (estado de cada parcial por materia‑curso‑gestión)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `parcial_periodo`;

CREATE TABLE `parcial_periodo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `gestion_id` int(11) NOT NULL,
  `parcial` varchar(30) NOT NULL,
  `estado` enum('abierto','enviado','cerrado') NOT NULL DEFAULT 'abierto',
  `abierto_por` int(11) DEFAULT NULL,
  `enviado_por` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pp` (`curso_id`,`materia_id`,`gestion_id`,`parcial`),
  KEY `materia_id` (`materia_id`),
  KEY `gestion_id` (`gestion_id`),
  CONSTRAINT `pp_ibfk_cur` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pp_ibfk_mat` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pp_ibfk_gest` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- notas  (calificaciones)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notas`;

CREATE TABLE `notas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `gestion_id` int(11) NOT NULL,
  `tipo` enum('conocer','hacer','ser','parcial','final') NOT NULL,
  `nombre_actividad` varchar(200) DEFAULT NULL,
  `nota` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nota` (`estudiante_id`,`curso_id`,`materia_id`,`gestion_id`,`tipo`,`nombre_actividad`),
  KEY `curso_id` (`curso_id`),
  KEY `materia_id` (`materia_id`),
  KEY `gestion_id` (`gestion_id`),
  CONSTRAINT `notas_ibfk_est` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notas_ibfk_cur` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notas_ibfk_mat` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notas_ibfk_gest` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- asistencia
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `asistencia`;

CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `gestion_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('presente','ausente','justificado') NOT NULL DEFAULT 'presente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencia` (`estudiante_id`,`curso_id`,`materia_id`,`gestion_id`,`fecha`),
  KEY `curso_id` (`curso_id`),
  KEY `materia_id` (`materia_id`),
  KEY `gestion_id` (`gestion_id`),
  CONSTRAINT `asist_ibfk_est` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asist_ibfk_cur` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asist_ibfk_mat` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asist_ibfk_gest` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- registro_config  (configuración de planillas oficiales)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `registro_config`;

CREATE TABLE `registro_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `gestion_id` int(11) NOT NULL,
  `carrera` text DEFAULT NULL,
  `asignatura` text DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `anio` varchar(20) DEFAULT NULL,
  `periodo` varchar(50) DEFAULT NULL,
  `docente` text DEFAULT NULL,
  `paralelo` varchar(20) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `logo` mediumblob DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regcfg` (`curso_id`,`materia_id`,`gestion_id`),
  KEY `materia_id` (`materia_id`),
  KEY `gestion_id` (`gestion_id`),
  CONSTRAINT `rcfg_ibfk_cur` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rcfg_ibfk_mat` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rcfg_ibfk_gest` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;