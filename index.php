<?php
session_start();
require_once __DIR__ . '/config/app.php';

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['rol']) {
        case 'admin':
            redirect("/view/admin/dashboard.php");
            break;
        case 'docente':
            redirect("/view/docente/dashboard.php");
            break;
        case 'estudiante':
            redirect("/view/estudiante/dashboard.php");
            break;
    }
    exit;
}

redirect("/login.php");
exit;
