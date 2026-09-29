<?php
require_once __DIR__ . '/../config/conexion.php';

class UsuariosModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE username = ? AND estado = 'activo' LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();

        if ($usuario && password_verify($password, $usuario['password'])) {
            return $usuario;
        }
        return null;
    }

    public function getUsuarioById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        return $usuario;
    }

    public function getAll() {
        $sql = "SELECT u.*, d.nombre_completo AS docente_nombre, d.ci AS docente_ci,
                       e.nombre_completo AS estudiante_nombre, e.ci AS estudiante_ci,
                       d.anio_ingreso AS docente_anio,
                       e.matricula AS estudiante_matricula, e.anio_ingreso AS estudiante_anio
                FROM usuarios u
                LEFT JOIN docentes d ON d.id = u.docente_id
                LEFT JOIN estudiantes e ON e.id = u.estudiante_id
                ORDER BY u.rol, u.username";
        $result = $this->conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) { $rows[] = $row; }
        return $rows;
    }

    /** Devuelve ['data'=>[], 'total'=>int] con paginación y búsqueda opcional */
    public function getAllPaginated(int $limit, int $offset, string $search = ''): array {
        $where = '';
        $params = [];
        $types = '';
        if ($search !== '') {
            $where = "WHERE u.username LIKE ? OR d.nombre_completo LIKE ? OR d.ci LIKE ? OR e.nombre_completo LIKE ? OR e.ci LIKE ?";
            $like = "%$search%";
            $params = [$like, $like, $like, $like, $like];
            $types = 'sssss';
        }
        $sqlCount = "SELECT COUNT(*) AS total FROM usuarios u
                     LEFT JOIN docentes d ON d.id = u.docente_id
                     LEFT JOIN estudiantes e ON e.id = u.estudiante_id $where";
        $stmt = $this->conn->prepare($sqlCount);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        $sql = "SELECT u.*, d.nombre_completo AS docente_nombre, d.ci AS docente_ci,
                       e.nombre_completo AS estudiante_nombre, e.ci AS estudiante_ci,
                       d.anio_ingreso AS docente_anio,
                       e.matricula AS estudiante_matricula, e.anio_ingreso AS estudiante_anio
                FROM usuarios u
                LEFT JOIN docentes d ON d.id = u.docente_id
                LEFT JOIN estudiantes e ON e.id = u.estudiante_id $where
                ORDER BY u.rol, u.username LIMIT ? OFFSET ?";
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

    public function crear($username, $password, $rol = 'admin', $docenteId = null, $estudianteId = null) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO usuarios (username, password, rol, docente_id, estudiante_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $username, $hash, $rol, $docenteId, $estudianteId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function crearParaDocente($docenteId, $username, $plainPass = null) {
        require_once __DIR__ . '/../includes/passwords.php';
        if ($plainPass === null || $plainPass === '') $plainPass = generar_password(6);
        $ok = $this->crear($username, $plainPass, 'docente', (int) $docenteId, null);
        return $ok ? $plainPass : false;
    }

    public function crearParaEstudiante($estudianteId, $username, $plainPass = null) {
        require_once __DIR__ . '/../includes/passwords.php';
        if ($plainPass === null || $plainPass === '') $plainPass = generar_password(6);
        $ok = $this->crear($username, $plainPass, 'estudiante', null, (int) $estudianteId);
        return $ok ? $plainPass : false;
    }

    public function getByDocenteId($docenteId) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE docente_id = ? LIMIT 1");
        $stmt->bind_param("i", $docenteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $u = $result->fetch_assoc();
        $stmt->close();
        return $u;
    }

    public function getByEstudianteId($estudianteId) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE estudiante_id = ? LIMIT 1");
        $stmt->bind_param("i", $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $u = $result->fetch_assoc();
        $stmt->close();
        return $u;
    }

    /** Genera nueva clave aleatoria para un usuario. Retorna el texto plano o false. */
    public function resetearPassword($usuarioId) {
        require_once __DIR__ . '/../includes/passwords.php';
        $nueva = generar_password(6);
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $usuarioId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? $nueva : false;
    }

    public function actualizar($id, $username, $estado) {
        $stmt = $this->conn->prepare("UPDATE usuarios SET username = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $estado, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function crearUsuario($username, $password, $rol, $referer_id = 0) {
        return $this->crear($username, $password, $rol);
    }

    public function cambiarPassword($id, $nuevaPassword) {
        $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function generarUsername(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre));
        $partes = array_values(array_filter($partes, function ($p) {
            return !preg_match('/^(ing|lic|dra|dr|mgr|mba)\.?$/i', $p);
        }));
        $base = strtolower($partes[0] ?? 'usuario');
        if (isset($partes[1])) {
            $base .= '.' . strtolower($partes[1]);
        }
        $base = preg_replace('/[^a-z0-9\.]/', '', $base);
        if ($base === '' || $base === '.') $base = 'usuario';
        $username = $base;
        $i = 1;
        while ($this->existeUsername($username)) {
            $i++;
            $username = $base . $i;
        }
        return $username;
    }

    public function existeUsername(string $u): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $u);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    public function crearUsuarioPara(string $username, string $plainPass, string $rol, int $refererId): bool
    {
        if ($rol === 'docente') return (bool) $this->crearParaDocente($refererId, $username, $plainPass);
        if ($rol === 'estudiante') return (bool) $this->crearParaEstudiante($refererId, $username, $plainPass);
        return (bool) $this->crear($username, $plainPass, 'admin');
    }
}
