-- ============================================
-- BASE DE DATOS: CENTRALIZADOR DE NOTAS
-- Instituto Tecnologico "PACCIOLI"
-- Esquema completo + datos de desarrollo
-- ============================================

CREATE DATABASE IF NOT EXISTS centralizador_notas
DEFAULT CHARACTER SET utf8mb4
DEFAULT COLLATE utf8mb4_general_ci;

USE centralizador_notas;

-- ============================================
-- TABLA: carreras
-- ============================================
CREATE TABLE carreras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    duracion INT NOT NULL DEFAULT 3,
    tipo ENUM('anual', 'semestral') NOT NULL DEFAULT 'anual',
    turno_anio_1 ENUM('mañana','tarde') NOT NULL DEFAULT 'mañana',
    turno_anio_2 ENUM('mañana','tarde') NOT NULL DEFAULT 'mañana',
    turno_anio_3 ENUM('mañana','tarde') NOT NULL DEFAULT 'mañana',
    estado ENUM('activa', 'inactiva') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: docentes
-- ============================================
CREATE TABLE docentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ci VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    email VARCHAR(150),
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: estudiantes
-- ============================================
CREATE TABLE estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ci VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    matricula VARCHAR(50) UNIQUE,
    anio_ingreso INT NOT NULL,
    email VARCHAR(150),
    password VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: usuarios
-- ============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','docente','estudiante') NOT NULL,
    referer_id INT NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: materias
-- ============================================
CREATE TABLE materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(20) UNIQUE,
    carrera_id INT NOT NULL,
    anio_carrera TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE,
    CHECK (anio_carrera BETWEEN 1 AND 3),
    INDEX idx_carrera_anio (carrera_id, anio_carrera)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: cursos
-- ============================================
CREATE TABLE cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    paralelo VARCHAR(10) NOT NULL DEFAULT 'A',
    anio_carrera TINYINT(1) NOT NULL DEFAULT 1,
    turno ENUM('mañana','tarde') NOT NULL DEFAULT 'mañana',
    carrera_id INT NOT NULL,
    gestion INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE,
    UNIQUE KEY uq_curso_real (carrera_id, anio_carrera, turno, paralelo, gestion)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: estudiantes_cursos (inscripciones)
-- ============================================
CREATE TABLE estudiantes_cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    semestre INT DEFAULT 1,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inscripcion (estudiante_id, curso_id)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: docente_materia_curso (asignaciones)
-- ============================================
CREATE TABLE docente_materia_curso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    docente_id INT NOT NULL,
    materia_id INT NOT NULL,
    curso_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_id) REFERENCES docentes(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asignacion (docente_id, materia_id, curso_id)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: parcial_periodo (temporadas por parcial)
-- ============================================
CREATE TABLE parcial_periodo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    gestion INT NOT NULL,
    materia_id INT NOT NULL,
    parcial VARCHAR(30) NOT NULL,
    estado ENUM('abierto', 'enviado', 'cerrado') NOT NULL DEFAULT 'abierto',
    abierto_por INT NULL,
    enviado_por INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    UNIQUE KEY uq_curso_mat_parcial (curso_id, materia_id, gestion, parcial)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: notas (registro de calificaciones)
-- ============================================
CREATE TABLE notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    materia_id INT NOT NULL,
    tipo ENUM('conocer','hacer','ser','parcial','final') NOT NULL,
    nombre_actividad VARCHAR(200),
    nota DECIMAL(5,2) DEFAULT 0.00,
    gestion INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_nota (estudiante_id, curso_id, materia_id, tipo, nombre_actividad)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: asistencia (registro de asistencia)
-- ============================================
CREATE TABLE asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    materia_id INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('presente','ausente','justificado') NOT NULL DEFAULT 'presente',
    gestion INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asistencia (estudiante_id, curso_id, materia_id, fecha)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: registro_config (configuración de planillas oficiales)
-- ============================================
CREATE TABLE registro_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    materia_id INT NOT NULL,
    gestion INT NOT NULL,
    carrera TEXT,
    asignatura TEXT,
    codigo VARCHAR(50),
    anio VARCHAR(20),
    periodo VARCHAR(50),
    docente TEXT,
    paralelo VARCHAR(20),
    observacion TEXT,
    logo MEDIUMBLOB,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reg (curso_id, materia_id, gestion)
) ENGINE=InnoDB;

-- ============================================
-- DATOS DE DESARROLLO (seed mínimo)
-- ============================================

-- Usuario admin por defecto (pass: admin123)
INSERT INTO usuarios (username, password, rol, referer_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1)
ON DUPLICATE KEY UPDATE password=VALUES(password);

-- Carrera de ejemplo
INSERT INTO carreras (nombre, duracion, tipo, turno_anio_1, turno_anio_2, turno_anio_3) VALUES
('Técnico Superior en Informática', 3, 'anual', 'mañana', 'mañana', 'mañana')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Materias de ejemplo (1er año)
INSERT INTO materias (nombre, codigo, carrera_id, anio_carrera) VALUES
('Programación I', 'INF-101', 1, 1),
('Matemática Discreta', 'INF-102', 1, 1),
('Sistemas Operativos', 'INF-103', 1, 1)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

-- Curso de ejemplo (gestión 2026)
INSERT INTO cursos (nombre, paralelo, anio_carrera, turno, carrera_id, gestion) VALUES
('1ro Informática A - 2026', 'A', 1, 'mañana', 1, 2026)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);