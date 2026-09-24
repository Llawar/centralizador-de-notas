<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
    redirect("/index.php?error=session");
    exit;
}

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';

$cursoId   = isset($_GET['curso_id'])  ? (int) $_GET['curso_id']  : 0;
$materiaId = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : 0;
$docenteId = (int) $_SESSION['referer_id'];

$cursosModel    = new CursosModel();
$estudiantesModel = new EstudiantesModel();

if (!$cursoId || !$materiaId || !$cursosModel->esDocenteAsignado($docenteId, $materiaId, $cursoId)) {
    redirect("/view/docente/dashboard.php?error=forbidden");
    exit;
}

$curso     = $cursosModel->getById($cursoId);
$estudiantes = $estudiantesModel->getByCurso($cursoId);

$materia = null;
$stmt = $conn->prepare("SELECT * FROM materias WHERE id = ?");
$stmt->bind_param("i", $materiaId);
$stmt->execute();
$materia = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Estudiantes</title>
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
        <h1>Estudiantes - <?php echo htmlspecialchars($materia['nombre'] ?? ''); ?></h1>
        <h2>Curso: <?php echo htmlspecialchars($curso['nombre'] ?? ''); ?> | Gestion: <?php echo (int) ($curso['gestion'] ?? 0); ?> | Paralelo: <?php echo htmlspecialchars($curso['paralelo'] ?? ''); ?></h2>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr><th>#</th><th>Nombre Completo</th><th>C.I.</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($estudiantes)): ?>
                        <tr><td colspan="3" style="text-align:center;">No hay estudiantes inscritos.</td></tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($estudiantes as $e): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($e['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($e['ci']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="<?= BASE_URL ?>/view/docente/dashboard.php" class="btn btn-secondary"><svg class="ic ic-flip"><use href="#i-arrow"/></svg> Volver</a>
    </div>
    </main>
</div>
</div>
<script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
