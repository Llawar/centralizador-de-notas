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
  `turno_anio_1` enum('mañana','tarde') NOT NULL DEFAULT 'mañana',
  `turno_anio_2` enum('mañana','tarde') NOT NULL DEFAULT 'mañana',
  `turno_anio_3` enum('mañana','tarde') NOT NULL DEFAULT 'mañana',
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
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
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
  `anio_ingreso` int(11) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ci` (`ci`),
  UNIQUE KEY `matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- usuarios
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','docente','estudiante') NOT NULL,
  `referer_id` int(11) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 2. TABLAS CON DEPENDENCIA NIVEL 1
-- ============================================================

-- ------------------------------------------------------------
-- materias (→ carreras)
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
  CONSTRAINT `chk_materias_anio_carrera` CHECK (`anio_carrera` between 1 and 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- cursos (→ carreras)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cursos`;

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `paralelo` varchar(10) NOT NULL DEFAULT 'A',
  `anio_carrera` tinyint(1) NOT NULL DEFAULT 1,
  `turno` enum('mañana','tarde') NOT NULL DEFAULT 'mañana',
  `carrera_id` int(11) NOT NULL,
  `gestion` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curso_real` (`carrera_id`,`anio_carrera`,`turno`,`paralelo`,`gestion`),
  KEY `carrera_id` (`carrera_id`),
  CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. TABLAS DE RELACIÓN (N:M y dependientes)
-- ============================================================

-- ------------------------------------------------------------
-- estudiantes_cursos (inscripciones)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `estudiantes_cursos`;

CREATE TABLE `estudiantes_cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `semestre` int(11) DEFAULT 1,
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_inscripcion` (`estudiante_id`,`curso_id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `estudiantes_cursos_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estudiantes_cursos_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- docente_materia_curso (asignaciones docente-materia-curso)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `docente_materia_curso`;

CREATE TABLE `docente_materia_curso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `docente_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asignacion` (`docente_id`,`materia_id`,`curso_id`),
  KEY `materia_id` (`materia_id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `docente_materia_curso_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `docente_materia_curso_ibfk_2` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `docente_materia_curso_ibfk_3` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- parcial_periodo (estados de parciales por curso/materia/gestión)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `parcial_periodo`;

CREATE TABLE `parcial_periodo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `gestion` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `parcial` varchar(30) NOT NULL,
  `estado` enum('abierto','enviado','cerrado') NOT NULL DEFAULT 'abierto',
  `abierto_por` int(11) DEFAULT NULL,
  `enviado_por` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curso_mat_parcial` (`curso_id`,`materia_id`,`gestion`,`parcial`),
  KEY `materia_id` (`materia_id`),
  CONSTRAINT `parcial_periodo_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parcial_periodo_ibfk_2` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- notas (registro de calificaciones)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notas`;

CREATE TABLE `notas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `tipo` enum('conocer','hacer','ser','parcial','final') NOT NULL,
  `nombre_actividad` varchar(200) DEFAULT NULL,
  `nota` decimal(5,2) DEFAULT 0.00,
  `gestion` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nota` (`estudiante_id`,`curso_id`,`materia_id`,`tipo`,`nombre_actividad`),
  KEY `curso_id` (`curso_id`),
  KEY `materia_id` (`materia_id`),
  CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notas_ibfk_3` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- asistencia (registro de asistencia)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `asistencia`;

CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estudiante_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('presente','ausente','justificado') NOT NULL DEFAULT 'presente',
  `gestion` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asistencia` (`estudiante_id`,`curso_id`,`materia_id`,`fecha`),
  KEY `curso_id` (`curso_id`),
  KEY `materia_id` (`materia_id`),
  CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asistencia_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asistencia_ibfk_3` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- registro_config (configuración de planillas/formularios oficiales)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `registro_config`;

CREATE TABLE `registro_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `gestion` int(11) NOT NULL,
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
  UNIQUE KEY `unique_reg` (`curso_id`,`materia_id`,`gestion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;