<?php
require_once __DIR__ . '/../config/app.php';
include __DIR__ . '/icons.php';

/** Detecta página actual para marcar active en sidebar */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$currentPath = rtrim($currentPath, '/');
$currentFile = basename($currentPath);
$active = function(string $file) use ($currentFile): string {
    return $currentFile === $file ? ' active' : '';
};
?>
<aside class="sidebar" id="sidebar" aria-label="Navegación principal">
<nav>
  <p class="group-title">Principal</p>
  <a class="nav-item<?= $active('dashboard.php') ?>" href="<?= BASE_URL ?>/view/admin/dashboard.php"><svg class="ic"><use href="#i-grid"/></svg><span class="label">Dashboard</span></a>

  <p class="group-title">Académico</p>
  <a class="nav-item<?= $active('gestion_carreras.php') ?>" href="<?= BASE_URL ?>/view/admin/gestion_carreras.php"><svg class="ic"><use href="#i-layers"/></svg><span class="label">Carreras</span></a>
  <a class="nav-item<?= $active('gestion_materias.php') ?>" href="<?= BASE_URL ?>/view/admin/gestion_materias.php"><svg class="ic"><use href="#i-book"/></svg><span class="label">Materias</span></a>
  <a class="nav-item<?= $active('gestion_cursos.php') ?>" href="<?= BASE_URL ?>/view/admin/gestion_cursos.php"><svg class="ic"><use href="#i-monitor"/></svg><span class="label">Cursos</span></a>
  <a class="nav-item<?= $active('inscripciones.php') ?>" href="<?= BASE_URL ?>/view/admin/inscripciones.php"><svg class="ic"><use href="#i-userplus"/></svg><span class="label">Inscripciones</span></a>
  <a class="nav-item<?= $active('asignaciones.php') ?>" href="<?= BASE_URL ?>/view/admin/asignaciones.php"><svg class="ic"><use href="#i-clip"/></svg><span class="label">Asignaciones</span></a>
  <a class="nav-item<?= $active('gestion_periodos.php') ?>" href="<?= BASE_URL ?>/view/admin/gestion_periodos.php"><svg class="ic"><use href="#i-cal"/></svg><span class="label">Gestión de Períodos</span></a>

  <p class="group-title">Personas</p>
  <a class="nav-item<?= $active('gestion_docentes.php') ?>" href="<?= BASE_URL ?>/view/admin/gestion_docentes.php"><svg class="ic"><use href="#i-users"/></svg><span class="label">Docentes</span></a>
  <a class="nav-item<?= $active('gestion_estudiantes.php') ?>" href="<?= BASE_URL ?>/view/admin/gestion_estudiantes.php"><svg class="ic"><use href="#i-cap"/></svg><span class="label">Estudiantes</span></a>

  <p class="group-title">Calificaciones</p>
  <a class="nav-item<?= $active('historial_academico.php') ?>" href="<?= BASE_URL ?>/view/admin/historial_academico.php"><svg class="ic"><use href="#i-file"/></svg><span class="label">Historial Académico</span></a>

  <p class="group-title">Sistema</p>
  <a class="nav-item danger" href="<?= BASE_URL ?>/logout.php"><svg class="ic"><use href="#i-out"/></svg><span class="label">Cerrar Sesión</span></a>
</nav>
</aside>
<div class="backdrop" id="backdrop"></div>