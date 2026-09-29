<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';

$cursosModel = new CursosModel();
$carrerasModel = new CarrerasModel();
$carreras = $carrerasModel->getAll();

// filtro opcional ?carrera_id= (igual que Materias: cada página filtra lo suyo, sin saltos)
$fCarrera = isset($_GET['carrera_id']) && $_GET['carrera_id'] !== '' ? (int)$_GET['carrera_id'] : null;
$cursos = $cursosModel->getByFilters($fCarrera, null, null, null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $cursosModel->crear((int)($_POST['anio_carrera'] ?? 1), $_POST['turno'] ?? 'mañana', $_POST['paralelo'] ?? 'A', (int)$_POST['carrera_id']);
        redirect("/view/admin/gestion_cursos.php?msg=created");
    } elseif ($accion === 'editar') {
        $cursosModel->actualizar((int)$_POST['id'], (int)($_POST['anio_carrera'] ?? 1), $_POST['turno'] ?? 'mañana', $_POST['paralelo'] ?? 'A', (int)$_POST['carrera_id']);
        redirect("/view/admin/gestion_cursos.php?msg=updated");
    } elseif ($accion === 'eliminar') {
        $cursosModel->eliminar((int)$_POST['id']);
        redirect("/view/admin/gestion_cursos.php?msg=deleted");
    }
}
$titulo = 'Gestionar Cursos';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <div>
                <h1>Gestionar Cursos</h1>
                <p class="subtitle">Catálogo fijo: año del plan + turno + paralelo. Sin gestión.</p>
            </div>
            <div class="head-actions">
                <input type="search" id="qCursos" placeholder="Buscar curso…" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.05);color:var(--text);">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('active')">+ Nuevo Curso</button>
            </div>
        </div>
        <?= flash_html(['created'=>'Curso creado exitosamente.','updated'=>'Curso actualizado.','deleted'=>'Curso eliminado.']) ?>
        <form method="GET" class="form-container" style="max-width:1100px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;"><label>Carrera</label><select name="carrera_id" onchange="this.form.submit()"><option value="">— Todas —</option><?php foreach ($carreras as $c): ?><option value="<?php echo (int) $c['id']; ?>" <?php echo $fCarrera===(int)$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
            <a href="<?= BASE_URL ?>/view/admin/gestion_cursos.php" class="btn btn-ghost" style="margin-bottom:2px;">Limpiar</a>
        </form>
        <div class="tabla-contenedor" style="margin-top:16px;">
            <table>
                <thead><tr><th>N°</th><th>Carrera</th><th>Año</th><th>Turno</th><th>Paralelo</th><th>Acciones</th></tr></thead>
                <tbody id="tbodyCursos">
                    <?php $n=1; foreach ($cursos as $c): ?>
                    <tr>
                        <td><?php echo $n++; ?></td>
                        <td><?php echo htmlspecialchars($c['carrera_nombre']); ?></td>
                        <td><?php echo (int) $c['anio_carrera']; ?>°</td>
                        <td><?php echo htmlspecialchars($c['turno']); ?></td>
                        <td><?php echo htmlspecialchars($c['paralelo']); ?></td>
                        <td>
                            <?= acciones_columna((int)$c['id'], ['edit','delete'], [
                                'edit' => ['onclick' => "editarCurso({$c['id']},{$c['anio_carrera']},'{$c['turno']}','{$c['paralelo']}',{$c['carrera_id']})"],
                                'delete' => ['form_action' => 'gestion_cursos.php', 'confirm' => '¿Eliminar este curso?']
                            ]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <div id="modalCrear" class="modal-overlay">
        <div class="modal">
            <h3>Crear Nuevo Curso</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>Año de carrera</label><select name="anio_carrera" required><option value="1">1er Año</option><option value="2">2do Año</option><option value="3">3er Año</option></select></div>
                <div class="form-group"><label>Turno</label><select name="turno" required><option value="mañana">Mañana</option><option value="tarde">Tarde</option></select></div>
                <div class="form-group"><label>Paralelo</label><input type="text" name="paralelo" value="A" required></div>
                <div class="form-group"><label>Carrera</label><select name="carrera_id" required><?php foreach ($carreras as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Crear Curso</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCrear').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <div id="modalEditar" class="modal-overlay">
        <div class="modal">
            <h3>Editar Curso</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group"><label>Año</label><select name="anio_carrera" id="editAnio"><option value="1">1er Año</option><option value="2">2do Año</option><option value="3">3er Año</option></select></div>
                <div class="form-group"><label>Turno</label><select name="turno" id="editTurno"><option value="mañana">Mañana</option><option value="tarde">Tarde</option></select></div>
                <div class="form-group"><label>Paralelo</label><input type="text" name="paralelo" id="editParalelo" required></div>
                <div class="form-group"><label>Carrera</label><select name="carrera_id" id="editCarrera"><?php foreach ($carreras as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalEditar').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function editarCurso(id,anio,turno,paralelo,carrera){ document.getElementById('editId').value=id; document.getElementById('editAnio').value=anio; document.getElementById('editTurno').value=turno; document.getElementById('editParalelo').value=paralelo; document.getElementById('editCarrera').value=carrera; document.getElementById('modalEditar').classList.add('active'); }
    (function(){ var q=document.getElementById('qCursos'); var tb=document.getElementById('tbodyCursos'); if(q&&tb){ q.addEventListener('input',function(){ var t=q.value.toLowerCase(); Array.from(tb.rows).forEach(function(r){ r.style.display=r.textContent.toLowerCase().includes(t)?'':'none'; }); }); } document.querySelectorAll('.modal-overlay').forEach(function(m){ m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active'); }); }); })();
    </script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
