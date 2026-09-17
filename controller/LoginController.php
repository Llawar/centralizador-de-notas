<?php
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../model/UsuariosModel.php';
require_once __DIR__ . '/../model/DocentesModel.php';
require_once __DIR__ . '/../model/EstudiantesModel.php';

$usuariosModel = new UsuariosModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        redirect("/index.php?error=complete");
        exit;
    }

    $usuario = $usuariosModel->login($username, $password);

    if ($usuario) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['referer_id'] = $usuario['referer_id'];

        switch ($usuario['rol']) {
            case 'admin':
                redirect("/view/admin/dashboard.php");
                exit;
            case 'docente':
                $model = new \DocentesModel();
                $docente = $model->getById($usuario['referer_id']);
                if (!$docente) {
                    session_destroy();
                    redirect("/login.php?error=invalid");
                    exit;
                }
                $_SESSION['docente_id'] = $docente['id'];
                $_SESSION['nombre_completo'] = $docente['nombre_completo'];
                redirect("/view/docente/dashboard.php");
                exit;
            case 'estudiante':
                $model = new \EstudiantesModel();
                $estudiante = $model->getById($usuario['referer_id']);
                if (!$estudiante) {
                    session_destroy();
                    redirect("/login.php?error=invalid");
                    exit;
                }
                $_SESSION['est_id'] = $estudiante['id'];
                $_SESSION['est_name'] = $estudiante['nombre_completo'];
                redirect("/view/estudiante/dashboard.php");
                exit;
        }
        exit;
    } else {
        redirect("/index.php?error=invalid");
        exit;
    }
}
