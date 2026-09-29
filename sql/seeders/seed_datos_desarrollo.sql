-- ============================================================
-- OBSOLETO: no ejecutar. Usar sql/centralizador_notas_esquema.sql + sql/centralizador_notas_datos.sql
-- (este archivo referencia tablas antiguas: secciones, email/telefono, referer_id)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 0. Constantes útiles
-- ------------------------------------------------------------
-- Hash bcrypt de la contraseña "123456"
SET @pwd_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- ------------------------------------------------------------
-- 1. GESTIÓN 2026 (abierta)
-- ------------------------------------------------------------
INSERT IGNORE INTO `gestiones` (`anio`,`estado`,`fecha_inicio`,`fecha_fin`)
VALUES (2026,'abierta','2026-02-01','2026-11-30');

-- ------------------------------------------------------------
-- 2. CARRERAS (6)
-- ------------------------------------------------------------
INSERT IGNORE INTO `carreras` (`id`,`nombre`,`duracion`,`tipo`,`estado`) VALUES
(1,'Contaduría General',3,'anual','activa'),
(2,'Secretariado Ejecutivo',3,'anual','activa'),
(3,'Sistemas Informáticos',3,'anual','activa'),
(4,'Electrónica',3,'semestral','activa'),
(5,'Electricidad Industrial',3,'semestral','activa'),
(6,'Gastronomía',3,'anual','activa');

-- ------------------------------------------------------------
-- 3. MATERIAS – 6 materias base por carrera, replicadas I/II/III
--    Para carreras semestrales añadimos sufijo -S1 / -S2 por semestre
-- ------------------------------------------------------------
-- Lista base (nombre, codigo_base)
SET @materias_base = 'Matemática,Comunicación,Legislación,Práctica,Inglés,Electiva';

-- Procedimiento auxiliar: insertar materias por carrera
DROP PROCEDURE IF EXISTS sp_insert_materias;
DELIMITER //
CREATE PROCEDURE sp_insert_materias()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_carrera_id INT;
  DECLARE v_tipo VARCHAR(10);
  DECLARE cur CURSOR FOR SELECT id, tipo FROM carreras WHERE estado='activa';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO v_carrera_id, v_tipo;
    IF done THEN LEAVE read_loop; END IF;

    -- Año 1,2,3
    SET @anio = 1;
    WHILE @anio <= 3 DO
      -- cada materia base
      SET @idx = 1;
      WHILE @idx <= 6 DO
        SET @nombre_base = (SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(@materias_base,',',@idx),',',-1)));
        SET @codigo_base = CONCAT(UPPER(SUBSTRING(@nombre_base,1,3)),'-',@anio);
        IF v_tipo = 'semestral' THEN
          -- dos semestres
          INSERT IGNORE INTO materias (nombre,codigo,carrera_id,anio_carrera)
          VALUES (CONCAT(@nombre_base,' I - S1'), CONCAT(@codigo_base,'-S1'), v_carrera_id, @anio),
                 (CONCAT(@nombre_base,' I - S2'), CONCAT(@codigo_base,'-S2'), v_carrera_id, @anio);
        ELSE
          INSERT IGNORE INTO materias (nombre,codigo,carrera_id,anio_carrera)
          VALUES (CONCAT(@nombre_base,' ',CASE @anio WHEN 1 THEN 'I' WHEN 2 THEN 'II' ELSE 'III' END), @codigo_base, v_carrera_id, @anio);
        END IF;
        SET @idx = @idx + 1;
      END WHILE;
      SET @anio = @anio + 1;
    END WHILE;
  END LOOP;
  CLOSE cur;
END//
DELIMITER ;
CALL sp_insert_materias();
DROP PROCEDURE IF EXISTS sp_insert_materias;

-- ------------------------------------------------------------
-- 4. CURSOS (catálogo) – uno por carrera + año del plan (3 años) = 18 filas
-- ------------------------------------------------------------
INSERT IGNORE INTO `cursos` (`nombre`,`carrera_id`,`anio_carrera`)
SELECT CONCAT(' ',CASE anio_carrera WHEN 1 THEN '1º' WHEN 2 THEN '2º' ELSE '3º' END,' ',c.nombre),
       c.id, anio_carrera
FROM carreras c
JOIN (SELECT 1 AS anio_carrera UNION SELECT 2 UNION SELECT 3) a ON 1=1
WHERE c.estado='activa';

-- ------------------------------------------------------------
-- 5. SECCIONES – para gestión 2026, turno mañana, paralelo A
-- ------------------------------------------------------------
INSERT IGNORE INTO `secciones` (`curso_id`,`gestion_id`,`turno`,`paralelo`)
SELECT cu.id, g.id, 'mañana', 'A'
FROM cursos cu
JOIN gestiones g ON g.anio = 2026;

