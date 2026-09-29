<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/GestionesModel.php';

$cursosModel = new CursosModel();
$estModel = new EstudiantesModel();
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
    if ($accion === 'inscribir_uno' && $gestionId) {
        $estId = (int)($_POST['estudiante_id'] ?? 0);
        $carreraId = (int)($_POST['carrera_id'] ?? 0);
        $anio = (int)($_POST['anio'] ?? 0);
        $turno = trim($_POST['turno'] ?? '');
        $paralelo = trim($_POST['paralelo'] ?? '');
        if ($estId && $carreraId && $anio && $turno && $paralelo) {
            $cursos = $cursosModel->getByFilters($carreraId, $anio, $turno, $paralelo);
            if (!empty($cursos)) {
                $cursoId = (int)$cursos[0]['id'];
                // Validación año anterior: si anio>1 exige inscripción previa en anio-1 (misma carrera, gestión abierta)
                $bloqueado = false;
                if ($anio > 1) {
                    $prev = $cursosModel->getByFilters($carreraId, $anio - 1, null, null);
                    $tienePrevio = false;
                    foreach ($prev as $pc) {
                        if ($estModel->estaInscrito($estId, (int)$pc['id'], $gestionId)) { $tienePrevio = true; break; }
                    }
                    if (!$tienePrevio) $bloqueado = true;
                }
                if ($bloqueado) {
                    redirect('/view/admin/inscripciones.php?msg=blocked&view=curso');
                }
                $estModel->inscribirCurso($estId, $cursoId, $gestionId, 1);
                redirect('/view/admin/inscripciones.php?msg=created&view=curso&carrera_id=' . $carreraId . '&anio=' . $anio . '&turno=' . urlencode($turno) . '&paralelo=' . urlencode($paralelo));
            }
        }
        redirect('/view/admin/inscripciones.php?msg=error&view=curso');
    }
    if ($accion === 'dar_baja' && $gestionId) {
        $estId = (int)($_POST['estudiante_id'] ?? 0);
        $cursoId = (int)($_POST['curso_id'] ?? 0);
        if ($estId && $cursoId) {
            $estModel->darDeBaja($estId, $cursoId, $gestionId);
        }
        redirect('/view/admin/inscripciones.php?msg=deleted&view=' . urlencode($_POST['view'] ?? 'curso'));
    }
}

$view = $_GET['view'] ?? 'curso';
if (!in_array($view, ['curso', 'estudiante'], true)) $view = 'curso';

$opciones = $cursosModel->getFilterOptions();

// ---- Vista Por Curso ----
$carreraId = isset($_GET['carrera_id']) && $_GET['carrera_id'] !== '' ? (int)$_GET['carrera_id'] : null;
$anio = isset($_GET['anio']) && $_GET['anio'] !== '' ? (int)$_GET['anio'] : null;
$turno = isset($_GET['turno']) && $_GET['turno'] !== '' ? $_GET['turno'] : null;
$paralelo = isset($_GET['paralelo']) && $_GET['paralelo'] !== '' ? $_GET['paralelo'] : null;
$cursos = $view === 'curso' ? $cursosModel->getByFilters($carreraId, $anio, $turno, $paralelo) : [];

// Candidatos para el modal Inscribir uno (datalist): todos los activos, top 200
$todosEst = $estModel->getAllPaginated(200, 0, '')['data'] ?? [];

// ---- Vista Por Estudiante ----
$estQuery = trim($_GET['q'] ?? '');
$estSeleccionado = isset($_GET['estudiante_id']) && $_GET['estudiante_id'] !== '' ? (int)$_GET['estudiante_id'] : null;
$sugerencias = [];
$historial = [];
$estInfo = null;
if ($view === 'estudiante') {
    if ($estQuery !== '') {
        $sugerencias = array_slice($estModel->getAllPaginated(10, 0, $estQuery)['data'] ?? [], 0, 10);
    }
    if ($estSeleccionado) {
        $estInfo = $estModel->getById($estSeleccionado);
        if ($estInfo && $gestionId) {
            $historial = $estModel->getHistorialPorEstudiante($estSeleccionado);
        }
    }
}

