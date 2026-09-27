<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';

$model = new EstudiantesModel();
$estudiantes = $model->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $model->crear($_POST['ci'], $_POST['nombre'], $_POST['matricula'], $_POST['anio_ingreso'], $_POST['email'], $_POST['password']);
        redirect("/view/admin/gestion_estudiantes.php?msg=created");
    } elseif ($accion === 'editar') {
        $model->actualizar($_POST['id'], $_POST['ci'], $_POST['nombre'], $_POST['matricula'], $_POST['anio_ingreso'], $_POST['email'], $_POST['estado']);
        redirect("/view/admin/gestion_estudiantes.php?msg=updated");
    } elseif ($accion === 'eliminar') {
        $model->eliminar($_POST['id']);
        redirect("/view/admin/gestion_estudiantes.php?msg=deleted");
    }
}
$titulo = 'Gestionar Estudiantes';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <h1>Gestionar Estudiantes</h1>
            <div class="head-actions">
                <input type="search" id="qEstudiantes" placeholder="Buscar estudiante…" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.05);color:var(--text);">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('active')">+ Nuevo Estudiante</button>
            </div>
        </div>

        <?= flash_html(['created'=>'Estudiante creado exitosamente.','updated'=>'Estudiante actualizado exitosamente.','deleted'=>'Estudiante eliminado exitosamente.']) ?>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr><th>ID</th><th>C.I.</th><th>Nombre</th><th>Matrícula</th><th>Año Ingreso</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody id="tbodyEstudiantes">
                    <?php foreach ($estudiantes as $e): ?>
                    <tr>
                        <td><?php echo (int) $e['id']; ?></td>
                        <td><?php echo htmlspecialchars($e['ci']); ?></td>
                        <td><?php echo htmlspecialchars($e['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($e['matricula']); ?></td>
                        <td><?php echo (int) $e['anio_ingreso']; ?></td>
                        <td><?php echo htmlspecialchars($e['estado']); ?></td>
                        <td>
                            <?= acciones_columna((int)$e['id'], ['edit', 'delete'], [
                                'edit' => [
                                    'onclick' => "editarEstudiante({$e['id']}, '".htmlspecialchars($e['ci'], ENT_QUOTES)."', '".htmlspecialchars($e['nombre_completo'], ENT_QUOTES)."', '".htmlspecialchars($e['matricula'], ENT_QUOTES)."', {$e['anio_ingreso']}, '".htmlspecialchars($e['email'], ENT_QUOTES)."', '".htmlspecialchars($e['estado'], ENT_QUOTES)."')"
                                ],
                                'delete' => [
                                    'form_action' => 'gestion_estudiantes.php',
                                    'confirm' => '¿Eliminar este estudiante?'
                                ]
                            ]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <div id="modalCrear" class="modal-overlay">
        <div class="modal">
            <h3>Agregar Nuevo Estudiante</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>C.I.</label><input type="text" name="ci" required></div>
                <div class="form-group"><label>Nombre Completo</label><input type="text" name="nombre" required></div>
                <div class="form-group"><label>Matrícula</label><input type="text" name="matricula" required></div>
                <div class="form-group"><label>Año de Ingreso</label><input type="number" name="anio_ingreso" value="<?php echo date('Y'); ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Contraseña</label><input type="password" name="password" required></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Agregar Estudiante</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCrear').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditar" class="modal-overlay">
        <div class="modal">
            <h3>Editar Estudiante</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group"><label>C.I.</label><input type="text" name="ci" id="editCi" required></div>
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" id="editNombre" required></div>
                <div class="form-group"><label>Matrícula</label><input type="text" name="matricula" id="editMatricula" required></div>
                <div class="form-group"><label>Año Ingreso</label><input type="number" name="anio_ingreso" id="editAnio" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail"></div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="editEstado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalEditar').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarEstudiante(id, ci, nombre, matricula, anio, email, estado) {
        document.getElementById('editId').value = id;
        document.getElementById('editCi').value = ci;
        document.getElementById('editNombre').value = nombre;
        document.getElementById('editMatricula').value = matricula;
        document.getElementById('editAnio').value = anio;
        document.getElementById('editEmail').value = email;
        document.getElementById('editEstado').value = estado;
        document.getElementById('modalEditar').classList.add('active');
    }
    (function(){ var q=document.getElementById('qEstudiantes'); var tb=document.getElementById('tbodyEstudiantes'); if(q&&tb){ q.addEventListener('input',function(){ var t=q.value.toLowerCase(); Array.from(tb.rows).forEach(function(r){ r.style.display=r.textContent.toLowerCase().includes(t)?'':'none'; }); }); } document.querySelectorAll('.modal-overlay').forEach(function(m){ m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active'); }); }); })();
    </script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
