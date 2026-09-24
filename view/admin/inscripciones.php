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
    $estudiantesModel->inscribirCurso($_POST['estudiante_id'], $_POST['curso_id'], $_POST['semestre'] ?? 1);
    redirect("/view/admin/inscripciones.php?msg=created");
}
$titulo = 'Inscribir Estudiantes';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Inscribir Estudiante en Curso</h1>
        <?= flash_html(['created'=>'Estudiante inscrito exitosamente.']) ?>
        <div class="form-container">
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <div class="form-group">
                    <label>Estudiante</label>
                    <select name="estudiante_id" required><?php foreach ($estudiantes as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre_completo']); ?> (<?php echo $e['ci']; ?>)</option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso_id" required><?php foreach ($cursos as $c): ?><option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?> - <?php echo htmlspecialchars($c['paralelo']); ?> (<?php echo (int) $c['gestion']; ?>)</option><?php endforeach; ?></select>
                </div>
                <div class="form-group"><label>Semestre</label><input type="number" name="semestre" value="1" min="1" max="2"></div>
                <button type="submit" class="btn btn-success">Inscribir</button>
            </form>
        </div>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
