<?php
require_once __DIR__ . '/../config/conexion.php';

class CursosModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $sql = "SELECT c.id, c.anio_carrera, c.turno, c.paralelo, c.carrera_id,
                       CONCAT(ca.nombre, ' · ', c.anio_carrera, 'º · ', c.turno, ' · ', c.paralelo) AS nombre,
                       ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo FROM cursos c
                JOIN carreras ca ON c.carrera_id = ca.id
                ORDER BY ca.nombre, c.anio_carrera, c.turno, c.paralelo";
        $result = $this->conn->query($sql);
        $cursos = [];
        while ($row = $result->fetch_assoc()) { $cursos[] = $row; }
        return $cursos;
    }

    /** Opciones para filtros faceted */
    public function getFilterOptions(): array {
        $carreras = $this->conn->query("SELECT id, nombre FROM carreras WHERE estado='activa' ORDER BY nombre");
        $carrerasList = [];
        while ($r = $carreras->fetch_assoc()) $carrerasList[] = $r;
        $anios = $this->conn->query("SELECT DISTINCT anio_carrera FROM cursos ORDER BY anio_carrera");
        $aniosList = [];
        while ($r = $anios->fetch_assoc()) $aniosList[] = (int)$r['anio_carrera'];
        $turnos = $this->conn->query("SELECT DISTINCT turno FROM cursos ORDER BY turno");
        $turnosList = [];
        while ($r = $turnos->fetch_assoc()) $turnosList[] = $r['turno'];
        $paralelos = $this->conn->query("SELECT DISTINCT paralelo FROM cursos ORDER BY paralelo");
        $paralelosList = [];
        while ($r = $paralelos->fetch_assoc()) $paralelosList[] = $r['paralelo'];
        return [
            'carreras' => $carrerasList,
            'anios' => $aniosList,
            'turnos' => $turnosList,
            'paralelos' => $paralelosList,
        ];
    }

    /** Cursos filtrados por carrera, año, turno, paralelo (cualquiera puede ser null) */
    public function getByFilters(?int $carreraId, ?int $anio, ?string $turno, ?string $paralelo): array {
        $where = [];
        $params = [];
        $types = '';
        if ($carreraId) { $where[] = "c.carrera_id = ?"; $params[] = $carreraId; $types .= 'i'; }
        if ($anio) { $where[] = "c.anio_carrera = ?"; $params[] = $anio; $types .= 'i'; }
        if ($turno) { $where[] = "c.turno = ?"; $params[] = $turno; $types .= 's'; }
        if ($paralelo) { $where[] = "c.paralelo = ?"; $params[] = $paralelo; $types .= 's'; }
        $sql = "SELECT c.id, c.anio_carrera, c.turno, c.paralelo, c.carrera_id,
                       CONCAT(ca.nombre, ' · ', c.anio_carrera, 'º · ', c.turno, ' · ', c.paralelo) AS nombre,
                       ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo
                FROM cursos c JOIN carreras ca ON c.carrera_id = ca.id";
        if ($where) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY ca.nombre, c.anio_carrera, c.turno, c.paralelo";
        $stmt = $this->conn->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $cursos = [];
        while ($row = $res->fetch_assoc()) $cursos[] = $row;
        $stmt->close();
        return $cursos;
    }

    public function getByCarrera($carreraId) {
        $stmt = $this->conn->prepare("SELECT c.id, c.anio_carrera, c.turno, c.paralelo, c.carrera_id, CONCAT((SELECT nombre FROM carreras WHERE id = c.carrera_id), ' · ', c.anio_carrera, 'º · ', c.turno, ' · ', c.paralelo) AS nombre FROM cursos c WHERE carrera_id = ? ORDER BY anio_carrera, turno, paralelo");
        $stmt->bind_param("i", $carreraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $cursos = [];
        while ($row = $result->fetch_assoc()) { $cursos[] = $row; }
        $stmt->close();
        return $cursos;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT c.id, c.anio_carrera, c.turno, c.paralelo, c.carrera_id, CONCAT(ca.nombre, ' · ', c.anio_carrera, 'º · ', c.turno, ' · ', c.paralelo) AS nombre, ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo FROM cursos c JOIN carreras ca ON c.carrera_id = ca.id WHERE c.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $curso = $result->fetch_assoc();
        $stmt->close();
        return $curso;
    }

    public function crear($anioCarrera, $turno, $paralelo, $carreraId) {
        $anioCarrera = max(1, min(3, (int) $anioCarrera));
        $turno = in_array($turno, ['mañana','tarde'], true) ? $turno : 'mañana';
        $paralelo = $paralelo !== '' ? $paralelo : 'A';
        $stmt = $this->conn->prepare("INSERT INTO cursos (anio_carrera, turno, paralelo, carrera_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $anioCarrera, $turno, $paralelo, $carreraId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function actualizar($id, $anioCarrera, $turno, $paralelo, $carreraId) {
        $anioCarrera = max(1, min(3, (int) $anioCarrera));
        $stmt = $this->conn->prepare("UPDATE cursos SET anio_carrera=?, turno=?, paralelo=?, carrera_id=? WHERE id=?");
        $stmt->bind_param("issii", $anioCarrera, $turno, $paralelo, $carreraId, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM cursos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getMateriasImpartidas($cursoId, $gestionId = null) {
        $curso = $this->getById($cursoId);
        if (!$curso) return [];
        $anioCarrera = (int) $curso['anio_carrera'];
        $carreraId = (int) $curso['carrera_id'];
        if ($gestionId === null) {
            $g = $this->conn->query("SELECT id FROM gestiones WHERE estado='abierta' LIMIT 1");
            $row = $g ? $g->fetch_assoc() : null;
            $gestionId = $row ? (int) $row['id'] : 0;
        }
        $sql = "SELECT m.id, m.nombre, m.codigo, m.anio_carrera,
                       dms.docente_id, d.nombre_completo AS docente_nombre
                FROM materias m
                LEFT JOIN docente_materia_seccion dms ON dms.materia_id = m.id AND dms.curso_id = ? AND dms.gestion_id = ?
                LEFT JOIN docentes d ON d.id = dms.docente_id
                WHERE m.carrera_id = ? AND m.anio_carrera = ?
                ORDER BY m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiii", $cursoId, $gestionId, $carreraId, $anioCarrera);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) { $materias[] = $row; }
        $stmt->close();
        return $materias;
    }

    public function getEstudiantes($cursoId, $gestionId = null) {
        if ($gestionId === null) {
            $g = $this->conn->query("SELECT id FROM gestiones WHERE estado='abierta' LIMIT 1");
            $row = $g ? $g->fetch_assoc() : null;
            $gestionId = $row ? (int) $row['id'] : 0;
        }
        $sql = "SELECT e.* FROM estudiantes e
                JOIN estudiantes_secciones es ON es.estudiante_id = e.id
                WHERE es.curso_id = ? AND es.gestion_id = ?
                ORDER BY e.nombre_completo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $cursoId, $gestionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) { $estudiantes[] = $row; }
        $stmt->close();
        return $estudiantes;
    }

    public function asignarDocenteMateria($docenteId, $materiaId, $cursoId, $gestionId) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO docente_materia_seccion (docente_id, materia_id, curso_id, gestion_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $docenteId, $materiaId, $cursoId, $gestionId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function quitarAsignacion(int $docenteId, int $materiaId, int $cursoId, int $gestionId): bool {
        $stmt = $this->conn->prepare("DELETE FROM docente_materia_seccion WHERE docente_id = ? AND materia_id = ? AND curso_id = ? AND gestion_id = ? LIMIT 1");
        $stmt->bind_param("iiii", $docenteId, $materiaId, $cursoId, $gestionId);
        $result = $stmt->execute();
        $stmt->close();
        return (bool) $result;
    }
}
