<?php
require_once __DIR__ . '/../config/conexion.php';

class GestionesModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function getAll() {
        $result = $this->conn->query("SELECT * FROM gestiones ORDER BY anio DESC");
        $rows = [];
        while ($row = $result->fetch_assoc()) { $rows[] = $row; }
        return $rows;
    }

    public function getAbierta() {
        $result = $this->conn->query("SELECT * FROM gestiones WHERE estado='abierta' LIMIT 1");
        return $result ? $result->fetch_assoc() : null;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM gestiones WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function crear($anio, $fechaInicio = null, $fechaFin = null) {
        $stmt = $this->conn->prepare("INSERT INTO gestiones (anio, estado, fecha_inicio, fecha_fin) VALUES (?, 'cerrada', ?, ?)");
        $stmt->bind_param("sss", $anio, $fechaInicio, $fechaFin);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function abrir($id) {
        $this->conn->query("UPDATE gestiones SET estado='cerrada' WHERE estado='abierta'");
        $stmt = $this->conn->prepare("UPDATE gestiones SET estado='abierta' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function cerrar($id) {
        $stmt = $this->conn->prepare("UPDATE gestiones SET estado='cerrada' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function eliminar($id) {
        $stmt = $this->conn->prepare("DELETE FROM gestiones WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
