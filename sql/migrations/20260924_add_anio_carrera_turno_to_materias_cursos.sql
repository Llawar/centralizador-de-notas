-- ============================================================
-- Migración 2026-09-24: Estado real confirmado de la BD
-- Plan 003 — Reordenamiento Admin + Navegación por Rol
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. materias: anio_carrera (1..3) + índice compuesto
ALTER TABLE `materias`
  ADD COLUMN IF NOT EXISTS `anio_carrera` TINYINT NOT NULL DEFAULT 1;

ALTER TABLE `materias`
  ADD INDEX IF NOT EXISTS `idx_carrera_anio` (`carrera_id`, `anio_carrera`);

ALTER TABLE `materias`
  ADD CONSTRAINT IF NOT EXISTS `chk_materias_anio_carrera`
    CHECK (`anio_carrera` BETWEEN 1 AND 3);

-- 2. cursos: anio_carrera, turno, paralelo, gestion, carrera_id
ALTER TABLE `cursos`
  ADD COLUMN IF NOT EXISTS `anio_carrera` TINYINT NOT NULL DEFAULT 1;

ALTER TABLE `cursos`
  ADD COLUMN IF NOT EXISTS `turno` ENUM('mañana','tarde') NOT NULL DEFAULT 'mañana';

ALTER TABLE `cursos`
  MODIFY COLUMN `paralelo` VARCHAR(10) NOT NULL DEFAULT 'A';

CREATE UNIQUE INDEX IF NOT EXISTS `uq_curso_real`
  ON `cursos` (`carrera_id`, `anio_carrera`, `turno`, `paralelo`, `gestion`);

-- 3. carreras: turno_anio_1, turno_anio_2, turno_anio_3 ya existen
-- 4. parcial_periodo: sin cambios

SET FOREIGN_KEY_CHECKS = 1;