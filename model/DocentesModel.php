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
        while ($row = $result->fetch_assoc()) {
            $docentes[] = $row;
        }
        return $docentes;
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

    public function crear($ci, $nombre, $email, $telefono, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO docentes (ci, nombre_completo, email, telefono, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ci, $nombre, $email, $telefono, $hash);
        $result = $stmt->execute();
        $id = $this->conn->insert_id;
        $stmt->close();

        if ($result) {
            require_once __DIR__ . '/UsuariosModel.php';
            $uModel = new UsuariosModel();
            $username = $uModel->generarUsername($nombre);
            $uModel->crearUsuarioPara($username, $password, 'docente', $id);
        }
        return $result;
    }

    public function actualizar($id, $ci, $nombre, $email, $telefono, $estado) {
        $stmt = $this->conn->prepare("UPDATE docentes SET ci = ?, nombre_completo = ?, email = ?, telefono = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $ci, $nombre, $email, $telefono, $estado, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM docentes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE rol = 'docente' AND referer_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        return $result;
    }

    public function getMaterias($docenteId) {
        $sql = "SELECT m.nombre AS materia, m.codigo, c.nombre AS curso, c.anio, c.paralelo, c.gestion, c.semestre, c.id AS curso_id, m.id AS materia_id,
                       ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo, ca.duracion AS carrera_duracion
                FROM docente_materia_curso dmc
                JOIN materias m ON dmc.materia_id = m.id
                JOIN cursos c ON dmc.curso_id = c.id
                JOIN carreras ca ON ca.id = c.carrera_id
                WHERE dmc.docente_id = ?
                ORDER BY c.anio, c.paralelo, m.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $docenteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $stmt->close();
        return $materias;
    }

}
