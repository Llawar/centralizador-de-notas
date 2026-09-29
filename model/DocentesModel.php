<?php
require_once __DIR__ . '/../config/conexion.php';

class DocentesModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $result = $this->conn->query("SELECT * FROM docentes ORDER BY nombre_completo");
        $docentes = [];
        while ($row = $result->fetch_assoc()) { $docentes[] = $row; }
        return $docentes;
    }

    /** Devuelve ['data'=>[], 'total'=>int] con paginación y búsqueda opcional */
    public function getAllPaginated(int $limit, int $offset, string $search = ''): array {
        $where = '';
        $params = [];
        $types = '';
        if ($search !== '') {
            $where = "WHERE nombre_completo LIKE ? OR ci LIKE ?";
            $like = "%$search%";
            $params = [$like, $like];
            $types = 'ss';
        }
        $sqlCount = "SELECT COUNT(*) AS total FROM docentes $where";
        $stmt = $this->conn->prepare($sqlCount);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        $sql = "SELECT * FROM docentes $where ORDER BY nombre_completo LIMIT ? OFFSET ?";
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
        $stmt = $this->conn->prepare("SELECT * FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $docente = $result->fetch_assoc();
        $stmt->close();
        return $docente;
    }

    public function getByNombreCi($nombre, $ci) {
        $stmt = $this->conn->prepare("SELECT * FROM docentes WHERE nombre_completo = ? AND ci = ? LIMIT 1");
        $stmt->bind_param("ss", $nombre, $ci);
        $stmt->execute();
        $result = $stmt->get_result();
        $docente = $result->fetch_assoc();
        $stmt->close();
        return $docente;
    }

    /** Crea docente + usuario (clave aleatoria de 6). Retorna ['id'=>..,'password'=>plain] o false. */
    public function crear($ci, $nombre, $anioIngreso, $password = null) {
        $stmt = $this->conn->prepare("INSERT INTO docentes (ci, nombre_completo, anio_ingreso) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $ci, $nombre, $anioIngreso);
        $result = $stmt->execute();
        $newId = $result ? $this->conn->insert_id : 0;
        $stmt->close();
        if (!$result || !$newId) return false;
        require_once __DIR__ . '/UsuariosModel.php';
        $u = new UsuariosModel();
        if ($u->existeUsername($ci)) return ['id' => $newId, 'password' => null];
        $plain = $u->crearParaDocente($newId, $ci, $password);
        if ($plain === false) return false;
        return ['id' => $newId, 'password' => $plain];
    }

    /** Resetea la clave del usuario vinculado. Retorna el texto plano o false. */
    public function resetearPassword($docenteId) {
        require_once __DIR__ . '/UsuariosModel.php';
        $u = new UsuariosModel();
        $usr = $u->getByDocenteId((int) $docenteId);
        if (!$usr) return false;
        return $u->resetearPassword((int) $usr['id']);
    }

    public function actualizar($id, $ci, $nombre, $anioIngreso, $estado) {
        $stmt = $this->conn->prepare("UPDATE docentes SET ci = ?, nombre_completo = ?, anio_ingreso = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $ci, $nombre, $anioIngreso, $estado, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getMaterias($docenteId, $gestionId = null) {
        if ($gestionId === null) {
            $g = $this->conn->query("SELECT id FROM gestiones WHERE estado='abierta' LIMIT 1");
            $row = $g ? $g->fetch_assoc() : null;
            $gestionId = $row ? (int) $row['id'] : 0;
        }
        $sql = "SELECT m.nombre AS materia, m.codigo, c.nombre AS curso, c.anio_carrera, c.turno, c.paralelo,
                       ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo,
                       c.id AS curso_id, m.id AS materia_id
                FROM docente_materia_seccion dms
                JOIN materias m ON dms.materia_id = m.id
                JOIN cursos c ON dms.curso_id = c.id
                JOIN carreras ca ON ca.id = c.carrera_id
                WHERE dms.docente_id = ? AND dms.gestion_id = ?
                ORDER BY c.anio_carrera, c.paralelo, m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $docenteId, $gestionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) { $materias[] = $row; }
        $stmt->close();
        return $materias;
    }
}
