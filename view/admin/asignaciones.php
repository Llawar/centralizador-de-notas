<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';
require_once __DIR__ . '/../../model/GestionesModel.php';

$cursosModel = new CursosModel();
$docModel = new DocentesModel();
$matModel = new MateriasModel();
$parcialModel = new ParcialPeriodoModel();
$gestionesModel = new GestionesModel();
$gestionAbierta = $gestionesModel->getAbierta();
$gestionId = $gestionAbierta ? (int)$gestionAbierta['id'] : 0;
$gestionAnio = $gestionAbierta ? (int)$gestionAbierta['anio'] : 0;

function ordinal_anio(int $n): string {
    $ords = [1 => '1er', 2 => '2do', 3 => '3er', 4 => '4to', 5 => '5to', 6 => '6to'];
    return ($ords[$n] ?? $n . 'to') . ' Año';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'asignar' && $gestionId) {
        // Orden modal: Docente → Carrera → Año → Turno → Materia → Paralelo
        $docenteId = (int)($_POST['docente_id'] ?? 0);
        $carreraId = (int)($_POST['carrera_id'] ?? 0);
        $anio = (int)($_POST['anio'] ?? 0);
        $turno = trim($_POST['turno'] ?? '');
        $materiaId = (int)($_POST['materia_id'] ?? 0);
        $paralelo = trim($_POST['paralelo'] ?? '');
        if ($docenteId && $carreraId && $anio && $turno && $materiaId && $paralelo) {
            $cursos = $cursosModel->getByFilters($carreraId, $anio, $turno, $paralelo);
            if (!empty($cursos)) {
                $cursoId = (int)$cursos[0]['id'];
                $cursosModel->asignarDocenteMateria($docenteId, $materiaId, $cursoId, $gestionId);
                $parcialModel->asegurarPeriodosParaCursoMateria($cursoId, $materiaId, $gestionId);
                redirect('/view/admin/asignaciones.php?msg=created&view=curso&carrera_id=' . $carreraId . '&anio=' . $anio . '&paralelo=' . urlencode($paralelo));
            }
        }
        redirect('/view/admin/asignaciones.php?msg=error&view=curso');
    }
    if ($accion === 'quitar' && $gestionId) {
        $docenteId = (int)($_POST['docente_id'] ?? 0);
        $materiaId = (int)($_POST['materia_id'] ?? 0);
        $cursoId = (int)($_POST['curso_id'] ?? 0);
        if ($docenteId && $materiaId && $cursoId) {
            $cursosModel->quitarAsignacion($docenteId, $materiaId, $cursoId, $gestionId);
        }
        redirect('/view/admin/asignaciones.php?msg=deleted&view=' . urlencode($_POST['view'] ?? 'curso'));
    }
}

$view = $_GET['view'] ?? 'curso';
if (!in_array($view, ['curso', 'docente'], true)) $view = 'curso';
$opciones = $cursosModel->getFilterOptions();

// ---- Por Curso ----
$carreraId = isset($_GET['carrera_id']) && $_GET['carrera_id'] !== '' ? (int)$_GET['carrera_id'] : null;
$anio = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : null;
$paralelo = isset($_GET['paralelo']) && $_GET['paralelo'] !== '' ? $_GET['paralelo'] : null;
$q = trim($_GET['q'] ?? '');
$cursos = [];
$matriz = []; // curso_id => impartidas
$totalCupos = 0; $totalAsig = 0;
if ($view === 'curso') {
    $cursos = $cursosModel->getByFilters($carreraId, $anio, null, $paralelo);
    foreach ($cursos as $c) {
        $imp = $gestionId ? $cursosModel->getMateriasImpartidas((int)$c['id'], $gestionId) : [];
        if ($q !== '') {
            $imp = array_values(array_filter($imp, function ($m) use ($q) {
                $qq = mb_strtolower($q);
                return str_contains(mb_strtolower($m['nombre'] ?? ''), $qq) || str_contains(mb_strtolower($m['codigo'] ?? ''), $qq) || str_contains(mb_strtolower($m['docente_nombre'] ?? ''), $qq);
            }));
        }
        $matriz[(int)$c['id']] = $imp;
        $totalCupos += count($imp);
        foreach ($imp as $m) if (!empty($m['docente_id'])) $totalAsig++;
    }
}
$totalPend = $totalCupos - $totalAsig;
$cobertura = $totalCupos ? (int)round($totalAsig / $totalCupos * 100) : 0;