$titulo = 'Inscripciones';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-head">
    <div>
        <h1>Inscripciones</h1>
        <p class="subtitle">Consulta y gestiona matrículas por curso o por estudiante.</p>
    </div>
    <div class="head-actions">
        <div class="seg">
            <a href="<?= BASE_URL ?>/view/admin/inscripciones.php?view=curso" class="<?php echo $view === 'curso' ? 'on' : ''; ?>">Por Curso</a>
            <a href="<?= BASE_URL ?>/view/admin/inscripciones.php?view=estudiante" class="<?php echo $view === 'estudiante' ? 'on' : ''; ?>">Por Estudiante</a>
        </div>
        <?php if ($view === 'curso'): ?>
        <button type="button" class="btn btn-primary" id="btnInscribirUno">
            <svg class="ic ic-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Inscribir uno
        </button>
        <?php endif; ?>
    </div>
</div>
<?= flash_html(['created' => 'Estudiante inscrito exitosamente.', 'deleted' => 'Inscripción eliminada.', 'blocked' => 'Bloqueado: el estudiante no cursó el año anterior.', 'error' => 'No se pudo inscribir. Verifique los datos.']) ?>

<?php if ($view === 'curso'): ?>
<?php
$totalCursos = count($cursos);
$totalInsc = 0;
$conteo = [];
foreach ($cursos as $cc) {
    $n = $gestionId ? count($cursosModel->getEstudiantes((int)$cc['id'], $gestionId)) : 0;
    $conteo[(int)$cc['id']] = $n;
    $totalInsc += $n;
}
?>
<div class="filter-bar">
    <form method="GET" style="display:contents;">
        <input type="hidden" name="view" value="curso">
        <div class="form-group"><label>Carrera</label>
            <select name="carrera_id" onchange="this.form.submit()"><option value="">Todas</option>
            <?php foreach ($opciones['carreras'] as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php echo $carreraId === (int)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option>
            <?php endforeach; ?></select></div>
        <div class="form-group"><label>Curso</label>
            <select name="anio" onchange="this.form.submit()"><option value="">Todos</option>
            <?php foreach ($opciones['anios'] as $a): ?>
            <option value="<?php echo $a; ?>" <?php echo $anio === (int)$a ? 'selected' : ''; ?>><?php echo ordinal_anio((int)$a); ?></option>
            <?php endforeach; ?></select></div>
        <div class="form-group"><label>Turno</label>
            <select name="turno" onchange="this.form.submit()"><option value="">Todos</option>
            <?php foreach ($opciones['turnos'] as $t): ?>
            <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $turno === $t ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($t)); ?></option>
            <?php endforeach; ?></select></div>
        <div class="form-group"><label>Paralelo</label>
            <select name="paralelo" onchange="this.form.submit()"><option value="">Todos</option>
            <?php foreach ($opciones['paralelos'] as $p): ?>
            <option value="<?php echo htmlspecialchars($p); ?>" <?php echo $paralelo === $p ? 'selected' : ''; ?>><?php echo htmlspecialchars($p); ?></option>
            <?php endforeach; ?></select></div>
        <div class="chips">
            <span class="chip-stat cs-gold">Gestión <?php echo $gestionAnio ?: '—'; ?> abierta</span>
            <span class="chip-stat cs-green"><?php echo $totalInsc; ?> inscritos</span>
        </div>
    </form>
</div>
<div class="tabla-contenedor">
    <div class="table-top">
        <div class="search">
            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input id="insSearch" type="text" placeholder="Filtrar por nombre o C.I.">
        </div>
        <span class="chip-stat cs-blue"><?php echo $totalCursos; ?> curso(s) · Gestión <?php echo $gestionAnio ?: '—'; ?></span>
    </div>
    <table>
        <thead><tr><th style="width:44px">#</th><th>Carrera</th><th>Curso</th><th>Turno</th><th>Paralelo</th><th>Inscritos</th><th class="acc">Acciones</th></tr></thead>
        <tbody id="insBody">
            <?php $i = 0; foreach ($cursos as $c): $i++; $insc = $gestionId ? $cursosModel->getEstudiantes((int)$c['id'], $gestionId) : []; ?>
            <tr data-search="<?php echo htmlspecialchars(mb_strtolower($c['carrera_nombre'] . ' ' . ordinal_anio((int)$c['anio_carrera']))); ?>">
                <td><?php echo $i; ?></td>
                <td><span class="nm"><?php echo htmlspecialchars($c['carrera_nombre']); ?></span></td>
                <td><?php echo ordinal_anio((int)$c['anio_carrera']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($c['turno'])); ?></td>
                <td><span class="code-badge"><?php echo htmlspecialchars($c['paralelo']); ?></span></td>
                <td><span class="tag tag-blue"><?php echo count($insc); ?> inscritos</span><span class="sub"><?php foreach (array_slice($insc, 0, 3) as $e) echo htmlspecialchars($e['nombre_completo']) . '<br>'; ?><?php if (count($insc) > 3) echo '+' . (count($insc) - 3) . ' más'; ?></span></td>
                <td class="acc"><div class="acc-wrap">
                    <form method="GET" action="<?= BASE_URL ?>/view/admin/inscripciones.php" style="display:inline;">
                        <input type="hidden" name="view" value="estudiante">
                        <button type="submit" class="btn-mini" title="Ver detalle">Ver</button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="empty <?php echo empty($cursos) ? '' : 'hidden'; ?>" id="insEmpty">
        <b>Sin inscritos en este curso</b>
        <p>Todavía no hay estudiantes matriculados en la combinación seleccionada.</p>
    </div>
