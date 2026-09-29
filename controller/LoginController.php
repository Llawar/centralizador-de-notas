<?php
session_start();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../model/UsuariosModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        redirect("/index.php?error=complete");
        exit;
    }

    $uModel = new UsuariosModel();
    $usuario = $uModel->login($username, $password);
    if (!$usuario) {
        redirect("/index.php?error=invalid");
        exit;
    }

    // Docentes y estudiantes entran solo con C.I. (numérico) + contraseña.
    if (in_array($usuario['rol'] ?? '', ['docente', 'estudiante'], true) && !ctype_digit($username)) {
        redirect("/index.php?error=invalid");
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['username'] = $usuario['username'];
    $_SESSION['rol'] = $usuario['rol'];

    if ($usuario['rol'] === 'admin') {
        redirect("/view/admin/dashboard.php");
        exit;
    }
    if ($usuario['rol'] === 'docente') {
        $_SESSION['docente_id'] = $usuario['docente_id'];
        redirect("/view/docente/dashboard.php");
        exit;
    }
    if ($usuario['rol'] === 'estudiante') {
        $_SESSION['est_id'] = $usuario['estudiante_id'];
        redirect("/view/estudiante/dashboard.php");
        exit;
    }

    redirect("/index.php?error=invalid");
    exit;
}
