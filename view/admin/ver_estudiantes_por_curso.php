<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';

$cursosModel = new CursosModel();
$estudiantesModel = new EstudiantesModel();

$cursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : null;
$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : null;

$curso = $cursoId ? $cursosModel->getById($cursoId) : null;
$estudiantes = ($cursoId && $anio) ? $estudiantesModel->getByCurso($cursoId) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Estudiantes por Curso</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_menu.css">
    <script defer src="<?= BASE_URL ?>/js/script_menu.js"></script>
    </head>
<body>
    <canvas id="canvas" aria-hidden="true"></canvas>
<div class="app">
<header class="topbar">
  <button class="icon-btn" id="btnSide" aria-label="Menu"><svg class="ic"><use href="#i-menu"/></svg></button>
  <a class="brand" href="<?= BASE_URL ?>/view/admin/dashboard.php"><img src="<?= BASE_URL ?>/view/img/escudo.jpg" class="brand-logo" alt="Escudo"><span class="brand-name">Instituto Tecnologico <strong>PACCIOLI</strong></span></a>
  <div class="topbar-center"><div class="search"><input id="q" type="search" placeholder="Buscar…"><kbd>/</kbd></div></div>
  <div class="topbar-right"><span class="user-chip"><span class="avatar">A</span><span class="user-tx"><b><?php echo htmlspecialchars($_SESSION['username']); ?></b><span>Administrador</span></span></span></div>
</header>
<div class="shell">
<?php include __DIR__ . '/../../includes/menu_admin.php'; ?>
<main class="main">
        <h1>Estudiantes - <?php echo htmlspecialchars($curso['nombre'] ?? ''); ?> (<?php echo htmlspecialchars($curso['carrera_nombre'] ?? ''); ?>)</h1>
        <h2>Gestion <?php echo $anio ? (int) $anio : ''; ?></h2>

        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Nombre Completo</th><th>C.I.</th><th>Matricula</th><th>Anio Ingreso</th></tr></thead>
                <tbody>
                    <?php if (empty($estudiantes)): ?>
                        <tr><td colspan="4" style="text-align:center;">No hay estudiantes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($estudiantes as $e): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($e['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($e['ci']); ?></td>
                            <td><?php echo htmlspecialchars($e['matricula']); ?></td>
                            <td><?php echo (int) $e['anio_ingreso']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </main>
</div>
</div>
<script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
