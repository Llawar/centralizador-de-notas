<?php require_once __DIR__ . '/../config/app.php'; ?>
<?php include __DIR__ . '/icons.php'; ?>
<aside class="sidebar" id="sidebar" aria-label="Navegación principal">
<nav>
  <p class="group-title">Principal</p>
  <a class="nav-item" href="<?= BASE_URL ?>/view/docente/dashboard.php"><svg class="ic"><use href="#i-grid"/></svg><span class="label">Dashboard</span></a>
  <p class="group-title">Académico</p>
  <a class="nav-item" href="<?= BASE_URL ?>/view/docente/lista_materias.php"><svg class="ic"><use href="#i-book"/></svg><span class="label">Mis Materias</span></a>
  <a class="nav-item" href="<?= BASE_URL ?>/view/docente/registro_pedagogico.php"><svg class="ic"><use href="#i-clip"/></svg><span class="label">Registro Pedagógico</span></a>
  <a class="nav-item" href="<?= BASE_URL ?>/view/docente/ver_estudiantes.php"><svg class="ic"><use href="#i-users"/></svg><span class="label">Estudiantes</span></a>
  <p class="group-title">Sistema</p>
  <a class="nav-item danger" href="<?= BASE_URL ?>/logout.php"><svg class="ic"><use href="#i-out"/></svg><span class="label">Cerrar Sesión</span></a>
</nav>
</aside>
<div class="backdrop" id="backdrop"></div>