</div>
<?php else: ?>
<div class="filter-bar">
    <form method="GET" style="display:contents;">
        <input type="hidden" name="view" value="estudiante">
        <div class="form-group" style="flex:1 1 300px;max-width:none;position:relative;"><label>Buscar estudiante</label>
            <div class="search" style="width:100%;max-width:none;">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input name="q" type="text" value="<?php echo htmlspecialchars($estQuery); ?>" placeholder="Nombre o C.I." autocomplete="off" list="estSug">
            </div>
            <datalist id="estSug">
                <?php foreach ($sugerencias as $s): ?>
                <option value="<?php echo htmlspecialchars($s['nombre_completo']); ?>"><?php echo htmlspecialchars($s['ci']); ?></option>
                <?php endforeach; ?>
            </datalist>
            <?php if (!empty($sugerencias) && !$estSeleccionado): ?>
            <div class="autocomplete-dropdown" id="estDropdown">
                <?php foreach ($sugerencias as $s): ?>
                <a class="autocomplete-item" style="text-decoration:none;color:inherit;" href="<?= BASE_URL ?>/view/admin/inscripciones.php?view=estudiante&q=<?php echo urlencode($estQuery); ?>&estudiante_id=<?php echo (int)$s['id']; ?>">
                    <div><div class="nm"><?php echo htmlspecialchars($s['nombre_completo']); ?></div><div class="sub">C.I. <?php echo htmlspecialchars($s['ci']); ?></div></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;padding-bottom:4px;">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>
</div>
<?php if ($estQuery !== '' && empty($sugerencias)): ?>
<div class="empty"><b>Sin coincidencias</b><p>Sin coincidencias para «<?php echo htmlspecialchars($estQuery); ?>».</p></div>
<?php endif; ?>
<?php if ($estSeleccionado && $estInfo): ?>
<div class="tabla-contenedor">
    <div class="table-top">
        <div><strong><?php echo htmlspecialchars($estInfo['nombre_completo']); ?></strong> <span style="color:var(--text-3);font-size:12.5px;">C.I. <?php echo htmlspecialchars($estInfo['ci']); ?></span></div>
        <span class="chip-stat cs-green"><?php echo count($historial); ?> inscripción(es)</span>
    </div>
    <table>
        <thead><tr><th style="width:44px">#</th><th>Gestión</th><th>Carrera</th><th>Curso</th><th>Turno</th><th>Paralelo</th><th>Estado</th><th class="acc">Acción</th></tr></thead>
        <tbody>
            <?php foreach ($historial as $idx => $h): ?>
            <tr>
                <td><?php echo $idx + 1; ?></td>
                <td><span class="code-badge"><?php echo htmlspecialchars((string)$h['gestion_anio']); ?></span></td>
                <td><span class="nm"><?php echo htmlspecialchars($h['carrera_nombre']); ?></span></td>
                <td><?php echo ordinal_anio((int)$h['anio_carrera']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($h['turno'])); ?></td>
                <td><?php echo htmlspecialchars($h['paralelo']); ?></td>
                <td><span class="tag tag-blue">Activo</span></td>
                <td class="acc"><div class="acc-wrap"><form method="POST" onsubmit="return confirm('¿Dar de baja esta inscripción?');"><?php echo csrf_campo(); ?><input type="hidden" name="accion" value="dar_baja"><input type="hidden" name="view" value="estudiante"><input type="hidden" name="estudiante_id" value="<?php echo $estSeleccionado; ?>"><input type="hidden" name="curso_id" value="<?php echo (int)$h['curso_id']; ?>"><button class="btn-mini" type="submit">Dar baja</button></form></div></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($historial)): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--text-3);">Este estudiante aún no tiene inscripciones.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php elseif ($view === 'estudiante' && !$estSeleccionado && $estQuery === ''): ?>
