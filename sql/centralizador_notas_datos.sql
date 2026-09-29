-- ============================================================
-- Centralizador de Notas - Instituto Tecnologico "PACCIOLI"
-- Datos de desarrollo (seed) – compatible con el esquema final
-- Ejecutar DESPUÉS de centralizador_notas_esquema.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 0. Constante hash de contraseña "123456" (bcrypt)
-- ------------------------------------------------------------
SET @pwd = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- ------------------------------------------------------------
-- 1. GESTIONES
-- ------------------------------------------------------------
INSERT IGNORE INTO `gestiones` (`anio`,`estado`,`fecha_inicio`,`fecha_fin`) VALUES
(2026,'abierta','2026-02-01','2026-11-30'),
(2025,'cerrada','2025-02-01','2025-11-30');

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
--    Para semestrales se añade sufijo -S1 / -S2
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_seed_materias;
DELIMITER //
CREATE PROCEDURE sp_seed_materias()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_cid INT;
  DECLARE v_tipo VARCHAR(10);
  DECLARE cur CURSOR FOR SELECT id, tipo FROM carreras WHERE estado='activa';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO v_cid, v_tipo;
    IF done THEN LEAVE read_loop; END IF;

    SET @anio = 1;
    WHILE @anio <= 3 DO
      SET @idx = 1;
      WHILE @idx <= 6 DO
        SET @base = (SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX('Matemática,Comunicación,Legislación,Práctica,Inglés,Electiva',',',@idx),',',-1)));
        SET @code = CONCAT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(SUBSTRING(@base,1,3)),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'-',@anio,'-C',v_cid);
        IF v_tipo = 'semestral' THEN
          INSERT IGNORE INTO `materias` (`nombre`,`codigo`,`carrera_id`,`anio_carrera`) VALUES
            (CONCAT(@base,' I - S1'), CONCAT(@code,'-S1'), v_cid, @anio),
            (CONCAT(@base,' I - S2'), CONCAT(@code,'-S2'), v_cid, @anio);
        ELSE
          INSERT IGNORE INTO `materias` (`nombre`,`codigo`,`carrera_id`,`anio_carrera`) VALUES
            (CONCAT(@base,' ',CASE @anio WHEN 1 THEN 'I' WHEN 2 THEN 'II' ELSE 'III' END), @code, v_cid, @anio);
        END IF;
        SET @idx = @idx + 1;
      END WHILE;
      SET @anio = @anio + 1;
    END WHILE;
  END LOOP;
  CLOSE cur;
END//
DELIMITER ;
CALL sp_seed_materias();
DROP PROCEDURE IF EXISTS sp_seed_materias;

-- ------------------------------------------------------------
-- 4. CURSOS (catálogo) – combinaciones carrera × año × turno × paralelo
--    Turnos: mañana, tarde  | Paralelos: A (por ahora)
-- ------------------------------------------------------------
INSERT IGNORE INTO `cursos` (`anio_carrera`,`turno`,`paralelo`,`carrera_id`)
SELECT anio_carrera, t.turno, 'A', c.id
FROM `carreras` c
JOIN (SELECT 1 AS anio_carrera UNION SELECT 2 UNION SELECT 3) a
JOIN (SELECT 'mañana' AS turno UNION SELECT 'tarde') t
WHERE c.estado='activa';

-- ------------------------------------------------------------
-- 5. DOCENTES – 2 por carrera
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
-- 6. ESTUDIANTES – 5 por carrera
-- ------------------------------------------------------------
INSERT IGNORE INTO `estudiantes` (`ci`,`nombre_completo`,`matricula`,`anio_ingreso`,`estado`) VALUES
('200001','Estudiante Contaduría 1','MAT-200001',2026,'activo'),
('200002','Estudiante Contaduría 2','MAT-200002',2026,'activo'),
('200003','Estudiante Contaduría 3','MAT-200003',2026,'activo'),
('200004','Estudiante Contaduría 4','MAT-200004',2026,'activo'),
('200005','Estudiante Contaduría 5','MAT-200005',2026,'activo'),

('210001','Estudiante Secretariado 1','MAT-210001',2026,'activo'),
('210002','Estudiante Secretariado 2','MAT-210002',2026,'activo'),
('210003','Estudiante Secretariado 3','MAT-210003',2026,'activo'),
('210004','Estudiante Secretariado 4','MAT-210004',2026,'activo'),
('210005','Estudiante Secretariado 5','MAT-210005',2026,'activo'),

('220001','Estudiante Sistemas 1','MAT-220001',2026,'activo'),
('220002','Estudiante Sistemas 2','MAT-220002',2026,'activo'),
('220003','Estudiante Sistemas 3','MAT-220003',2026,'activo'),
('220004','Estudiante Sistemas 4','MAT-220004',2026,'activo'),
('220005','Estudiante Sistemas 5','MAT-220005',2026,'activo'),

('230001','Estudiante Electrónica 1','MAT-230001',2026,'activo'),
('230002','Estudiante Electrónica 2','MAT-230002',2026,'activo'),
('230003','Estudiante Electrónica 3','MAT-230003',2026,'activo'),
('230004','Estudiante Electrónica 4','MAT-230004',2026,'activo'),
('230005','Estudiante Electrónica 5','MAT-230005',2026,'activo'),

