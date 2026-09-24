<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/DocentesModel.php';

$model = new DocentesModel();
$materias = $model->getMaterias($_SESSION['docente_id']);
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
  <a class="brand" href="<?= BASE_URL ?>/view/docente/dashboard.php"><img src="<?= BASE_URL ?>/view/img/escudo.jpg" class="brand-logo" alt="Escudo"><span class="brand-name">Instituto Tecnologico <strong>PACCIOLI</strong></span></a>
  <div class="topbar-center"><div class="search"><input id="q" type="search" placeholder="Buscar…"><kbd>/</kbd></div></div>
  <div class="topbar-right"><span class="user-chip"><span class="avatar">D</span><span class="user-tx"><b><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></b><span>Docente</span></span></span></div>
</header>
<div class="shell">
<?php include __DIR__ . '/../../includes/menu_docente.php'; ?>
<main class="main">
        <h1>Mis Materias</h1>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Materia</th><th>Codigo</th><th>Curso</th><th>Anio</th><th>Paralelo</th><th>Gestion</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($materias as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['materia']); ?></td>
                        <td><?php echo htmlspecialchars($m['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($m['curso']); ?></td>
                        <td><?php echo $m['anio']; ?></td>
                        <td><?php echo $m['paralelo']; ?></td>
                        <td><?php echo $m['gestion']; ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/view/docente/ver_estudiantes.php?curso_id=<?php echo $m['curso_id']; ?>&materia_id=<?php echo $m['materia_id']; ?>" class="btn btn-primary">Estudiantes</a>
                            <a href="<?= BASE_URL ?>/view/docente/registro_pedagogico.php?curso_id=<?php echo $m['curso_id']; ?>&materia_id=<?php echo $m['materia_id']; ?>" class="btn btn-success">Registro</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
