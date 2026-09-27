<?php
require_once __DIR__ . '/../config/conexion.php';

class CursosModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $sql = "SELECT c.*, ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo, ca.duracion AS carrera_duracion FROM cursos c
                JOIN carreras ca ON c.carrera_id = ca.id
                ORDER BY c.gestion DESC, ca.nombre, c.anio_carrera, c.turno, c.paralelo";
        $result = $this->conn->query($sql);
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        return $cursos;
    }

    public function getByCarrera($carreraId) {
        $stmt = $this->conn->prepare("SELECT * FROM cursos WHERE carrera_id = ? ORDER BY anio_carrera, turno, paralelo, gestion DESC");
        $stmt->bind_param("i", $carreraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        $stmt->close();
        return $cursos;
    }

    public function getByGestion($gestion) {
        $sql = "SELECT c.*, ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo, ca.duracion AS carrera_duracion FROM cursos c
                JOIN carreras ca ON c.carrera_id = ca.id
                WHERE c.gestion = ?
                ORDER BY ca.nombre, c.anio_carrera, c.turno, c.paralelo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $gestion);
        $stmt->execute();
        $result = $stmt->get_result();
        $cursos = [];
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        $stmt->close();
        return $cursos;
    }

    public function getByTurno($turno) {
        $stmt = $this->conn->prepare("SELECT c.*, ca.nombre AS carrera_nombre FROM cursos c JOIN carreras ca ON c.carrera_id=ca.id WHERE c.turno = ? ORDER BY c.gestion DESC, ca.nombre, c.anio_carrera, c.paralelo");
        if (!$stmt) return [];
        $stmt->bind_param("s", $turno);
        $stmt->execute();
        $result = $stmt->get_result();
        $cursos = [];
        while ($row = $result->fetch_assoc()) { $cursos[] = $row; }
        $stmt->close();
        return $cursos;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT c.*, ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo, ca.duracion AS carrera_duracion FROM cursos c JOIN carreras ca ON c.carrera_id = ca.id WHERE c.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $curso = $result->fetch_assoc();
        $stmt->close();
        return $curso;
    }

    public function crear($nombre, $anio, $paralelo, $carreraId, $gestion, $semestre, $turno = 'mañana') {
        $anioCarrera = max(1, min(3, (int) $anio));
        $turno = in_array($turno, ['mañana','tarde'], true) ? $turno : 'mañana';
        $paralelo = $paralelo !== '' ? $paralelo : 'A';
        $stmt = $this->conn->prepare("INSERT INTO cursos (nombre, anio_carrera, turno, paralelo, carrera_id, gestion, semestre) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissiii", $nombre, $anioCarrera, $turno, $paralelo, $carreraId, $gestion, $semestre);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Materias impartidas: materias cuyo anio_carrera coincide con el curso
     * + info de docente asignado (si existe)
     */
    public function getMateriasImpartidas($cursoId) {
        $curso = $this->getById($cursoId);
        if (!$curso) return [];
        $anioCarrera = (int) $curso['anio_carrera'];
        $carreraId = (int) $curso['carrera_id'];
        $sql = "SELECT m.id, m.nombre, m.codigo, m.anio_carrera,
                       dmc.docente_id, d.nombre_completo AS docente_nombre
                FROM materias m
                LEFT JOIN docente_materia_curso dmc ON dmc.materia_id = m.id AND dmc.curso_id = ?
                LEFT JOIN docentes d ON d.id = dmc.docente_id
                WHERE m.carrera_id = ? AND m.anio_carrera = ?
                ORDER BY m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $cursoId, $carreraId, $anioCarrera);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $stmt->close();
        return $materias;
    }

    public function getEstudiantes($cursoId) {
        $sql = "SELECT e.*, ec.semestre FROM estudiantes e
                JOIN estudiantes_cursos ec ON ec.estudiante_id = e.id
                WHERE ec.curso_id = ?
                ORDER BY e.nombre_completo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = $row;
        }
        $stmt->close();
        return $estudiantes;
    }

    public function getMateriasPorCurso($cursoId) {
        $sql = "SELECT DISTINCT m.id, m.nombre, m.codigo
                FROM docente_materia_curso dmc
                JOIN materias m ON dmc.materia_id = m.id
                WHERE dmc.curso_id = ?
                ORDER BY m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $stmt->close();
        return $materias;
    }

    public function getDocenteMaterias($cursoId) {
        $sql = "SELECT dmc.*, d.nombre_completo AS docente_nombre, m.nombre AS materia_nombre, m.codigo AS materia_codigo
                FROM docente_materia_curso dmc
                JOIN docentes d ON dmc.docente_id = d.id
                JOIN materias m ON dmc.materia_id = m.id
                WHERE dmc.curso_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $cursoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $asignaciones = [];
        while ($row = $result->fetch_assoc()) {
            $asignaciones[] = $row;
        }
        $stmt->close();
        return $asignaciones;
    }

    public function esDocenteAsignado($docenteId, $materiaId, $cursoId) {
        $stmt = $this->conn->prepare("SELECT id FROM docente_materia_curso WHERE docente_id = ? AND materia_id = ? AND curso_id = ?");
        $stmt->bind_param("iii", $docenteId, $materiaId, $cursoId);
        $stmt->execute();
        $stmt->store_result();
        $asignado = $stmt->num_rows > 0;
        $stmt->close();
        return $asignado;
    }

    public function asignarDocenteMateria($docenteId, $materiaId, $cursoId) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO docente_materia_curso (docente_id, materia_id, curso_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $docenteId, $materiaId, $cursoId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getAniosClase() {
        $result = $this->conn->query("SELECT DISTINCT gestion FROM cursos ORDER BY gestion DESC");
        $anios = [];
        while ($row = $result->fetch_assoc()) {
            $anios[] = $row['gestion'];
        }
        return $anios;
    }
}
