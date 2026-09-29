<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/GestionesModel.php';

$model = new GestionesModel();
$gestiones = $model->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $model->crear($_POST['anio'], $_POST['fecha_inicio'] ?: null, $_POST['fecha_fin'] ?: null);
        redirect("/view/admin/gestiones.php?msg=created");
    } elseif ($accion === 'abrir') {
        $model->abrir((int) $_POST['id']);
        redirect("/view/admin/gestiones.php?msg=updated");
    } elseif ($accion === 'cerrar') {
        $model->cerrar((int) $_POST['id']);
        redirect("/view/admin/gestiones.php?msg=updated");
    } elseif ($accion === 'eliminar') {
        $model->eliminar((int) $_POST['id']);
        redirect("/view/admin/gestiones.php?msg=deleted");
    }
}
$titulo = 'Gestiones';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <div>
                <h1>Gestiones</h1>
                <p class="subtitle">Año lectivo. Solo una gestión puede estar abierta a la vez.</p>
            </div>
            <div class="head-actions">
                <input type="search" id="qGestiones" placeholder="Buscar gestión…" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.05);color:var(--text);">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('active')">+ Nueva Gestión</button>
            </div>
        </div>
        <?= flash_html(['created'=>'Gestión creada.','updated'=>'Gestión actualizada.','deleted'=>'Gestión eliminada.']) ?>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>N°</th><th>Año</th><th>Estado</th><th>Inicio</th><th>Fin</th><th>Acciones</th></tr></thead>
                <tbody id="tbodyGestiones">
                    <?php $n=1; foreach ($gestiones as $g): ?>
                    <tr>
                        <td><?php echo $n++; ?></td>
                        <td><?php echo htmlspecialchars($g['anio']); ?></td>
                        <td><?php echo $g['estado']==='abierta' ? '<span class="badge-abierto">Abierta</span>' : '<span class="badge-cerrado">Cerrada</span>'; ?></td>
                        <td><?php echo htmlspecialchars($g['fecha_inicio'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($g['fecha_fin'] ?? '—'); ?></td>
                        <td>
                            <?php if ($g['estado']==='abierta'): ?>
                            <form method="POST" style="display:inline;"><?php echo csrf_campo(); ?><input type="hidden" name="accion" value="cerrar"><input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>"><button class="btn btn-ghost" style="padding:6px 10px;font-size:12px;">Cerrar</button></form>
                            <?php else: ?>
                            <form method="POST" style="display:inline;"><?php echo csrf_campo(); ?><input type="hidden" name="accion" value="abrir"><input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>"><button class="btn btn-success" style="padding:6px 10px;font-size:12px;">Abrir</button></form>
                            <?php endif; ?>
                            <?= acciones_columna((int)$g['id'], ['delete'], ['delete'=>['form_action'=>'gestiones.php','confirm'=>'¿Eliminar esta gestión?']]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <div id="modalCrear" class="modal-overlay">
        <div class="modal">
            <h3>Nueva Gestión</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>Año</label><input type="number" name="anio" value="<?php echo date('Y'); ?>" min="2000" max="2100" required></div>
                <div class="form-group"><label>Fecha inicio</label><input type="date" name="fecha_inicio"></div>
                <div class="form-group"><label>Fecha fin</label><input type="date" name="fecha_fin"></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Crear</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCrear').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function(){ var q=document.getElementById('qGestiones'); var tb=document.getElementById('tbodyGestiones'); if(q&&tb){ q.addEventListener('input',function(){ var t=q.value.toLowerCase(); Array.from(tb.rows).forEach(function(r){ r.style.display=r.textContent.toLowerCase().includes(t)?'':'none'; }); }); } document.querySelectorAll('.modal-overlay').forEach(function(m){ m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active'); }); }); })();
    </script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