-- ------------------------------------------------------------
-- 6. DOCENTES – 2 por carrera (12 total)
-- ------------------------------------------------------------
INSERT IGNORE INTO `docentes` (`ci`,`nombre_completo`,`anio_ingreso`,`estado`) VALUES
('1111111','Docente Contaduría 1',2020,'activo'),
('1111112','Docente Contaduría 2',2021,'activo'),
('2222221','Docente Secretariado 1',2019,'activo'),
('2222222','Docente Secretariado 2',2020,'activo'),
('3333331','Docente Sistemas 1',2018,'activo'),
('3333332','Docente Sistemas 2',2019,'activo'),
('4444441','Docente Electrónica 1',2020,'activo'),
('4444442','Docente Electrónica 2',2021,'activo'),
('5555551','Docente Electricidad 1',2019,'activo'),
('5555552','Docente Electricidad 2',2020,'activo'),
('6666661','Docente Gastronomía 1',2021,'activo'),
('6666662','Docente Gastronomía 2',2022,'activo');

-- ------------------------------------------------------------
-- 7. ESTUDIANTES – 5 por carrera (30 total)
-- ------------------------------------------------------------
INSERT IGNORE INTO `estudiantes` (`ci`,`nombre_completo`,`matricula`,`anio_ingreso`,`estado`) VALUES
('200001','Estudiante Contaduría 1','MAT-200001',2026,'activo'),
('200002','Estudiante Contaduría 2','MAT-200002',2026,'est2@paccioli.edu',@pwd_hash,'activo'),
('200003','Estudiante Contaduría 3','MAT-200003',2026,'est3@paccioli.edu',@pwd_hash,'activo'),
('200004','Estudiante Contaduría 4','MAT-200004',2026,'est4@paccioli.edu',@pwd_hash,'activo'),
('200005','Estudiante Contaduría 5','MAT-200005',2026,'est5@paccioli.edu',@pwd_hash,'activo'),

('210001','Estudiante Secretariado 1','MAT-210001',2026,'est6@paccioli.edu',@pwd_hash,'activo'),
('210002','Estudiante Secretariado 2','MAT-210002',2026,'est7@paccioli.edu',@pwd_hash,'activo'),
('210003','Estudiante Secretariado 3','MAT-210003',2026,'est8@paccioli.edu',@pwd_hash,'activo'),
('210004','Estudiante Secretariado 4','MAT-210004',2026,'est9@paccioli.edu',@pwd_hash,'activo'),
('210005','Estudiante Secretariado 5','MAT-210005',2026,'est10@paccioli.edu',@pwd_hash,'activo'),

('220001','Estudiante Sistemas 1','MAT-220001',2026,'est11@paccioli.edu',@pwd_hash,'activo'),
('220002','Estudiante Sistemas 2','MAT-220002',2026,'est12@paccioli.edu',@pwd_hash,'activo'),
('220003','Estudiante Sistemas 3','MAT-220003',2026,'est13@paccioli.edu',@pwd_hash,'activo'),
('220004','Estudiante Sistemas 4','MAT-220004',2026,'est14@paccioli.edu',@pwd_hash,'activo'),
('220005','Estudiante Sistemas 5','MAT-220005',2026,'est15@paccioli.edu',@pwd_hash,'activo'),

('230001','Estudiante Electrónica 1','MAT-230001',2026,'est16@paccioli.edu',@pwd_hash,'activo'),
('230002','Estudiante Electrónica 2','MAT-230002',2026,'est17@paccioli.edu',@pwd_hash,'activo'),
('230003','Estudiante Electrónica 3','MAT-230003',2026,'est18@paccioli.edu',@pwd_hash,'activo'),
('230004','Estudiante Electrónica 4','MAT-230004',2026,'est19@paccioli.edu',@pwd_hash,'activo'),
('230005','Estudiante Electrónica 5','MAT-230005',2026,'est20@paccioli.edu',@pwd_hash,'activo'),

('240001','Estudiante Electricidad 1','MAT-240001',2026,'est21@paccioli.edu',@pwd_hash,'activo'),
('240002','Estudiante Electricidad 2','MAT-240002',2026,'est22@paccioli.edu',@pwd_hash,'activo'),
('240003','Estudiante Electricidad 3','MAT-240003',2026,'est23@paccioli.edu',@pwd_hash,'activo'),
('240004','Estudiante Electricidad 4','MAT-240004',2026,'est24@paccioli.edu',@pwd_hash,'activo'),
('240005','Estudiante Electricidad 5','MAT-240005',2026,'est25@paccioli.edu',@pwd_hash,'activo'),

