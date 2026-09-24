<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_guard('admin');
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../includes/flash.php';

$model = new DocentesModel();
$docentes = $model->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $model->crear($_POST['ci'], $_POST['nombre'], $_POST['email'], $_POST['telefono'], $_POST['password']);
        redirect("/view/admin/gestion_docentes.php?msg=created");
    } elseif ($accion === 'editar') {
        $model->actualizar($_POST['id'], $_POST['ci'], $_POST['nombre'], $_POST['email'], $_POST['telefono'], $_POST['estado']);
        redirect("/view/admin/gestion_docentes.php?msg=updated");
    } elseif ($accion === 'eliminar') {
        $model->eliminar($_POST['id']);
        redirect("/view/admin/gestion_docentes.php?msg=deleted");
    }
}
$titulo = 'Gestionar Docentes';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Gestionar Docentes</h1>

        <?= flash_html(['created'=>'Docente creado exitosamente.','updated'=>'Docente actualizado exitosamente.','deleted'=>'Docente eliminado exitosamente.']) ?>

        <div class="form-container">
            <h3>Agregar Nuevo Docente</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group">
                    <label>C.I.</label>
                    <input type="text" name="ci" required>
                </div>
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Telefono</label>
                    <input type="text" name="telefono">
                </div>
                <div class="form-group">
                    <label>Contrasena</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-success">Agregar Docente</button>
            </form>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>C.I.</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docentes as $d): ?>
                    <tr>
                        <td><?php echo $d['id']; ?></td>
                        <td><?php echo htmlspecialchars($d['ci']); ?></td>
                        <td><?php echo htmlspecialchars($d['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($d['email']); ?></td>
                        <td><?php echo htmlspecialchars($d['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($d['estado']); ?></td>
                        <td>
                            <button class="btn btn-primary" onclick="editarDocente(<?php echo (int) $d['id']; ?>, '<?php echo htmlspecialchars($d['ci'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($d['nombre_completo'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($d['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($d['telefono'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($d['estado'], ENT_QUOTES); ?>')">Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este docente?')">
                                <?php echo csrf_campo(); ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo (int) $d['id']; ?>">
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <div id="modalEditar" class="modal-overlay">
        <div class="modal">
            <h3>Editar Docente</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group"><label>C.I.</label><input type="text" name="ci" id="editCi" required></div>
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" id="editNombre" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail"></div>
                <div class="form-group"><label>Telefono</label><input type="text" name="telefono" id="editTelefono"></div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="editEstado">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalEditar').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarDocente(id, ci, nombre, email, telefono, estado) {
        document.getElementById('editId').value = id;
        document.getElementById('editCi').value = ci;
        document.getElementById('editNombre').value = nombre;
        document.getElementById('editEmail').value = email;
        document.getElementById('editTelefono').value = telefono;
        document.getElementById('editEstado').value = estado;
        document.getElementById('modalEditar').classList.add('active');
    }
    </script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