<div class="empty"><b>Sin historial</b><p>Seleccione un estudiante para ver sus inscripciones.</p></div>
<?php endif; ?>
<?php endif; ?>

<!-- Modal Inscribir uno -->
<div class="modal-overlay" id="insOneModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-head">
            <div>
                <h3>Inscribir estudiante</h3>
                <p class="m-sub">Completa los datos de la inscripción y busca al estudiante</p>
            </div>
            <button class="icon-btn" type="button" data-close="insOneModal"><svg class="ic ic-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST">
            <?php echo csrf_campo(); ?>
            <input type="hidden" name="accion" value="inscribir_uno">
            <div class="readonly-line">Gestión <b><?php echo $gestionAnio ?: '—'; ?></b> (abierta)</div>
            <div class="form-2col">
                <div class="form-group"><label>Carrera</label><select name="carrera_id" required><option value="">—</option><?php foreach ($opciones['carreras'] as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Año</label><select name="anio" required><option value="">—</option><?php foreach ($opciones['anios'] as $a): ?><option value="<?php echo $a; ?>"><?php echo ordinal_anio((int)$a); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Turno</label><select name="turno" required><option value="">—</option><?php foreach ($opciones['turnos'] as $t): ?><option value="<?php echo htmlspecialchars($t); ?>"><?php echo ucfirst(htmlspecialchars($t)); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Paralelo</label><select name="paralelo" required><option value="">—</option><?php foreach ($opciones['paralelos'] as $p): ?><option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option><?php endforeach; ?></select></div>
                <div class="form-group full"><label>Matrícula</label><input type="text" placeholder="Número de matrícula (opcional)" disabled><div class="sub" style="font-size:11.5px;color:var(--text-3);">Si cursa 2do/3er año se valida inscripción en el año anterior.</div></div>
            </div>
            <div class="form-group" style="position:relative;margin-top:12px;"><label>Estudiante</label>
                <div class="search" style="width:100%;max-width:none;">
                    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                    <input id="insOneFilter" type="text" placeholder="Buscar por nombre o C.I." autocomplete="off">
                </div>
                <select name="estudiante_id" id="insOneSelect" required size="5" style="margin-top:8px;">
                    <option value="">Seleccione…</option>
                    <?php foreach ($todosEst as $t): ?><option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['nombre_completo']); ?> (<?php echo htmlspecialchars($t['ci']); ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-close="insOneModal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Inscribir</button>
            </div>
        </form>
    </div>
</div>
<div class="toast-wrap" id="toasts"></div>
<script>
(function(){
  var m=document.getElementById('insOneModal');
  function openM(){ if(m){m.classList.add('active');document.body.classList.add('modal-open');} }
  function closeM(id){ var o=document.getElementById(id); if(o)o.classList.remove('active'); document.body.classList.remove('modal-open'); }
  var b=document.getElementById('btnInscribirUno'); if(b)b.addEventListener('click',openM);
  document.querySelectorAll('[data-close]').forEach(function(x){x.addEventListener('click',function(){closeM(x.getAttribute('data-close'));});});
  if(m)m.addEventListener('click',function(e){ if(e.target===m)closeM('insOneModal'); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'){closeM('insOneModal');} });
  var s=document.getElementById('insSearch');
  if(s)s.addEventListener('input',function(){
    var q=s.value.toLowerCase();
    document.querySelectorAll('#insBody tr').forEach(function(r){
      var t=(r.textContent||'').toLowerCase();
      r.style.display=(!q||t.indexOf(q)>-1)?'':'none';
    });
  });
  var f=document.getElementById('insOneFilter'), sel=document.getElementById('insOneSelect');
  if(f&&sel)f.addEventListener('input',function(){
    var q=f.value.toLowerCase();
    Array.prototype.forEach.call(sel.options,function(o){
      if(!o.value){o.hidden=false;return;}
      o.hidden=!!q&&o.text.toLowerCase().indexOf(q)===-1;
    });
  });
})();
</script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
