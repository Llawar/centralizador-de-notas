<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/AsistenciaModel.php';

$estudiantesModel = new EstudiantesModel();
$asistenciaModel = new AsistenciaModel();

$materias = $estudiantesModel->getMaterias($_SESSION['est_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Mi Asistencia</title>
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
        <h1>Mi Asistencia</h1>

        <?php foreach ($materias as $m):
            $resumen = $asistenciaModel->getResumenAsistencia($_SESSION['est_id'], $m['curso_id'], $m['materia_id']);
        ?>
        <h2 style="text-align:left; margin-top:25px;"><?php echo htmlspecialchars($m['materia']); ?></h2>
        <div class="cards-grid">
            <div class="card"><svg class="ic c-ok"><use href="#i-check"/></svg><h3><?php echo $resumen['presente']; ?></h3><p>Presente</p></div>
            <div class="card"><svg class="ic c-bad"><use href="#i-alert"/></svg><h3><?php echo $resumen['ausente']; ?></h3><p>Ausente</p></div>
            <div class="card"><svg class="ic c-warn"><use href="#i-alert"/></svg><h3><?php echo $resumen['justificado']; ?></h3><p>Justificado</p></div>
            <div class="card"><svg class="ic c-info"><use href="#i-trend"/></svg><h3><?php echo $resumen['porcentaje']; ?>%</h3><p>Porcentaje</p></div>
        </div>
        <?php endforeach; ?>
    </div>
    </main>
</div>
</div>
<script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
