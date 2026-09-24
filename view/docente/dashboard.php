<?php
session_start();
require_once __DIR__ . '/../../config/app.php';
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') { redirect("/index.php?error=session"); exit; }
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
$model=new DocentesModel(); $materias=$model->getMaterias($_SESSION['docente_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Panel Docente · PACCIOLI</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_menu.css"><script defer src="<?= BASE_URL ?>/js/script_menu.js"></script></head>
<body><canvas id="canvas" aria-hidden="true"></canvas>
<div class="app">
<header class="topbar"><button class="icon-btn" id="btnSide" aria-label="Menú"><svg class="ic"><use href="#i-menu"/></svg></button><a class="brand" href="<?= BASE_URL ?>/view/docente/dashboard.php"><img src="<?= BASE_URL ?>/view/img/escudo.jpg" class="brand-logo" alt="Escudo"><span class="brand-name">Instituto Tecnológico <strong>PACCIOLI</strong></span></a><div class="topbar-center"><div class="search"><input id="q" type="search" placeholder="Buscar estudiante…"><kbd>/</kbd></div></div><div class="topbar-right"><span class="user-chip"><span class="avatar">D</span><span class="user-tx"><b><?= htmlspecialchars($_SESSION['nombre_completo']) ?></b><span>Docente</span></span></span></div></header>
<div class="shell"><?php include __DIR__ . '/../../includes/menu_docente.php'; ?>
<main class="main">
  <div class="page-head"><div><h1>Panel Docente</h1><p class="subtitle"><?= htmlspecialchars($_SESSION['nombre_completo']) ?> · <?= date('d/m/Y') ?></p></div><div class="head-actions"><a href="<?= BASE_URL ?>/view/docente/lista_materias.php" class="btn btn-ghost">Mis materias</a><a href="<?= BASE_URL ?>/view/docente/registro_pedagogico.php" class="btn btn-gold">Registro Pedagógico</a></div></div>
  <section class="kpi-grid">
    <article class="kpi"><div class="kpi-top"><span class="chip t-blue"><svg class="ic ic-lg"><use href="#i-book"/></svg></span><div><span class="kpi-num"><?= count($materias) ?></span><span class="kpi-label">Materias asignadas</span></div></div><span class="kpi-foot">Período actual</span></article>
    <article class="kpi star"><div class="kpi-top"><span class="chip t-gold"><svg class="ic ic-lg"><use href="#i-clip"/></svg></span><div><span class="kpi-num gold">85%</span><span class="kpi-label">Avance de carga</span></div></div><div class="kpi-prog"><span class="track"><span class="fill f-gold" data-val="85" style="width:85%"></span></span><span class="meta"><span>Notas registradas</span><span>meta 100%</span></span></div></article>
  </section>
  <div class="banner"><span class="chip t-gold banner-ic"><svg class="ic ic-lg"><use href="#i-alert"/></svg></span><div class="banner-tx"><p>Completa el Registro Pedagógico antes del cierre</p><span>Actas pendientes por curso</span></div><a href="<?= BASE_URL ?>/view/docente/lista_materias.php" class="btn btn-gold">Ver materias</a></div>
  <section class="dash-grid">
    <article class="card"><div class="card-head"><div><h2>Mis Materias</h2><span class="sub">Asignaciones vigentes</span></div><a href="<?= BASE_URL ?>/view/docente/lista_materias.php" class="link">Ver todas →</a></div>
      <div class="tabla-contenedor" style="margin:0;max-width:none;border:none;border-radius:0"><table><thead><tr><th>Materia</th><th>Curso</th><th>Acción</th></tr></thead><tbody>
        <?php foreach(array_slice($materias,0,5) as $m): ?><tr><td><?= htmlspecialchars($m['materia']) ?></td><td><?= htmlspecialchars($m['curso']." ".$m['paralelo']) ?></td><td><a href="<?= BASE_URL ?>/view/docente/registro_pedagogico.php?curso_id=<?= $m['curso_id'] ?>&materia_id=<?= $m['materia_id'] ?>" class="btn btn-primary" style="padding:6px 12px;font-size:12px">Registro</a></td></tr><?php endforeach; ?>
        <?php if(empty($materias)): ?><tr><td colspan="3" style="text-align:center;color:var(--text-3)">Sin asignaciones</td></tr><?php endif; ?>
      </tbody></table></div>
    </article>
    <article class="card"><div class="card-head"><div><h2>Accesos rápidos</h2><span class="sub">Gestión diaria</span></div></div><div class="quick-grid" style="padding:16px;grid-template-columns:1fr"><a href="<?= BASE_URL ?>/view/docente/ver_estudiantes.php" class="quick"><span class="q-chip t-sky"><svg class="ic ic-lg"><use href="#i-users"/></svg></span><span class="q-name">Estudiantes</span><span class="q-desc">Por curso</span></a></div></article>
  </section>
  <footer class="foot"><b>Centralizador de Notas</b> · PACCIOLI</footer>
</main>
</div>
</div>
<script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
