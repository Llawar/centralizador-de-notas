<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_guard('admin');
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';
require_once __DIR__ . '/../../includes/flash.php';

$cursosModel = new CursosModel();
$docentesModel = new DocentesModel();
$materiasModel = new MateriasModel();
$parcialModel = new ParcialPeriodoModel();

$docentes = $docentesModel->getAll();
$materias = $materiasModel->getAll();
$cursos = $cursosModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $cursosModel->asignarDocenteMateria((int) $_POST['docente_id'], (int) $_POST['materia_id'], (int) $_POST['curso_id']);
    // auto-generar periodos
    $c = $cursosModel->getById((int) $_POST['curso_id']);
    if ($c) $parcialModel->asegurarPeriodosParaCursoMateria((int) $_POST['curso_id'], (int) $_POST['materia_id'], (int) ($c['gestion'] ?? date('Y')));
    redirect("/view/admin/asignaciones.php?msg=created");
}
$titulo = 'Asignaciones';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Asignaciones</h1>
        <div class="alert alert-info">Las asignaciones ahora se gestionan desde <strong>Detalle del Curso → pestaña Materias impartidas</strong>. Esta vista se mantiene por compatibilidad.</div>
        <?= flash_html(['created'=>'Asignación creada exitosamente.']) ?>
        <div class="form-container">
            <p style="margin-bottom:10px;">Atajo a cursos:</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                <?php foreach (array_slice($cursos,0,8) as $c): ?><a class="btn btn-ghost" style="font-size:12px;" href="<?= BASE_URL ?>/view/admin/ver_curso.php?id=<?php echo (int)$c['id']; ?>&tab=materias"><?php echo htmlspecialchars($c['nombre']); ?> <?php echo htmlspecialchars($c['paralelo']); ?></a><?php endforeach; ?>
                <a class="btn btn-primary" style="font-size:12px;" href="<?= BASE_URL ?>/view/admin/gestion_cursos.php">Ver todos los cursos</a>
            </div>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <div class="form-group">
                    <label>Docente</label>
                    <select name="docente_id" required><?php foreach ($docentes as $d): ?><option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['nombre_completo']); ?></option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Materia</label>
                    <select name="materia_id" required><?php foreach ($materias as $m): ?><option value="<?php echo (int)$m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?> (<?php echo htmlspecialchars($m['codigo']); ?>)</option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso_id" required><?php foreach ($cursos as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?> - <?php echo htmlspecialchars($c['paralelo']); ?></option><?php endforeach; ?></select>
                </div>
                <button type="submit" class="btn btn-success">Asignar</button>
            </form>
        </div>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