('250001','Estudiante Gastronomía 1','MAT-250001',2026,'est26@paccioli.edu',@pwd_hash,'activo'),
('250002','Estudiante Gastronomía 2','MAT-250002',2026,'est27@paccioli.edu',@pwd_hash,'activo'),
('250003','Estudiante Gastronomía 3','MAT-250003',2026,'est28@paccioli.edu',@pwd_hash,'activo'),
('250004','Estudiante Gastronomía 4','MAT-250004',2026,'est29@paccioli.edu',@pwd_hash,'activo'),
('250005','Estudiante Gastronomía 5','MAT-250005',2026,'est30@paccioli.edu',@pwd_hash,'activo');

-- ------------------------------------------------------------
-- 8. USUARIOS (credenciales) – admin + 12 docentes + 30 estudiantes
-- ------------------------------------------------------------
INSERT IGNORE INTO `usuarios` (`username`,`password`,`rol`,`referer_id`,`estado`) VALUES
('admin',@pwd_hash,'admin',1,'activo');

-- docentes
INSERT IGNORE INTO `usuarios` (`username`,`password`,`rol`,`referer_id`,`estado`)
SELECT CONCAT('doc',d.id), @pwd_hash, 'docente', d.id, 'activo' FROM docentes d;

-- estudiantes
INSERT IGNORE INTO `usuarios` (`username`,`password`,`rol`,`referer_id`,`estado`)
SELECT CONCAT('est',e.id), @pwd_hash, 'estudiante', e.id, 'activo' FROM estudiantes e;

-- ------------------------------------------------------------
-- 9. INSCRIPCIONES (estudiantes_secciones) – cada alumno en la sección de su carrera, año 1
-- ------------------------------------------------------------
INSERT IGNORE INTO `estudiantes_secciones` (`estudiante_id`,`seccion_id`,`semestre`)
SELECT e.id, s.id, 1
FROM estudiantes e
JOIN secciones s ON s.curso_id = (
    SELECT cu.id FROM cursos cu WHERE cu.carrera_id = (
        SELECT carrera_id FROM materias m WHERE m.carrera_id = (SELECT carrera_id FROM carreras ca WHERE ca.id = (SELECT carrera_id FROM estudiantes WHERE id=e.id LIMIT 1) LIMIT 1) LIMIT 1
    ) AND cu.anio_carrera = 1
) AND s.gestion_id = (SELECT id FROM gestiones WHERE anio=2026)
WHERE e.anio_ingreso = 2026;

-- Nota: la consulta arriba es ilustrativa; en entorno real se haría por carrera.
-- Para simplicidad, inscribimos a todos los estudiantes en la sección de 1º año de su carrera.
-- Se puede refinarse con un script PHP posterior.

-- ------------------------------------------------------------
-- 10. ASIGNACIONES DOCENTE-MATERIA-SECCIÓN (docente_materia_seccion)
--     Asignamos los 2 docentes de cada carrera a las 6 materias del 1º año
-- ------------------------------------------------------------
INSERT IGNORE INTO `docente_materia_seccion` (`docente_id`,`materia_id`,`seccion_id`)
SELECT d.id, m.id, s.id
FROM docentes d
JOIN materias m ON m.carrera_id = (SELECT carrera_id FROM docentes WHERE id=d.id LIMIT 1) AND m.anio_carrera = 1
JOIN secciones s ON s.curso_id = (SELECT cu.id FROM cursos cu WHERE cu.carrera_id = m.carrera_id AND cu.anio_carrera = 1)
                AND s.gestion_id = (SELECT id FROM gestiones WHERE anio=2026)
WHERE d.estado='activo'
LIMIT 100;  -- control de duplicados

-- ------------------------------------------------------------
-- 11. PARCIALES (parcial_periodo) – se generan automáticamente por trigger/modelo,
--     pero insertamos estado inicial "abierto" para cada materia‑sección.
-- ------------------------------------------------------------
INSERT IGNORE INTO `parcial_periodo` (`seccion_id`,`materia_id`,`gestion_id`,`parcial`,`estado`)
SELECT s.id, m.id, g.id,
       CASE
         WHEN (SELECT tipo FROM carreras WHERE id = (SELECT carrera_id FROM cursos WHERE id=s.curso_id))='semestral'
         THEN CONCAT('Parcial S', (m.anio_carrera*2)-1)  -- simplificado
         ELSE CONCAT('Parcial ', m.anio_carrera)
       END,
       'abierto'
FROM secciones s
JOIN gestiones g ON g.id = s.gestion_id
JOIN materias m ON m.carrera_id = (SELECT carrera_id FROM cursos WHERE id=s.curso_id) AND m.anio_carrera = (SELECT anio_carrera FROM cursos WHERE id=s.curso_id)
WHERE g.anio = 2026;

-- ------------------------------------------------------------
-- 12. FIN
-- ------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;

-- Mensaje de confirmación
SELECT 'Seed de desarrollo insertado correctamente' AS status;