<?php
require_once __DIR__ . '/../config/conexion.php';

class UsuariosModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE username = ? AND estado = 'activo'");
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

    public function crearUsuario($username, $password, $rol, $referer_id) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO usuarios (username, password, rol, referer_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $hash, $rol, $referer_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
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
        $hash = password_hash($plainPass, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO usuarios (username, password, rol, referer_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $hash, $rol, $refererId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
