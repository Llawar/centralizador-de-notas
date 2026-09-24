<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/NotasModel.php';

$notasModel = new NotasModel();
$notas = $notasModel->getResumenNotas($_SESSION['est_id']);

// Agrupar notas por materia
$porMateria = [];
foreach ($notas as $n) {
    $key = $n['materia'];
    if (!isset($porMateria[$key])) {
        $porMateria[$key] = ['codigo' => $n['codigo'], 'notas' => []];
    }
    $porMateria[$key]['notas'][] = $n;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Mis Notas</title>
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
        <h1>Mis Notas</h1>

        <?php if (empty($porMateria)): ?>
            <div class="alert alert-info">No tiene notas registradas aun.</div>
        <?php else: ?>
            <?php foreach ($porMateria as $materia => $data): ?>
            <h2 style="text-align:left; margin-top:25px;"><?php echo htmlspecialchars($materia); ?> <small style="color:rgba(255,255,255,0.5);">(<?php echo $data['codigo']; ?>)</small></h2>
            <div class="tabla-contenedor">
                <table>
                    <thead><tr><th>Tipo</th><th>Actividad</th><th>Nota</th><th>Gestion</th></tr></thead>
                    <tbody>
                        <?php foreach ($data['notas'] as $n): ?>
                        <tr>
                            <td><?php echo ucfirst($n['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($n['nombre_actividad']); ?></td>
                            <td><strong><?php echo number_format($n['nota'], 1); ?></strong></td>
                            <td><?php echo $n['gestion']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    </main>
</div>
</div>
<script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
