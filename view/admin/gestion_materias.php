<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';

$model = new MateriasModel();
$carrerasModel = new CarrerasModel();
$materias = $model->getAll();
$carreras = $carrerasModel->getAll();

// filtro opcional ?carrera_id=&anio=
$fCarrera = isset($_GET['carrera_id']) && $_GET['carrera_id'] !== '' ? (int) $_GET['carrera_id'] : null;
$fAnio = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int) $_GET['anio'] : null;
if ($fCarrera) {
    $materias = $model->getByCarrera($fCarrera, $fAnio ?: null);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $model->crear($_POST['nombre'], $_POST['codigo'], $_POST['carrera_id'], (int) ($_POST['anio_carrera'] ?? 1));
        redirect("/view/admin/gestion_materias.php?msg=created");
    } elseif ($accion === 'editar') {
        $model->actualizar($_POST['id'], $_POST['nombre'], $_POST['codigo'], $_POST['carrera_id'], (int) ($_POST['anio_carrera'] ?? 1));
        redirect("/view/admin/gestion_materias.php?msg=updated");
    } elseif ($accion === 'eliminar') {
        $model->eliminar($_POST['id']);
        redirect("/view/admin/gestion_materias.php?msg=deleted");
    }
}
$titulo = 'Gestionar Materias';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <h1>Gestionar Materias</h1>
            <div class="head-actions">
                <input type="search" id="qMaterias" placeholder="Buscar materia…" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.05);color:var(--text);">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('active')">+ Nueva Materia</button>
            </div>
        </div>
        <?= flash_html(['created'=>'Materia creada.','updated'=>'Materia actualizada.','deleted'=>'Materia eliminada.']) ?>

        <form method="GET" class="form-container" style="max-width:1100px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;"><label>Carrera</label><select name="carrera_id" onchange="this.form.submit()"><option value="">— Todas —</option><?php foreach ($carreras as $c): ?><option value="<?php echo (int) $c['id']; ?>" <?php echo $fCarrera===(int)$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group" style="margin-bottom:0;"><label>Año carrera</label><select name="anio" onchange="this.form.submit()"><option value="">— Todos —</option><option value="1" <?php echo $fAnio===1?'selected':''; ?>>1er Año</option><option value="2" <?php echo $fAnio===2?'selected':''; ?>>2do Año</option><option value="3" <?php echo $fAnio===3?'selected':''; ?>>3er Año</option></select></div>
            <a href="<?= BASE_URL ?>/view/admin/gestion_materias.php" class="btn btn-ghost" style="margin-bottom:2px;">Limpiar</a>
        </form>

        <div class="tabla-contenedor" style="margin-top:16px;">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>Código</th><th>Carrera</th><th>Año</th><th>Acciones</th></tr></thead>
                <tbody id="tbodyMaterias">
                    <?php foreach ($materias as $m): ?>
                    <tr>
                        <td><?php echo (int) $m['id']; ?></td>
                        <td><?php echo htmlspecialchars($m['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($m['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($m['carrera_nombre']); ?></td>
                        <td><?php echo isset($m['anio_carrera']) ? (int)$m['anio_carrera'].'°' : '—'; ?></td>
                        <td>
                            <?= acciones_columna((int)$m['id'], ['edit', 'delete'], [
                                'edit' => [
                                    'onclick' => "editarMateria({$m['id']}, '".htmlspecialchars($m['nombre'], ENT_QUOTES)."', '".htmlspecialchars($m['codigo'], ENT_QUOTES)."', {$m['carrera_id']}, ".($m['anio_carrera']??1).")"
                                ],
                                'delete' => [
                                    'form_action' => 'gestion_materias.php',
                                    'confirm' => '¿Eliminar esta materia?'
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
            <h3>Crear Nueva Materia</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required></div>
                <div class="form-group"><label>Código</label><input type="text" name="codigo" required></div>
                <div class="form-group"><label>Carrera</label><select name="carrera_id" required><?php foreach ($carreras as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Año de carrera</label><select name="anio_carrera" required><option value="1">1er Año</option><option value="2">2do Año</option><option value="3">3er Año</option></select></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Crear Materia</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCrear').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditar" class="modal-overlay">
        <div class="modal">
            <h3>Editar Materia</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" id="editNombre" required></div>
                <div class="form-group"><label>Código</label><input type="text" name="codigo" id="editCodigo" required></div>
                <div class="form-group"><label>Carrera</label><select name="carrera_id" id="editCarrera" required><?php foreach ($carreras as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Año de carrera</label><select name="anio_carrera" id="editAnio" required><option value="1">1er Año</option><option value="2">2do Año</option><option value="3">3er Año</option></select></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalEditar').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarMateria(id,nombre,codigo,carreraId,anio){ document.getElementById('editId').value=id; document.getElementById('editNombre').value=nombre; document.getElementById('editCodigo').value=codigo; document.getElementById('editCarrera').value=carreraId; document.getElementById('editAnio').value=anio; document.getElementById('modalEditar').classList.add('active'); }
    (function(){ var q=document.getElementById('qMaterias'); var tb=document.getElementById('tbodyMaterias'); if(!q||!tb) return; q.addEventListener('input',function(){ var t=q.value.toLowerCase(); Array.from(tb.rows).forEach(function(r){ r.style.display=r.textContent.toLowerCase().includes(t)?'':'none'; }); }); document.querySelectorAll('.modal-overlay').forEach(function(m){ m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active'); }); }); })();
    </script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
