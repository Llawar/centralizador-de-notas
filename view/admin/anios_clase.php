<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
$conn = getConnection();
$result = $conn->query("SELECT DISTINCT gestion FROM cursos ORDER BY gestion DESC");
$anios = [];
while ($row = $result->fetch_assoc()) { $anios[] = $row['gestion']; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Anios de Clase</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_menu.css">
    <script defer src="<?= BASE_URL ?>/js/script_menu.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</head>
<body>
    <canvas id="canvas"></canvas>
    <?php include __DIR__ . '/../../includes/menu_admin.php'; ?>

    <div class="top-header">
        <div class="logo-area">
            <button id="sidebar-toggle" type="button" title="Desplegar o contraer el menu" aria-label="Desplegar o contraer el menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
            <img src="<?= BASE_URL ?>/view/img/escudo.jpg" alt="Logo"><span>Instituto Tecnologico PACCIOLI</span>
        </div>
        <div class="user-area">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <button id="modo-btn" title="Cambiar modo">🌙</button>
        </div>
    </div>
    <div class="container">
        <h1>Anios de Clase</h1>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Gestion</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($anios as $a): ?>
                    <tr>
                        <td><?php echo (int) $a; ?></td>
                        <td><a href="<?= BASE_URL ?>/view/admin/gestion_cursos.php?gestion=<?php echo (int) $a; ?>" class="btn btn-primary">Ver Cursos</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
