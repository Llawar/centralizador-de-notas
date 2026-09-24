<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_guard('admin');
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';
require_once __DIR__ . '/../../includes/flash.php';

$model = new MateriasModel();
$carrerasModel = new CarrerasModel();
$materias = $model->getAll();
$carreras = $carrerasModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') { $model->crear($_POST['nombre'], $_POST['codigo'], $_POST['carrera_id']); redirect("/view/admin/gestion_materias.php?msg=created"); }
    elseif ($accion === 'eliminar') { $model->eliminar($_POST['id']); redirect("/view/admin/gestion_materias.php?msg=deleted"); }
}
$titulo = 'Gestionar Materias';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Gestionar Materias</h1>
        <?= flash_html(['created'=>'Materia creada.','deleted'=>'Materia eliminada.']) ?>
        <div class="form-container">
            <h3>Crear Nueva Materia</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required></div>
                <div class="form-group"><label>Codigo</label><input type="text" name="codigo" required></div>
                <div class="form-group"><label>Carrera</label><select name="carrera_id" required><?php foreach ($carreras as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="btn btn-success">Crear Materia</button>
            </form>
        </div>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>Codigo</th><th>Carrera</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($materias as $m): ?>
                    <tr>
                        <td><?php echo (int) $m['id']; ?></td>
                        <td><?php echo htmlspecialchars($m['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($m['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($m['carrera_nombre']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?')">
                                <?php echo csrf_campo(); ?>
                                <input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?php echo (int) $m['id']; ?>">
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