// ---- Por Docente ----
$docQuery = trim($_GET['dq'] ?? '');
$docSeleccionado = isset($_GET['docente_id']) && $_GET['docente_id'] !== '' ? (int)$_GET['docente_id'] : null;
$docSugerencias = [];
$docInfo = null;
$carga = [];
if ($view === 'docente') {
    if ($docQuery !== '') {
        $docSugerencias = array_slice($docModel->getAllPaginated(10, 0, $docQuery)['data'] ?? [], 0, 10);
    }
    if ($docSeleccionado) {
        $docInfo = $docModel->getById($docSeleccionado);
        if ($docInfo && $gestionId) {
            $carga = $docModel->getMaterias($docSeleccionado, $gestionId);
            if ($q !== '') {
                $qq = mb_strtolower($q);
                $carga = array_values(array_filter($carga, fn($r) => str_contains(mb_strtolower($r['materia'] ?? ''), $qq) || str_contains(mb_strtolower($r['codigo'] ?? ''), $qq)));
            }
        }
    }
}

$docentesTodos = $docModel->getAll();
$materiasModal = $carreraId ? $matModel->getByCarrera($carreraId, $anio) : [];

$titulo = 'Asignaciones';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-head">
    <div>
        <h1>Asignaciones</h1>
        <p class="subtitle">Vincula docentes a las materias de cada curso, turno y paralelo.</p>
    </div>
    <div class="head-actions">
        <div class="seg">
            <a href="<?= BASE_URL ?>/view/admin/asignaciones.php?view=curso" class="<?php echo $view === 'curso' ? 'on' : ''; ?>">Por Curso</a>
            <a href="<?= BASE_URL ?>/view/admin/asignaciones.php?view=docente" class="<?php echo $view === 'docente' ? 'on' : ''; ?>">Por Docente</a>
        </div>
        <button type="button" class="btn btn-primary" id="btnNewAsg">
            <svg class="ic ic-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Nueva asignación
        </button>
    </div>
</div>
<?= flash_html(['created' => 'Asignación guardada.', 'deleted' => 'Asignación eliminada.', 'error' => 'No se pudo asignar. Verifique los datos.']) ?>

