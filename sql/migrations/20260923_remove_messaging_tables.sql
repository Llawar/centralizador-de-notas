-- ============================================
-- Migración: Eliminar tablas de mensajería
-- Fecha: 2025-09-23
-- Descripción: Elimina las tablas `respuestas` y `mensajes`
--              tras retirar la funcionalidad de mensajería del código.
-- ============================================

-- 1. Eliminar FK y tabla dependiente primero
DROP TABLE IF EXISTS `respuestas`;

-- 2. Eliminar tabla principal
DROP TABLE IF EXISTS `mensajes`;

-- 3. (Opcional) Verificar que ya no existen
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('mensajes','respuestas');
-- Debe devolver 0 filas.