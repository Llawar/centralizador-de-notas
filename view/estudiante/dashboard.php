<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/NotasModel.php';
$estudiantesModel=new EstudiantesModel(); $notasModel=new NotasModel();
$estudianteId=$_SESSION['est_id']; $materias=$estudiantesModel->getMaterias($estudianteId); $notas=$notasModel->getResumenNotas($estudianteId);
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Panel Estudiante · PACCIOLI</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_menu.css"><script defer src="<?= BASE_URL ?>/js/script_menu.js"></script></head>
<body><canvas id="canvas" aria-hidden="true"></canvas>
<div class="app">
<header class="topbar"><button class="icon-btn" id="btnSide" aria-label="Menú"><svg class="ic"><use href="#i-menu"/></svg></button><a class="brand" href="<?= BASE_URL ?>/view/estudiante/dashboard.php"><img src="<?= BASE_URL ?>/view/img/escudo.jpg" class="brand-logo" alt="Escudo"><span class="brand-name">Instituto Tecnológico <strong>PACCIOLI</strong></span></a><div class="topbar-center"><div class="search"><input id="q" type="search" placeholder="Buscar materia…"><kbd>/</kbd></div></div><div class="topbar-right"><span class="user-chip"><span class="avatar">E</span><span class="user-tx"><b><?= htmlspecialchars($_SESSION['est_name']) ?></b><span>Estudiante</span></span></span></div></header>
<div class="shell"><?php include __DIR__ . '/../../includes/menu_estudiante.php'; ?>
<main class="main">
  <div class="page-head"><div><h1>Mi Panel</h1><p class="subtitle"><?= htmlspecialchars($_SESSION['est_name']) ?> · <?= date('d/m/Y') ?></p></div><div class="head-actions"><a href="<?= BASE_URL ?>/view/estudiante/ver_notas.php" class="btn btn-primary">Ver notas</a></div></div>
  <section class="kpi-grid">
    <article class="kpi"><div class="kpi-top"><span class="chip t-blue"><svg class="ic ic-lg"><use href="#i-book"/></svg></span><div><span class="kpi-num"><?= count($materias) ?></span><span class="kpi-label">Materias</span></div></div><span class="kpi-foot">Inscritas este período</span></article>
    <article class="kpi star"><div class="kpi-top"><span class="chip t-gold"><svg class="ic ic-lg"><use href="#i-clip"/></svg></span><div><span class="kpi-num gold"><?= count($notas) ?></span><span class="kpi-label">Notas registradas</span></div></div><span class="kpi-foot">Historial parcial</span></article>
  </section>
  <section class="dash-grid">
    <article class="card"><div class="card-head"><div><h2>Mis Materias</h2><span class="sub">Cursos actuales</span></div><a href="<?= BASE_URL ?>/view/estudiante/ver_materias.php" class="link">Ver todas →</a></div>
      <div class="tabla-contenedor" style="margin:0;max-width:none;border:none;border-radius:0"><table><thead><tr><th>Materia</th><th>Curso</th><th>Docente</th></tr></thead><tbody>
        <?php foreach(array_slice($materias,0,5) as $m): ?><tr><td><?= htmlspecialchars($m['materia']) ?></td><td><?= htmlspecialchars($m['curso']." ".$m['paralelo']) ?></td><td><?= htmlspecialchars($m['docente']) ?></td></tr><?php endforeach; ?>
        <?php if(empty($materias)): ?><tr><td colspan="3" style="text-align:center;color:var(--text-3)">Sin materias</td></tr><?php endif; ?>
      </tbody></table></div>
    </article>
    <article class="card"><div class="card-head"><div><h2>Accesos rápidos</h2><span class="sub">Consulta</span></div></div><div class="quick-grid" style="padding:16px;grid-template-columns:1fr 1fr"><a href="<?= BASE_URL ?>/view/estudiante/ver_notas.php" class="quick star"><span class="q-chip t-gold"><svg class="ic ic-lg"><use href="#i-clip"/></svg></span><span class="q-name">Mis Notas</span><span class="q-desc">Por parcial</span></a><a href="<?= BASE_URL ?>/view/estudiante/ver_asistencia.php" class="quick"><span class="q-chip t-sky"><svg class="ic ic-lg"><use href="#i-cal"/></svg></span><span class="q-name">Asistencia</span><span class="q-desc">Detalle</span></a></div></article>
  </section>
  <footer class="foot"><b>Centralizador de Notas</b> · PACCIOLI</footer>
</main>
</div>
</div>
<script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
