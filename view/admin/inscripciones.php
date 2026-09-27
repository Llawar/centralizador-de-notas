<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_guard('admin');
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../includes/flash.php';

$estudiantesModel = new EstudiantesModel();
$cursosModel = new CursosModel();
$estudiantes = $estudiantesModel->getAll();
$cursos = $cursosModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $estudiantesModel->inscribirCurso((int) $_POST['estudiante_id'], (int) $_POST['curso_id'], (int) ($_POST['semestre'] ?? 1));
    redirect("/view/admin/inscripciones.php?msg=created");
}
$titulo = 'Inscripciones';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Inscripciones</h1>
        <div class="alert alert-info">Las inscripciones ahora se gestionan desde <strong>Detalle del Curso → pestaña Estudiantes</strong>. Esta vista se mantiene por compatibilidad.</div>
        <?= flash_html(['created'=>'Estudiante inscrito exitosamente.']) ?>
        <div class="form-container">
            <p style="margin-bottom:10px;">Atajo a cursos:</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                <?php foreach (array_slice($cursos,0,8) as $c): ?><a class="btn btn-ghost" style="font-size:12px;" href="<?= BASE_URL ?>/view/admin/ver_curso.php?id=<?php echo (int)$c['id']; ?>&tab=estudiantes"><?php echo htmlspecialchars($c['nombre']); ?> <?php echo htmlspecialchars($c['paralelo']); ?></a><?php endforeach; ?>
                <a class="btn btn-primary" style="font-size:12px;" href="<?= BASE_URL ?>/view/admin/gestion_cursos.php">Ver todos los cursos</a>
            </div>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <div class="form-group">
                    <label>Estudiante</label>
                    <select name="estudiante_id" required><?php foreach ($estudiantes as $e): ?><option value="<?php echo (int)$e['id']; ?>"><?php echo htmlspecialchars($e['nombre_completo']); ?> (<?php echo htmlspecialchars($e['ci']); ?>)</option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso_id" required><?php foreach ($cursos as $c): ?><option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?> - <?php echo htmlspecialchars($c['paralelo']); ?> (<?php echo (int) $c['gestion']; ?>) <?php echo htmlspecialchars($c['turno']??''); ?></option><?php endforeach; ?></select>
                </div>
                <div class="form-group"><label>Semestre (solo semestral)</label><input type="number" name="semestre" value="1" min="1" max="2"></div>
                <button type="submit" class="btn btn-success">Inscribir</button>
            </form>
        </div>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
