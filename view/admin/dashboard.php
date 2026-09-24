<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';
$carrerasModel=new CarrerasModel(); $docentesModel=new DocentesModel(); $estudiantesModel=new EstudiantesModel(); $cursosModel=new CursosModel();
$totalCarreras=count($carrerasModel->getAll());
$totalDocentes=count($docentesModel->getAll());
$totalEstudiantes=count($estudiantesModel->getAll());
$totalCursos=count($cursosModel->getAll());
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Administrativo · PACCIOLI</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_menu.css">
<script defer src="<?= BASE_URL ?>/js/script_menu.js"></script>
</head>
<body>
<canvas id="canvas" aria-hidden="true"></canvas>
<div class="app">
<header class="topbar">
  <button class="icon-btn" id="btnSide" aria-label="Alternar menú"><svg class="ic"><use href="#i-menu"/></svg></button>
  <a class="brand" href="<?= BASE_URL ?>/view/admin/dashboard.php"><img src="<?= BASE_URL ?>/view/img/escudo.jpg" alt="Escudo" class="brand-logo"><span class="brand-name">Instituto Tecnológico <strong>PACCIOLI</strong></span></a>
  <div class="topbar-center"><div class="search"><input id="q" type="search" placeholder="Buscar estudiante, curso…"><kbd>/</kbd></div></div>
  <div class="topbar-right"><span class="user-chip"><span class="avatar">A</span><span class="user-tx"><b><?= htmlspecialchars($_SESSION['username']) ?></b><span>Administrador</span></span></span></div>
</header>
<div class="shell">
<?php include __DIR__ . '/../../includes/menu_admin.php'; ?>
<main class="main">
  <div class="page-head"><div><h1>Panel Administrativo</h1><p class="subtitle">Vista general del sistema · <?= date('d/m/Y') ?></p></div>
    <div class="head-actions"><a href="<?= BASE_URL ?>/view/admin/historial_academico.php" class="btn btn-ghost">Ver historial</a><a href="<?= BASE_URL ?>/view/admin/inscripciones.php" class="btn btn-primary">Nueva inscripción</a></div>
  </div>
  <section class="kpi-grid">
    <article class="kpi"><div class="kpi-top"><span class="chip t-blue"><svg class="ic ic-lg"><use href="#i-layers"/></svg></span><div><span class="kpi-num" data-count="<?= $totalCarreras ?>"><?= $totalCarreras ?></span><span class="kpi-label">Carreras</span></div></div><span class="kpi-foot">Oferta académica</span></article>
    <article class="kpi"><div class="kpi-top"><span class="chip t-sky"><svg class="ic ic-lg"><use href="#i-users"/></svg></span><div><span class="kpi-num" data-count="<?= $totalDocentes ?>"><?= $totalDocentes ?></span><span class="kpi-label">Docentes</span></div></div><span class="kpi-foot">Plantilla activa</span></article>
    <article class="kpi"><div class="kpi-top"><span class="chip t-deep"><svg class="ic ic-lg"><use href="#i-monitor"/></svg></span><div><span class="kpi-num" data-count="<?= $totalEstudiantes ?>"><?= $totalEstudiantes ?></span><span class="kpi-label">Estudiantes</span></div></div><span class="kpi-foot">Matriculados</span></article>
    <article class="kpi star"><div class="kpi-top"><span class="chip t-gold"><svg class="ic ic-lg"><use href="#i-clip"/></svg></span><div><span class="kpi-num gold" data-count="62" data-suffix="%">62%</span><span class="kpi-label">Carga de notas</span></div></div><div class="kpi-prog"><span class="track"><span class="fill f-gold" data-val="62" style="width:62%"></span></span><span class="meta"><span>Actas al día</span><span>meta 100%</span></span></div></article>
  </section>
  <div class="banner"><span class="chip t-gold banner-ic"><svg class="ic ic-lg"><use href="#i-clip"/></svg></span><div class="banner-tx"><p>Cierre de período: publica las actas pendientes</p><span>Acción institucional · Registro Pedagógico</span></div><a href="<?= BASE_URL ?>/view/admin/historial_academico.php" class="btn btn-gold">Publicar notas</a></div>
  <section class="dash-grid">
    <article class="card"><div class="card-head"><div><h2>Tareas pendientes</h2><span class="sub">Requieren tu acción</span></div><span class="pill">3 activas</span></div>
      <ul class="tasks"><li><span class="task-ic t-amber"><svg class="ic"><use href="#i-alert"/></svg></span><div class="task-tx"><p>Registro sin cerrar</p><span>Revisar materias con actas abiertas</span></div></li><li><span class="task-ic t-blue"><svg class="ic"><use href="#i-userplus"/></svg></span><div class="task-tx"><p>Inscripciones</p><span>Gestionar altas del período</span></div></li></ul>
    </article>
    <article class="card"><div class="card-head"><div><h2>Carga por curso</h2><span class="sub">Período actual</span></div></div>
      <div class="chart-body"><div class="bar-row"><div class="bar-head"><span>Informática 1A</span><b>85%</b></div><span class="track"><span class="fill f-green" data-val="85" style="width:85%"></span></span></div><div class="bar-row"><div class="bar-head"><span>Contaduría 1A</span><b class="warn">42%</b></div><span class="track"><span class="fill f-amber" data-val="42" style="width:42%"></span></span></div><div class="bar-row"><div class="bar-head"><span>Electromecánica 3A</span><b>91%</b></div><span class="track"><span class="fill f-green" data-val="91" style="width:91%"></span></span></div></div><div class="chart-foot">Actualizado hoy · datos del Registro</div>
    </article>
  </section>
  <div class="section-head"><h2>Accesos rápidos</h2></div>
  <section class="quick-grid">
    <a href="<?= BASE_URL ?>/view/admin/gestion_carreras.php" class="quick"><span class="q-chip t-blue"><svg class="ic ic-lg"><use href="#i-layers"/></svg></span><span class="q-name">Carreras</span><span class="q-desc">Gestionar oferta</span></a>
    <a href="<?= BASE_URL ?>/view/admin/gestion_docentes.php" class="quick"><span class="q-chip t-sky"><svg class="ic ic-lg"><use href="#i-users"/></svg></span><span class="q-name">Docentes</span><span class="q-desc">Alta y asignación</span></a>
    <a href="<?= BASE_URL ?>/view/admin/gestion_cursos.php" class="quick"><span class="q-chip t-deep"><svg class="ic ic-lg"><use href="#i-monitor"/></svg></span><span class="q-name">Cursos</span><span class="q-desc">Secciones y cupos</span></a>
    <a href="<?= BASE_URL ?>/view/admin/historial_academico.php" class="quick star"><span class="q-chip t-gold"><svg class="ic ic-lg"><use href="#i-clip"/></svg></span><span class="q-name">Historial</span><span class="q-desc">Consultar notas</span></a>
  </section>
  <footer class="foot"><b>Centralizador de Notas</b> · Instituto Tecnológico PACCIOLI</footer>
</main>
</div>
</div>
<script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
