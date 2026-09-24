<?php
require_once __DIR__ . '/../config/app.php';
// $titulo opcional definido por la vista antes de incluir
$__titulo = $titulo ?? 'PACCIOLI';
$__rol = $_SESSION['rol'] ?? 'admin';
// Etiqueta legible del rol
$__rolLabel = match ($__rol) {
    'admin' => 'Administrador',
    'docente' => 'Docente',
    'estudiante' => 'Estudiante',
    default => ucfirst($__rol),
};
$__username = $_SESSION['username'] ?? '';
$__initial = strtoupper(mb_substr($__username ?: $__rolLabel, 0, 1, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($__titulo, ENT_QUOTES) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_menu.css">
    <script defer src="<?= BASE_URL ?>/js/script_menu.js"></script>
</head>
<body>
    <canvas id="canvas" aria-hidden="true"></canvas>
<div class="app">
<header class="topbar">
  <button class="icon-btn" id="btnSide" aria-label="Menu"><svg class="ic"><use href="#i-menu"/></svg></button>
  <a class="brand" href="<?= BASE_URL ?>/view/<?= htmlspecialchars($__rol, ENT_QUOTES) ?>/dashboard.php"><img src="<?= BASE_URL ?>/view/img/escudo.jpg" class="brand-logo" alt="Escudo"><span class="brand-name">Instituto Tecnologico <strong>PACCIOLI</strong></span></a>
  <div class="topbar-center"><div class="search"><input id="q" type="search" placeholder="Buscar…"><kbd>/</kbd></div></div>
  <div class="topbar-right"><span class="user-chip"><span class="avatar"><?= htmlspecialchars($__initial, ENT_QUOTES) ?></span><span class="user-tx"><b><?= htmlspecialchars($__username, ENT_QUOTES) ?></b><span><?= htmlspecialchars($__rolLabel, ENT_QUOTES) ?></span></span></span></div>
</header>
<div class="shell">
<?php
$__menuFile = __DIR__ . '/menu_' . $__rol . '.php';
if (is_file($__menuFile)) {
    include $__menuFile;
}
?>
<main class="main">