<?php if ($view === 'curso'): ?>
<div class="filter-bar">
    <form method="GET" style="display:contents;">
        <input type="hidden" name="view" value="curso">
        <div class="form-group"><label>Gestión</label>
            <div class="readonly-line" style="margin:0;">Gestión <b><?php echo $gestionAnio ?: '—'; ?></b> abierta</div>
        </div>
        <div class="form-group"><label>Carrera</label><select name="carrera_id" onchange="this.form.submit()"><option value="">Todas</option><?php foreach ($opciones['carreras'] as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $carreraId === (int)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Curso</label><select name="anio" onchange="this.form.submit()"><option value="">Todos</option><?php foreach ($opciones['anios'] as $a): ?><option value="<?php echo $a; ?>" <?php echo $anio === (int)$a ? 'selected' : ''; ?>><?php echo ordinal_anio((int)$a); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Paralelo</label><select name="paralelo" onchange="this.form.submit()"><option value="">Todos</option><?php foreach ($opciones['paralelos'] as $p): ?><option value="<?php echo htmlspecialchars($p); ?>" <?php echo $paralelo === $p ? 'selected' : ''; ?>><?php echo htmlspecialchars($p); ?></option><?php endforeach; ?></select></div>
        <div class="chips">
            <span class="chip-stat cs-gold">Gestión <?php echo $gestionAnio ?: '—'; ?> abierta</span>
            <span class="chip-stat cs-blue"><?php echo $totalCupos; ?> cupos · <?php echo $totalAsig; ?> asignados · <?php echo $totalPend; ?> pendientes</span>
            <span class="chip-stat cs-green">Cobertura: <?php echo $cobertura; ?>%</span>
        </div>
    </form>
</div>
<div class="tabla-contenedor">
    <div class="table-top">
        <form method="GET" class="search" style="display:flex;">
            <input type="hidden" name="view" value="curso">
            <?php if ($carreraId): ?><input type="hidden" name="carrera_id" value="<?php echo (int)$carreraId; ?>"><?php endif; ?>
            <?php if ($anio): ?><input type="hidden" name="anio" value="<?php echo (int)$anio; ?>"><?php endif; ?>
            <?php if ($paralelo): ?><input type="hidden" name="paralelo" value="<?php echo htmlspecialchars($paralelo); ?>"><?php endif; ?>
            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input name="q" type="text" value="<?php echo htmlspecialchars($q); ?>" placeholder="Filtrar por materia o docente">
        </form>
        <span class="chip-stat cs-blue"><?php echo count($cursos); ?> curso(s) · <?php echo $gestionAnio ?: '—'; ?></span>
    </div>
    <table>
        <thead><tr><th style="width:105px">Código</th><th>Materia</th><th>Carrera</th><th>Curso</th><th style="width:100px">Turno</th><th>Docente</th><th class="acc" style="width:130px">Acciones</th></tr></thead>
        <tbody id="asgBody">
            <?php $hay = false; foreach ($cursos as $c): foreach (($matriz[(int)$c['id']] ?? []) as $m): $hay = true; ?>
            <tr>
                <td><span class="code-badge"><?php echo htmlspecialchars($m['codigo']); ?></span></td>
                <td><span class="nm"><?php echo htmlspecialchars($m['nombre']); ?></span></td>
                <td><span class="nm"><?php echo htmlspecialchars($c['carrera_nombre']); ?></span></td>
                <td><?php echo ordinal_anio((int)$c['anio_carrera']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($c['turno'])); ?></td>
                <td><?php echo !empty($m['docente_nombre']) ? '<span class="nm">' . htmlspecialchars($m['docente_nombre']) . '</span>' : '<span class="tag tag-amber">Sin asignar</span>'; ?></td>
                <td class="acc"><div class="acc-wrap">
                    <?php if (!empty($m['docente_id'])): ?>
                    <form method="POST" onsubmit="return confirm('¿Quitar esta asignación?');" style="display:inline;"><?php echo csrf_campo(); ?><input type="hidden" name="accion" value="quitar"><input type="hidden" name="view" value="curso"><input type="hidden" name="docente_id" value="<?php echo (int)$m['docente_id']; ?>"><input type="hidden" name="materia_id" value="<?php echo (int)$m['id']; ?>"><input type="hidden" name="curso_id" value="<?php echo (int)$c['id']; ?>"><button class="btn-mini" type="submit">Quitar</button></form>
                    <?php else: ?>
                    <button type="button" class="btn-mini" data-open-asg>Asignar</button>
                    <?php endif; ?>
                </div></td>
            </tr>
            <?php endforeach; endforeach; ?>
        </tbody>
    </table>
    <div class="empty <?php echo $hay ? 'hidden' : ''; ?>" id="asgEmpty">
        <b>Sin materias en este nivel</b>
        <p>La malla curricular de este curso aún no tiene materias cargadas.</p>
    </div>
</div>
<?php else: ?>
<div class="filter-bar">
    <form method="GET" style="display:contents;">
        <input type="hidden" name="view" value="docente">
        <div class="form-group" style="flex:1 1 300px;max-width:none;position:relative;"><label>Docente</label>
            <div class="search" style="width:100%;max-width:none;">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input name="dq" type="text" value="<?php echo htmlspecialchars($docQuery); ?>" placeholder="Nombre o C.I. del docente" autocomplete="off" list="docSug">
            </div>
            <datalist id="docSug"><?php foreach ($docSugerencias as $d): ?><option value="<?php echo htmlspecialchars($d['nombre_completo']); ?>"><?php echo htmlspecialchars($d['ci']); ?></option><?php endforeach; ?></datalist>
            <?php if (!empty($docSugerencias) && !$docSeleccionado): ?>
            <div class="autocomplete-dropdown">
                <?php foreach ($docSugerencias as $d): ?>
                <a class="autocomplete-item" style="text-decoration:none;color:inherit;" href="<?= BASE_URL ?>/view/admin/asignaciones.php?view=docente&dq=<?php echo urlencode($docQuery); ?>&docente_id=<?php echo (int)$d['id']; ?>">
                    <div><div class="nm"><?php echo htmlspecialchars($d['nombre_completo']); ?></div><div class="sub">C.I. <?php echo htmlspecialchars($d['ci']); ?></div></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;padding-bottom:4px;"><button type="submit" class="btn btn-primary">Buscar</button></div>
        <div class="chips">
            <span class="chip-stat cs-gold">Gestión <?php echo $gestionAnio ?: '—'; ?> abierta</span>
            <?php if ($docInfo): ?><span class="chip-stat cs-blue"><?php echo count($carga); ?> materia(s)</span><?php endif; ?>
        </div>
    </form>
</div>
<?php if ($docQuery !== '' && empty($docSugerencias)): ?>
<div class="empty"><b>Sin coincidencias</b><p>Sin coincidencias para «<?php echo htmlspecialchars($docQuery); ?>».</p></div>
<?php endif; ?>
<?php if ($docSeleccionado && $docInfo): ?>
<div class="tabla-contenedor">
    <div class="table-top">
        <div><strong><?php echo htmlspecialchars($docInfo['nombre_completo']); ?></strong> <span style="color:var(--text-3);font-size:12.5px;">· <?php echo count($carga); ?> materia(s) en <?php echo $gestionAnio; ?></span></div>
        <span class="chip-stat cs-green"><?php echo count($carga); ?> asignada(s)</span>
    </div>
    <table>
        <thead><tr><th style="width:105px">Código</th><th>Materia</th><th>Carrera</th><th>Curso</th><th style="width:100px">Turno</th><th style="width:90px">Paralelo</th></tr></thead>
        <tbody>
            <?php foreach ($carga as $r): ?>
            <tr>
                <td><span class="code-badge"><?php echo htmlspecialchars($r['codigo']); ?></span></td>
                <td><span class="nm"><?php echo htmlspecialchars($r['materia']); ?></span></td>
                <td><?php echo htmlspecialchars($r['carrera_nombre']); ?></td>
                <td><?php echo ordinal_anio((int)$r['anio_carrera']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($r['turno'])); ?></td>
                <td><?php echo htmlspecialchars($r['paralelo']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($carga)): ?><tr><td colspan="6" style="text-align:center;color:var(--text-3);"><?php echo htmlspecialchars($docInfo['nombre_completo']); ?> no tiene materias asignadas en la gestión <?php echo $gestionAnio; ?>.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php if (empty($carga)): ?>
    <div class="empty"><b>Sin asignaciones</b><p><?php echo htmlspecialchars($docInfo['nombre_completo']); ?> no tiene materias asignadas en la gestión <?php echo $gestionAnio; ?>.</p></div>
    <?php endif; ?>
</div>
<?php elseif ($view === 'docente' && !$docSeleccionado && $docQuery === ''): ?>
<div class="empty"><b>Sin docente seleccionado</b><p>Busque por nombre o C.I. y seleccione para ver la carga.</p></div>
<?php endif; ?>
<?php endif; ?>

<!-- Modal Nueva asignación: Docente → Carrera → Año → Turno → Materia → Paralelo -->
<div class="modal-overlay" id="asgModal">
    <div class="modal" style="max-width:520px">
        <div class="modal-head">
            <div>
                <h3>Nueva asignación</h3>
                <p class="m-sub">Vincula un docente a una materia</p>
            </div>
            <button class="icon-btn" type="button" data-close="asgModal"><svg class="ic ic-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST">
            <?php echo csrf_campo(); ?>
            <input type="hidden" name="accion" value="asignar">
            <div class="form-2col">
                <div class="form-group full"><label>Docente</label><select name="docente_id" required><option value="">—</option><?php foreach ($docentesTodos as $d): ?><option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['nombre_completo']); ?> (<?php echo htmlspecialchars($d['ci']); ?>)</option><?php endforeach; ?></select></div>
                <div class="form-group full"><label>Carrera</label><select name="carrera_id" required><option value="">—</option><?php foreach ($opciones['carreras'] as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $carreraId === (int)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Curso</label><select name="anio" required><option value="">—</option><?php foreach ($opciones['anios'] as $a): ?><option value="<?php echo $a; ?>" <?php echo $anio === (int)$a ? 'selected' : ''; ?>><?php echo ordinal_anio((int)$a); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Turno</label><select name="turno" required><option value="">—</option><?php foreach ($opciones['turnos'] as $t): ?><option value="<?php echo htmlspecialchars($t); ?>"><?php echo ucfirst(htmlspecialchars($t)); ?></option><?php endforeach; ?></select></div>
                <div class="form-group full"><label>Materia</label><select name="materia_id" required><option value="">Seleccione carrera y año…</option><?php foreach ($matModel->getAll() as $m): ?><option value="<?php echo (int)$m['id']; ?>"><?php echo htmlspecialchars($m['codigo'] . ' · ' . $m['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Paralelo</label><select name="paralelo" required><option value="">—</option><?php foreach ($opciones['paralelos'] as $p): ?><option value="<?php echo htmlspecialchars($p); ?>" <?php echo $paralelo === $p ? 'selected' : ''; ?>><?php echo htmlspecialchars($p); ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-close="asgModal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar asignación</button>
            </div>
        </form>
    </div>
</div>
<div class="toast-wrap" id="toasts"></div>
<script>
(function(){
  var m=document.getElementById('asgModal');
  function openM(){ if(m){m.classList.add('active');document.body.classList.add('modal-open');} }
  function closeM(id){ var o=document.getElementById(id); if(o)o.classList.remove('active'); document.body.classList.remove('modal-open'); }
  var b=document.getElementById('btnNewAsg'); if(b)b.addEventListener('click',openM);
  document.querySelectorAll('[data-open-asg]').forEach(function(x){x.addEventListener('click',openM);});
  document.querySelectorAll('[data-close]').forEach(function(x){x.addEventListener('click',function(){closeM(x.getAttribute('data-close'));});});
  if(m)m.addEventListener('click',function(e){ if(e.target===m)closeM('asgModal'); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'){closeM('asgModal');} });
})();
</script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
