-- ============================================================
-- Migración: separar catálogo de cursos de la instancia lectiva
-- Crea tabla `gestiones` y `secciones`, limpia `cursos`,
-- y re‑apunta las FK de las tablas dependientes a `seccion_id`.
-- Idempotente: se puede ejecutar varias veces.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1️⃣  Tabla GESTIONES (año lectivo)
CREATE TABLE IF NOT EXISTS `gestiones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `anio` YEAR NOT NULL UNIQUE,
  `estado` ENUM('abierta','cerrada') NOT NULL DEFAULT 'cerrada',
  `fecha_inicio` DATE NULL,
  `fecha_fin` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2️⃣  Tabla SECCIONES (instancia real: curso + gestión + turno + paralelo)
CREATE TABLE IF NOT EXISTS `secciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `curso_id` INT NOT NULL,
  `gestion_id` INT NOT NULL,
  `turno` ENUM('mañana','tarde') NOT NULL DEFAULT 'mañana',
  `paralelo` VARCHAR(10) NOT NULL DEFAULT 'A',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_seccion` (`curso_id`,`gestion_id`,`turno`,`paralelo`),
  CONSTRAINT `fk_seccion_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seccion_gestion` FOREIGN KEY (`gestion_id`) REFERENCES `gestiones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3️⃣  Ajustar tabla CURSOS: quitar columnas de instancia (turno, paralelo, gestion)
--    y dejar solo catálogo (carrera_id, anio_carrera, nombre)
--    Si las columnas ya no existen, los ALTER no fallan gracias a IF EXISTS (MariaDB 10.5+)
ALTER TABLE `cursos`
  DROP COLUMN IF EXISTS `turno`,
  DROP COLUMN IF EXISTS `paralelo`,
  DROP COLUMN IF EXISTS `gestion`;

-- Asegurar índice único de catálogo (carrera + año del plan)
ALTER TABLE `cursos`
  DROP INDEX IF EXISTS `uq_curso_real`,
  ADD UNIQUE KEY `uq_curso_catalogo` (`carrera_id`,`anio_carrera`);

-- 4️⃣  Migrar datos existentes de `cursos` (si los hubiera) a `gestiones` + `secciones`
--    Se crea una gestión 2026 si no existe y se genera una sección por cada fila antigua.
INSERT IGNORE INTO `gestiones` (`anio`,`estado`) VALUES (2026,'abierta');

INSERT IGNORE INTO `secciones` (`curso_id`,`gestion_id`,`turno`,`paralelo`)
SELECT c.`id`,
       g.`id`,
       COALESCE(NULLIF(c.`turno_antiguo`,''),'mañana'),   -- turno_antiguo no existe, fallback
       COALESCE(NULLIF(c.`paralelo_antiguo`,''),'A')      -- paralelo_antiguo no existe, fallback
FROM `cursos` c
JOIN `gestiones` g ON g.`anio` = 2026
WHERE NOT EXISTS (
    SELECT 1 FROM `secciones` s WHERE s.`curso_id`=c.`id` AND s.`gestion_id`=g.`id`
);

-- 5️⃣  Añadir `seccion_id` a tablas dependientes (si no existe)
--    5.1 estudiantes_cursos  →  estudiantes_secciones (renombramos para claridad)
RENAME TABLE `estudiantes_cursos` TO `estudiantes_secciones`;

ALTER TABLE `estudiantes_secciones`
  ADD COLUMN IF NOT EXISTS `seccion_id` INT NOT NULL AFTER `curso_id`,
  ADD CONSTRAINT `fk_estsec_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones`(`id`) ON DELETE CASCADE;

--    5.2 docente_materia_curso → docente_materia_seccion
RENAME TABLE `docente_materia_curso` TO `docente_materia_seccion`;

ALTER TABLE `docente_materia_seccion`
  ADD COLUMN IF NOT EXISTS `seccion_id` INT NOT NULL AFTER `curso_id`,
  ADD CONSTRAINT `fk_dms_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones`(`id`) ON DELETE CASCADE;

