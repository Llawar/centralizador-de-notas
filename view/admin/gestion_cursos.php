<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';

$cursosModel = new CursosModel();
$carrerasModel = new CarrerasModel();
$gestionFiltro = isset($_GET['gestion']) && $_GET['gestion'] !== '' ? (int) $_GET['gestion'] : null;
$cursos = $gestionFiltro ? $cursosModel->getByGestion($gestionFiltro) : $cursosModel->getAll();
$carreras = $carrerasModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $carreraSel = null;
        foreach ($carreras as $c) {
            if ((int) $c['id'] === (int) $_POST['carrera_id']) { $carreraSel = $c; break; }
        }
        $semestre = ($carreraSel['tipo'] ?? 'anual') === 'semestral' ? (int) ($_POST['semestre'] ?? 1) : 1;
        $anioVal = (int) ($_POST['anio_carrera'] ?? $_POST['anio'] ?? 1);
        $turno = $_POST['turno'] ?? 'mañana';
        $cursosModel->crear($_POST['nombre'], $anioVal, $_POST['paralelo'], $_POST['carrera_id'], $_POST['gestion'], $semestre, $turno);
        redirect("/view/admin/gestion_cursos.php?msg=created");
    }
}
$titulo = 'Gestionar Cursos';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <h1>Gestionar Cursos</h1>
            <div class="head-actions">
                <input type="search" id="qCursos" placeholder="Buscar curso…" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.05);color:var(--text);">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('active')">+ Nuevo Curso</button>
            </div>
        </div>

        <?= flash_html(['created'=>'Curso creado exitosamente.']) ?>
        <?php if ($gestionFiltro): ?>
            <div class="alert alert-info">Mostrando cursos de la gestión <?php echo (int) $gestionFiltro; ?> <a href="<?= BASE_URL ?>/view/admin/gestion_cursos.php" style="color:#fff; text-decoration:underline;">(ver todos)</a></div>
        <?php endif; ?>

        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>Año</th><th>Turno</th><th>Paralelo</th><th>Carrera</th><th>Gestión</th><th>Acciones</th></tr></thead>
                <tbody id="tbodyCursos">
                    <?php foreach ($cursos as $c): ?>
                    <tr>
                        <td><?php echo (int) $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                        <td><?php echo (int) $c['anio_carrera']; ?>°</td>
                        <td><?php echo htmlspecialchars($c['turno'] ?? 'mañana'); ?></td>
                        <td><?php echo htmlspecialchars($c['paralelo']); ?></td>
                        <td><?php echo htmlspecialchars($c['carrera_nombre']); ?></td>
                        <td><?php echo (int) $c['gestion']; ?></td>
                        <td>
                            <?= acciones_columna((int)$c['id'], ['view'], [
                                'view' => [
                                    'href' => BASE_URL . '/view/admin/ver_curso.php?id=' . (int)$c['id'],
                                    'label' => 'Ver'
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
            <h3>Crear Nuevo Curso</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" placeholder="Ej: 1er Año Sistemas A" required></div>
                <div class="form-group"><label>Año de carrera</label><select name="anio_carrera" id="frmAnio" required><option value="1">1er Año</option><option value="2">2do Año</option><option value="3">3er Año</option></select></div>
                <div class="form-group"><label>Turno</label><select name="turno" required><option value="mañana">Mañana (08:00-12:00)</option><option value="tarde">Tarde (13:00-17:00)</option></select></div>
                <div class="form-group"><label>Paralelo</label><input type="text" name="paralelo" value="A" required></div>
                <div class="form-group">
                    <label>Carrera</label>
                    <select name="carrera_id" id="frmCarrera" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo (int) $c['id']; ?>" data-tipo="<?php echo htmlspecialchars($c['tipo']); ?>" data-duracion="<?php echo (int) $c['duracion']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="frmGestionWrap"><label>Gestión</label><input type="number" name="gestion" id="frmGestion" value="<?php echo date('Y'); ?>" required></div>
                <div class="form-group" id="frmSemestreWrap" style="display:none;"><label>Semestre</label><input type="number" name="semestre" value="1" min="1" max="2"></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Crear Curso</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCrear').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function () {
        var sel = document.getElementById('frmCarrera');
        var anio = document.getElementById('frmAnio');
        var semWrap = document.getElementById('frmSemestreWrap');
        if (!sel || !anio || !semWrap) return;
        function actualizar() {
            var opt = sel.options[sel.selectedIndex];
            var tipo = opt && opt.getAttribute('data-tipo') ? opt.getAttribute('data-tipo') : '';
            semWrap.style.display = tipo === 'semestral' ? '' : 'none';
        }
        sel.addEventListener('change', actualizar);
        actualizar();
        var q=document.getElementById('qCursos'); var tb=document.getElementById('tbodyCursos');
        if(q&&tb){ q.addEventListener('input',function(){ var t=q.value.toLowerCase(); Array.from(tb.rows).forEach(function(r){ r.style.display=r.textContent.toLowerCase().includes(t)?'':'none'; }); }); }
        document.querySelectorAll('.modal-overlay').forEach(function(m){ m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active'); }); });
    })();
    </script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
