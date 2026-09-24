<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_guard('admin');
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';
require_once __DIR__ . '/../../includes/flash.php';

$cursosModel = new CursosModel();
$docentesModel = new DocentesModel();
$materiasModel = new MateriasModel();

$docentes = $docentesModel->getAll();
$materias = $materiasModel->getAll();
$cursos = $cursosModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $cursosModel->asignarDocenteMateria($_POST['docente_id'], $_POST['materia_id'], $_POST['curso_id']);
    redirect("/view/admin/asignaciones.php?msg=created");
}
$titulo = 'Asignar Docentes a Materias';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Asignar Docente a Materia/Curso</h1>
        <?= flash_html(['created'=>'Asignacion creada exitosamente.']) ?>
        <div class="form-container">
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <div class="form-group">
                    <label>Docente</label>
                    <select name="docente_id" required><?php foreach ($docentes as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre_completo']); ?></option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Materia</label>
                    <select name="materia_id" required><?php foreach ($materias as $m): ?><option value="<?php echo (int) $m['id']; ?>"><?php echo htmlspecialchars($m['nombre']); ?> (<?php echo htmlspecialchars($m['codigo']); ?>)</option><?php endforeach; ?></select>
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <select name="curso_id" required><?php foreach ($cursos as $c): ?><option value="<?php echo (int) $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?> - <?php echo htmlspecialchars($c['paralelo']); ?></option><?php endforeach; ?></select>
                </div>
                <button type="submit" class="btn btn-success">Asignar</button>
            </form>
        </div>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
