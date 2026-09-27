<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';

$model = new EstudiantesModel();
$materias = $model->getMaterias($_SESSION['est_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Mis Materias</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_menu.css">
    <script defer src="<?= BASE_URL ?>/js/script_menu.js"></script>
    </head>
<body>
    <canvas id="canvas" aria-hidden="true"></canvas>
<div class="app">
<header class="topbar">
  <button class="icon-btn" id="btnSide" aria-label="Menu"><svg class="ic"><use href="#i-menu"/></svg></button>
  <a class="brand" href="<?= BASE_URL ?>/view/estudiante/dashboard.php"><img src="<?= BASE_URL ?>/view/img/escudo.jpg" class="brand-logo" alt="Escudo"><span class="brand-name">Instituto Tecnologico <strong>PACCIOLI</strong></span></a>
  <div class="topbar-center"><div class="search"><input id="q" type="search" placeholder="Buscar…"><kbd>/</kbd></div></div>
  <div class="topbar-right"><span class="user-chip"><span class="avatar">E</span><span class="user-tx"><b><?php echo htmlspecialchars($_SESSION['est_name']); ?></b><span>Estudiante</span></span></span></div>
</header>
<div class="shell">
<?php include __DIR__ . '/../../includes/menu_estudiante.php'; ?>
<main class="main">
        <h1>Mis Materias</h1>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Carrera</th><th>Materia</th><th>Codigo</th><th>Curso</th><th>Anio</th><th>Paralelo</th><th>Docente</th></tr></thead>
                <tbody>
                    <?php if (empty($materias)): ?>
                        <tr><td colspan="7" style="text-align:center;">No tiene materias asignadas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($materias as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['carrera']); ?></td>
                            <td><?php echo htmlspecialchars($m['materia']); ?></td>
                            <td><?php echo htmlspecialchars($m['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($m['curso']); ?></td>
                            <td><?php echo $m['anio_carrera']; ?></td>
                            <td><?php echo $m['paralelo']; ?></td>
                            <td><?php echo htmlspecialchars($m['docente']); ?></td>
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
