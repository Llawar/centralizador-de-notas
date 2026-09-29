<?php
require_once __DIR__ . '/../config/conexion.php';

class EstudiantesModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $result = $this->conn->query("SELECT * FROM estudiantes ORDER BY nombre_completo");
        $estudiantes = [];
        while ($row = $result->fetch_assoc()) { $estudiantes[] = $row; }
        return $estudiantes;
    }

    /** Devuelve ['data'=>[], 'total'=>int] con paginación y búsqueda opcional */
    public function getAllPaginated(int $limit, int $offset, string $search = ''): array {
        $where = '';
        $params = [];
        $types = '';
        if ($search !== '') {
            $where = "WHERE nombre_completo LIKE ? OR ci LIKE ? OR matricula LIKE ?";
            $like = "%$search%";
            $params = [$like, $like, $like];
            $types = 'sss';
        }
        // total
        $sqlCount = "SELECT COUNT(*) AS total FROM estudiantes $where";
        $stmt = $this->conn->prepare($sqlCount);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        // data
        $sql = "SELECT * FROM estudiantes $where ORDER BY nombre_completo LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($row = $res->fetch_assoc()) { $data[] = $row; }
        $stmt->close();
        return ['data' => $data, 'total' => $total];
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM estudiantes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiante = $result->fetch_assoc();
        $stmt->close();
        return $estudiante;
    }

    public function getByNombreCi($nombre, $ci) {
        $stmt = $this->conn->prepare("SELECT * FROM estudiantes WHERE nombre_completo = ? AND ci = ? LIMIT 1");
        $stmt->bind_param("ss", $nombre, $ci);
        $stmt->execute();
        $result = $stmt->get_result();
        $estudiante = $result->fetch_assoc();
        $stmt->close();
        return $estudiante;
    }

    public function getByCurso($cursoId, $gestionId = null) {
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

    /** Crea estudiante + usuario (clave aleatoria de 6). Retorna ['id'=>..,'password'=>plain] o false. */
    public function crear($ci, $nombre, $matricula, $anioIngreso, $password = null) {
        $stmt = $this->conn->prepare("INSERT INTO estudiantes (ci, nombre_completo, matricula, anio_ingreso) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $ci, $nombre, $matricula, $anioIngreso);
        $result = $stmt->execute();
        $newId = $result ? $this->conn->insert_id : 0;
        $stmt->close();
        if (!$result || !$newId) return false;
        require_once __DIR__ . '/UsuariosModel.php';
        $u = new UsuariosModel();
        if ($u->existeUsername($ci)) return ['id' => $newId, 'password' => null];
        $plain = $u->crearParaEstudiante($newId, $ci, $password);
        if ($plain === false) return false;
        return ['id' => $newId, 'password' => $plain];
    }

    /** Resetea la clave del usuario vinculado. Retorna el texto plano o false. */
    public function resetearPassword($estudianteId) {
        require_once __DIR__ . '/UsuariosModel.php';
        $u = new UsuariosModel();
        $usr = $u->getByEstudianteId((int) $estudianteId);
        if (!$usr) return false;
        return $u->resetearPassword((int) $usr['id']);
    }

    public function actualizar($id, $ci, $nombre, $matricula, $anioIngreso, $estado) {
        $stmt = $this->conn->prepare("UPDATE estudiantes SET ci = ?, nombre_completo = ?, matricula = ?, anio_ingreso = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $ci, $nombre, $matricula, $anioIngreso, $estado, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function inscribirCurso($estudianteId, $cursoId, $gestionId, $semestre = 1) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO estudiantes_secciones (estudiante_id, curso_id, gestion_id, semestre) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $estudianteId, $cursoId, $gestionId, $semestre);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function reinscribir($estudianteId, $cursoId, $gestionId, $semestre = 1) {
        $stmt = $this->conn->prepare("UPDATE estudiantes_secciones SET curso_id = ?, semestre = ? WHERE estudiante_id = ? AND gestion_id = ?");
        $stmt->bind_param("iiii", $cursoId, $semestre, $estudianteId, $gestionId);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $stmt->close();
            return $this->inscribirCurso($estudianteId, $cursoId, $gestionId, $semestre);
        }
        $stmt->close();
        return true;
    }

    public function estaInscrito($estudianteId, $cursoId, $gestionId) {
        $stmt = $this->conn->prepare("SELECT 1 FROM estudiantes_secciones WHERE estudiante_id = ? AND curso_id = ? AND gestion_id = ? LIMIT 1");
        $stmt->bind_param("iii", $estudianteId, $cursoId, $gestionId);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    public function darDeBaja(int $estudianteId, int $cursoId, int $gestionId): bool {
        $stmt = $this->conn->prepare("DELETE FROM estudiantes_secciones WHERE estudiante_id = ? AND curso_id = ? AND gestion_id = ? LIMIT 1");
        $stmt->bind_param("iii", $estudianteId, $cursoId, $gestionId);
        $result = $stmt->execute();
        $stmt->close();
        return (bool) $result;
    }

    public function getHistorialPorEstudiante(int $estudianteId): array {
        $stmt = $this->conn->prepare("SELECT es.curso_id, es.gestion_id, c.anio_carrera, c.turno, c.paralelo, ca.nombre AS carrera_nombre, g.anio AS gestion_anio FROM estudiantes_secciones es JOIN cursos c ON c.id = es.curso_id JOIN carreras ca ON ca.id = c.carrera_id JOIN gestiones g ON g.id = es.gestion_id WHERE es.estudiante_id = ? ORDER BY g.anio DESC");
        $stmt->bind_param("i", $estudianteId);
        $stmt->execute();
        $res = $stmt->get_result();
        $historial = [];
        while ($r = $res->fetch_assoc()) $historial[] = $r;
        $stmt->close();
        return $historial;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM estudiantes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