('240001','Estudiante Electricidad 1','MAT-240001',2026,'activo'),
('240002','Estudiante Electricidad 2','MAT-240002',2026,'activo'),
('240003','Estudiante Electricidad 3','MAT-240003',2026,'activo'),
('240004','Estudiante Electricidad 4','MAT-240004',2026,'activo'),
('240005','Estudiante Electricidad 5','MAT-240005',2026,'activo'),

('250001','Estudiante Gastronomía 1','MAT-250001',2026,'activo'),
('250002','Estudiante Gastronomía 2','MAT-250002',2026,'activo'),
('250003','Estudiante Gastronomía 3','MAT-250003',2026,'activo'),
('250004','Estudiante Gastronomía 4','MAT-250004',2026,'activo'),
('250005','Estudiante Gastronomía 5','MAT-250005',2026,'activo');

-- ------------------------------------------------------------
-- 7. USUARIOS (admin + 1 por cada docente/estudiante, username = CI salvo admin)
--    password inicial 123456 (@pwd) para todos
-- ------------------------------------------------------------
INSERT IGNORE INTO `usuarios` (`username`,`password`,`rol`,`docente_id`,`estudiante_id`,`estado`) VALUES
('admin',@pwd,'admin',NULL,NULL,'activo');

INSERT IGNORE INTO `usuarios` (`username`,`password`,`rol`,`docente_id`,`estudiante_id`,`estado`)
SELECT d.ci, @pwd, 'docente', d.id, NULL, 'activo' FROM `docentes` d;

INSERT IGNORE INTO `usuarios` (`username`,`password`,`rol`,`docente_id`,`estudiante_id`,`estado`)
SELECT e.ci, @pwd, 'estudiante', NULL, e.id, 'activo' FROM `estudiantes` e;

-- ------------------------------------------------------------
-- 8. INSCRIPCIONES (estudiantes_secciones) – cada alumno en su curso 1er año, gestión 2026, turno mañana, paralelo A
-- ------------------------------------------------------------
INSERT IGNORE INTO `estudiantes_secciones` (`estudiante_id`,`curso_id`,`gestion_id`,`semestre`)
SELECT e.id, cu.id, g.id, 1
FROM `estudiantes` e
JOIN `gestiones` g ON g.anio = 2026
JOIN `cursos` cu ON cu.carrera_id = CASE LEFT(e.ci,3)
        WHEN '200' THEN 1 WHEN '210' THEN 2 WHEN '220' THEN 3
        WHEN '230' THEN 4 WHEN '240' THEN 5 WHEN '250' THEN 6 ELSE 1 END
    AND cu.anio_carrera = 1
    AND cu.turno = 'mañana'
    AND cu.paralelo = 'A'
WHERE e.anio_ingreso = 2026;

-- ------------------------------------------------------------
-- 9. ASIGNACIONES DOCENTE‑MATERIA‑SECCIÓN (docente_materia_seccion)
--    Asignamos los 2 docentes de cada carrera a las materias del 1er año, gestión 2026
-- ------------------------------------------------------------
INSERT IGNORE INTO `docente_materia_seccion` (`docente_id`,`materia_id`,`curso_id`,`gestion_id`)
SELECT d.id, m.id, cu.id, g.id
FROM `docentes` d
JOIN `gestiones` g ON g.anio = 2026
JOIN `cursos` cu ON cu.carrera_id = CASE LEFT(d.ci,3)
        WHEN '111' THEN 1 WHEN '222' THEN 2 WHEN '333' THEN 3
        WHEN '444' THEN 4 WHEN '555' THEN 5 WHEN '666' THEN 6 ELSE 1 END
    AND cu.anio_carrera = 1 AND cu.turno = 'mañana' AND cu.paralelo = 'A'
JOIN `materias` m ON m.carrera_id = cu.carrera_id AND m.anio_carrera = 1
WHERE d.estado='activo';

-- ------------------------------------------------------------
-- 10. PARCIALES INICIALES (parcial_periodo) – estado abierto para cada materia‑curso‑gestión
-- ------------------------------------------------------------
INSERT IGNORE INTO `parcial_periodo` (`curso_id`,`materia_id`,`gestion_id`,`parcial`,`estado`)
SELECT cu.id, m.id, g.id,
       CASE
         WHEN ca.tipo='semestral' THEN CONCAT('Parcial S', (m.anio_carrera*2)-1)
         ELSE CONCAT('Parcial ', m.anio_carrera)
       END,
       'abierto'
FROM `cursos` cu
JOIN `gestiones` g ON g.anio = 2026
JOIN `carreras` ca ON ca.id = cu.carrera_id
JOIN `materias` m ON m.carrera_id = ca.id AND m.anio_carrera = cu.anio_carrera
WHERE g.anio = 2026;

-- ------------------------------------------------------------
-- FIN
-- ------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Datos de desarrollo insertados correctamente' AS status;