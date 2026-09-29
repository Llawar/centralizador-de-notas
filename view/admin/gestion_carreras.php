<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';

$model = new CarrerasModel();
$carreras = $model->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $model->crear($_POST['nombre'], (int) ($_POST['duracion'] ?? 3), $_POST['tipo'] ?? 'anual');
        redirect("/view/admin/gestion_carreras.php?msg=created");
    } elseif ($accion === 'editar') {
        $model->actualizar($_POST['id'], $_POST['nombre'], (int) ($_POST['duracion'] ?? 3), $_POST['tipo'] ?? 'anual', $_POST['estado']);
        redirect("/view/admin/gestion_carreras.php?msg=updated");
    } elseif ($accion === 'eliminar') {
        $model->eliminar($_POST['id']);
        redirect("/view/admin/gestion_carreras.php?msg=deleted");
    }
}

$titulo = 'Gestionar Carreras';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <h1>Gestionar Carreras</h1>
            <div class="head-actions">
                <input type="search" id="qCarreras" placeholder="Buscar carrera…" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.05);color:var(--text);">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('active')">+ Nueva Carrera</button>
            </div>
        </div>

        <?= flash_html(['created'=>'Carrera creada exitosamente.','updated'=>'Carrera actualizada exitosamente.','deleted'=>'Carrera eliminada exitosamente.']) ?>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Duración</th>
                        <th>Tipo</th>
                        <th>Parciales</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyCarreras">
                    <?php foreach ($carreras as $c): ?>
                    <tr>
                        <td><?php echo (int) $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                        <td><?php echo (int) $c['duracion']; ?></td>
                        <td><?php echo $c['tipo'] === 'semestral' ? 'Semestral' : 'Anual'; ?></td>
                        <td><?php echo $c['tipo'] === 'semestral' ? '2 por semestre' : '4 al año'; ?></td>
                        <td><?php echo htmlspecialchars($c['estado']); ?></td>
                        <td>
                            <?= acciones_columna((int)$c['id'], ['edit', 'delete'], [
                                'edit' => [
                                    'onclick' => "editarCarrera({$c['id']}, '".htmlspecialchars($c['nombre'], ENT_QUOTES)."', {$c['duracion']}, '".$c['tipo']."', '".$c['estado']."')"
                                ],
                                'delete' => [
                                    'form_action' => 'gestion_carreras.php',
                                    'confirm' => '¿Eliminar esta carrera?'
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
            <h3>Crear Nueva Carrera</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Duración (años)</label>
                    <input type="number" name="duracion" value="3" min="1" max="10" required>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" required>
                        <option value="anual">Anual (4 parciales al año)</option>
                        <option value="semestral">Semestral (2 parciales por semestre)</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Crear Carrera</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCrear').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditar" class="modal-overlay">
        <div class="modal">
            <h3>Editar Carrera</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" id="editNombre" required>
                </div>
                <div class="form-group">
                    <label>Duración (años)</label>
                    <input type="number" name="duracion" id="editDuracion" min="1" max="10" required>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" id="editTipo" required>
                        <option value="anual">Anual (4 parciales al año)</option>
                        <option value="semestral">Semestral (2 parciales por semestre)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="editEstado">
                        <option value="activa">Activa</option>
                        <option value="inactiva">Inactiva</option>
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
    function editarCarrera(id, nombre, duracion, tipo, estado) {
        document.getElementById('editId').value = id;
        document.getElementById('editNombre').value = nombre;
        document.getElementById('editDuracion').value = duracion;
        document.getElementById('editTipo').value = tipo;
        document.getElementById('editEstado').value = estado;
        document.getElementById('modalEditar').classList.add('active');
    }
    (function(){
        var q=document.getElementById('qCarreras'); var tb=document.getElementById('tbodyCarreras');
        if(!q||!tb) return;
        q.addEventListener('input',function(){ var t=q.value.toLowerCase(); Array.from(tb.rows).forEach(function(r){ r.style.display=r.textContent.toLowerCase().includes(t)?'':'none'; }); });
        document.querySelectorAll('.modal-overlay').forEach(function(m){ m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active'); }); });
    })();
    </script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