--    5.3 parcial_periodo
ALTER TABLE `parcial_periodo`
  ADD COLUMN IF NOT EXISTS `seccion_id` INT NOT NULL AFTER `curso_id`,
  ADD CONSTRAINT `fk_pp_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones`(`id`) ON DELETE CASCADE;

--    5.4 notas
ALTER TABLE `notas`
  ADD COLUMN IF NOT EXISTS `seccion_id` INT NOT NULL AFTER `curso_id`,
  ADD CONSTRAINT `fk_notas_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones`(`id`) ON DELETE CASCADE;

--    5.5 asistencia
ALTER TABLE `asistencia`
  ADD COLUMN IF NOT EXISTS `seccion_id` INT NOT NULL AFTER `curso_id`,
  ADD CONSTRAINT `fk_asist_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones`(`id`) ON DELETE CASCADE;

--    5.6 registro_config
ALTER TABLE `registro_config`
  ADD COLUMN IF NOT EXISTS `seccion_id` INT NOT NULL AFTER `curso_id`,
  ADD CONSTRAINT `fk_regcfg_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones`(`id`) ON DELETE CASCADE;

-- 6️⃣  Poblar `seccion_id` en las tablas dependientes usando la sección creada en el paso 4
--    (asumimos una sola gestión 2026 y una sección por curso)
UPDATE `estudiantes_secciones` es
JOIN `secciones` s ON s.`curso_id` = es.`curso_id` AND s.`gestion_id` = (SELECT id FROM gestiones WHERE anio=2026)
SET es.`seccion_id` = s.`id`
WHERE es.`seccion_id` IS NULL OR es.`seccion_id` = 0;

UPDATE `docente_materia_seccion` dms
JOIN `secciones` s ON s.`curso_id` = dms.`curso_id` AND s.`gestion_id` = (SELECT id FROM gestiones WHERE anio=2026)
SET dms.`seccion_id` = s.`id`
WHERE dms.`seccion_id` IS NULL OR dms.`seccion_id` = 0;

UPDATE `parcial_periodo` pp
JOIN `secciones` s ON s.`curso_id` = pp.`curso_id` AND s.`gestion_id` = (SELECT id FROM gestiones WHERE anio=2026)
SET pp.`seccion_id` = s.`id`
WHERE pp.`seccion_id` IS NULL OR pp.`seccion_id` = 0;

UPDATE `notas` n
JOIN `secciones` s ON s.`curso_id` = n.`curso_id` AND s.`gestion_id` = (SELECT id FROM gestiones WHERE anio=2026)
SET n.`seccion_id` = s.`id`
WHERE n.`seccion_id` IS NULL OR n.`seccion_id` = 0;

UPDATE `asistencia` a
JOIN `secciones` s ON s.`curso_id` = a.`curso_id` AND s.`gestion_id` = (SELECT id FROM gestiones WHERE anio=2026)
SET a.`seccion_id` = s.`id`
WHERE a.`seccion_id` IS NULL OR a.`seccion_id` = 0;

UPDATE `registro_config` rc
JOIN `secciones` s ON s.`curso_id` = rc.`curso_id` AND s.`gestion_id` = (SELECT id FROM gestiones WHERE anio=2026)
SET rc.`seccion_id` = s.`id`
WHERE rc.`seccion_id` IS NULL OR rc.`seccion_id` = 0;

-- 7️⃣  (Opcional) Eliminar columnas antiguas `curso_id` de las tablas dependientes
--     Comentar si se quiere mantener compatibilidad temporal.
-- ALTER TABLE `estudiantes_secciones` DROP COLUMN `curso_id`;
-- ALTER TABLE `docente_materia_seccion` DROP COLUMN `curso_id`;
-- ALTER TABLE `parcial_periodo` DROP COLUMN `curso_id`;
-- ALTER TABLE `notas` DROP COLUMN `curso_id`;
-- ALTER TABLE `asistencia` DROP COLUMN `curso_id`;
-- ALTER TABLE `registro_config` DROP COLUMN `curso_id`;

SET FOREIGN_KEY_CHECKS = 1;