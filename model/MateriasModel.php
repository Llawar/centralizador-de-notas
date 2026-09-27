<?php
require_once __DIR__ . '/../config/conexion.php';

class MateriasModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $sql = "SELECT m.*, c.nombre AS carrera_nombre FROM materias m
                JOIN carreras c ON m.carrera_id = c.id
                ORDER BY c.nombre, m.anio_carrera, m.nombre";
        $result = $this->conn->query($sql);
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        return $materias;
    }

    public function getByCarrera($carreraId, $anio = null) {
        if ($anio !== null) {
            $stmt = $this->conn->prepare("SELECT * FROM materias WHERE carrera_id = ? AND anio_carrera = ? ORDER BY nombre");
            $stmt->bind_param("ii", $carreraId, $anio);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM materias WHERE carrera_id = ? ORDER BY anio_carrera, nombre");
            $stmt->bind_param("i", $carreraId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $materias = [];
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $stmt->close();
        return $materias;
    }

    public function getByAnio($carreraId, $anio) {
        return $this->getByCarrera($carreraId, $anio);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM materias WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $materia = $result->fetch_assoc();
        $stmt->close();
        return $materia;
    }

    public function crear($nombre, $codigo, $carreraId, $anioCarrera = 1) {
        $anioCarrera = max(1, min(3, (int) $anioCarrera));
        $stmt = $this->conn->prepare("INSERT INTO materias (nombre, codigo, carrera_id, anio_carrera) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $nombre, $codigo, $carreraId, $anioCarrera);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function actualizar($id, $nombre, $codigo, $carreraId, $anioCarrera = 1) {
        $anioCarrera = max(1, min(3, (int) $anioCarrera));
        $stmt = $this->conn->prepare("UPDATE materias SET nombre = ?, codigo = ?, carrera_id = ?, anio_carrera = ? WHERE id = ?");
        $stmt->bind_param("ssiii", $nombre, $codigo, $carreraId, $anioCarrera, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM materias WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
