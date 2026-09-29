<?php
/**
 * PLANILLA: CENTRALIZADOR DE CALIFICACIONES (anverso por folios + reverso al final)
 * Reglas aplicadas:
 *  - Hoja OFICIO 21,59 x 35,56 cm, impresión horizontal.
 *  - 20 estudiantes por folio; último folio se rellena con filas vacías hasta 20.
 *  - Si hay +20 alumnos se repite el formato completo de anverso en folios sucesivos.
 *  - El reverso (firmas por materia + estadísticas) va SIEMPRE al final.
 *  - LIBRO N° calculado: semestral = semestre correlativo (1-6); parciales = año de carrera (1-3).
 *  - FOLIO N° = número de plana de anverso (1, 2, 3...).
 *  - GESTIÓN estampada como "I/2026" (periodo/año).
 *  - Instancia aprobada => nota final 61 (aunque haya sacado 100).
 *  - _base.php ya incluye config/auth/csrf y ejecuta auth_guard('admin').
 */
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/NotasModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';
require_once __DIR__ . '/../../model/GestionesModel.php';

$cursosModel    = new CursosModel();
$notasModel     = new NotasModel();
$gestionesModel = new GestionesModel();

/* ───────── Gestiones (selector; cerradas = solo reimpresión) ───────── */
$gestiones      = $gestionesModel->getAll() ?: [];
$gestionAbierta = $gestionesModel->getAbierta();
$gestionIdSel   = isset($_GET['gestion_id']) && $_GET['gestion_id'] !== ''
    ? (int)$_GET['gestion_id']
    : ($gestionAbierta ? (int)$gestionAbierta['id'] : 0);
$gestionSel = null;
foreach ($gestiones as $g) if ((int)$g['id'] === $gestionIdSel) { $gestionSel = $g; break; }
if (!$gestionSel && $gestionAbierta) { $gestionSel = $gestionAbierta; $gestionIdSel = (int)$gestionAbierta['id']; }
$esAbierta = $gestionSel && ($gestionSel['estado'] ?? '') === 'abierta';

/* ───────── Filtros de cohorte ───────── */
$carreraId = isset($_GET['carrera_id']) && $_GET['carrera_id'] !== '' ? (int)$_GET['carrera_id'] : null;
$cursoId   = isset($_GET['curso_id'])   && $_GET['curso_id']   !== '' ? (int)$_GET['curso_id']   : 0;
$periodoSel = isset($_GET['periodo']) && $_GET['periodo'] !== '' ? (int)$_GET['periodo'] : 0; // 1..2 semestral | 1..4 parciales

$opciones = $cursosModel->getFilterOptions();
$cursos   = $cursosModel->getByFilters($carreraId, null, null, null);
$curso    = $cursoId ? $cursosModel->getById($cursoId) : null;

/* ───────── Régimen, ciclo y periodo ───────── */
$tipo  = $curso ? ($curso['carrera_tipo'] ?? 'anual') : 'anual';
$esSemestral = ($tipo === 'semestral');
$periodosPosibles = $esSemestral ? [1, 2] : [1, 2, 3, 4];
if (!in_array($periodoSel, $periodosPosibles, true)) $periodoSel = $periodosPosibles[0];
$ciclo = ParcialPeriodoModel::ciclo($tipo); // nombres de actividades de nota del periodo

/* Nombre de actividad que corresponde al periodo elegido */
if ($esSemestral) {
    // En semestral el ciclo trae los cortes del semestre (1er/2do Parcial); se promedian.
    $nombresPeriodo = $ciclo;
} else {
    // En parciales cada periodo es UNA nota con nombre "k-er Parcial".
    $nombresPeriodo = [$ciclo[$periodoSel - 1] ?? 'Parcial'];
}

/* ───────── LIBRO calculado ───────── */
$anioCarrera = $curso ? (int)($curso['anio_carrera'] ?? 1) : 1;
$libro = $esSemestral ? (($anioCarrera - 1) * 2 + $periodoSel) : $anioCarrera;

/* ───────── Sello GESTIÓN: I/2026 ───────── */
function romano($n) { $r = ['I','II','III','IV','V','VI']; return $r[$n - 1] ?? (string)$n; }
$selloGestion = ($gestionSel ? ($esSemestral ? romano($periodoSel) : (string)$periodoSel) : '') . '/' . ($gestionSel ? $gestionSel['anio'] : '');

/* ───────── Datos del curso ───────── */
$estudiantes = []; $materias = []; $docentePorMateria = []; $notasMatriz = []; $estadosEst = [];
$totalInscritos = 0; $aprobados = 0; $reprobados = 0; $abandono = 0; $sinNotas = 0;
$NOTA_APROB = 61; $FILAS_POR_FOLIO = 20;

if ($curso && $gestionIdSel) {
    $estudiantes = $cursosModel->getEstudiantes($cursoId, $gestionIdSel);
    foreach ($cursosModel->getMateriasImpartidas($cursoId, $gestionIdSel) as $r) {
        $mid = (int)$r['id'];
        $materias[$mid] = ['id' => $mid, 'nombre' => $r['nombre'], 'codigo' => $r['codigo']];
        $docentePorMateria[$mid] = $r['docente_nombre'] ?? null;
    }

    foreach ($estudiantes as $est) {
        $eid = (int)$est['id'];
        foreach ($materias as $mid => $mat) {
            $all = $notasModel->getNotasEstudiante($eid, $cursoId, $mid);
            $vals = []; $instancia = null;
            foreach ($all as $n) {
                $act  = $n['nombre_actividad'] ?? '';
                $tipoN = $n['tipo'] ?? '';
                // Instancia / prueba de recuperación
                if ($tipoN === 'instancia' || stripos($act, 'instancia') !== false) {
                    $instancia = $n['nota'] !== null ? (float)$n['nota'] : null;
                    continue;
                }
                if ($tipoN !== 'parcial' || !in_array($act, $nombresPeriodo, true)) continue;
                // En semestral distinguimos semestre si la nota lo trae; si no, se asume del curso.
                if ($esSemestral && isset($n['semestre']) && (int)$n['semestre'] !== $periodoSel) continue;
                if ($n['nota'] !== null) $vals[] = (float)$n['nota'];
            }
            $final = null;
            if (!empty($vals)) $final = round(array_sum($vals) / count($vals), 1);
            // Regla de instancia: aprobada => 61 fijo; reprobada => conserva el promedio (reprobado).
            if ($instancia !== null && $instancia >= $NOTA_APROB) $final = (float)$NOTA_APROB;
            $notasMatriz[$eid][$mid] = $final;
        }
        // Estado global del estudiante en el periodo
        $tiene = false; $todasAp = true;
        foreach ($materias as $mid => $mat) {
            $v = $notasMatriz[$eid][$mid] ?? null;
            if ($v === null) continue;
            $tiene = true;
            if ($v < $NOTA_APROB) $todasAp = false;
        }
        $estadosEst[$eid] = !$tiene ? 'NP' : ($todasAp ? 'AP' : 'RE');
    }

    /* Estadísticas del periodo */
    $totalInscritos = count($estudiantes);
    foreach ($estudiantes as $est) {
        if (strtolower($est['estado'] ?? 'activo') === 'abandono') { $abandono++; continue; }
        $e = $estadosEst[(int)$est['id']] ?? 'NP';
        if ($e === 'AP') $aprobados++;
        elseif ($e === 'RE') $reprobados++;
        else $sinNotas++;
    }
}

/* ───────── Paginación en folios de 20 (relleno incluido) ───────── */
$folios = [];
if ($curso) {
    $chunks = array_chunk($estudiantes, $FILAS_POR_FOLIO) ?: [[]];
    foreach ($chunks as $i => $chunk) {
        while (count($chunk) < $FILAS_POR_FOLIO) $chunk[] = null; // filas vacías del formulario
        $folios[] = ['n' => $i + 1, 'filas' => $chunk, 'inicio' => $i * $FILAS_POR_FOLIO];
    }
}

$titulo = 'Centralizador de calificaciones';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
<style>
/* ══════════ HOJA OFICIO HORIZONTAL ══════════ */
@page { size: 355.6mm 215.9mm; margin: 9mm; }
@media print {
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
    .page { page-break-after: always; box-shadow: none !important; border: none !important; margin: 0 !important; }
    .page:last-child { page-break-after: auto; }
}
.page {
    background: #fff; color: #14181d; padding: 8mm 10mm; margin: 0 auto 22px;
    max-width: 1250px; border: 1px solid #cfd6de; border-radius: 4px;
    box-shadow: 0 2px 10px rgba(20,40,80,.12); overflow-x: auto;
    font-family: Arimo, Arial, Helvetica, sans-serif;
}
.codreg { display: flex; justify-content: flex-end; gap: 8px; font: 700 11.5px Arimo, sans-serif; margin-bottom: 4px; }
.p1-head { display: flex; align-items: flex-start; gap: 16px; }
.gov { display: flex; align-items: center; gap: 10px; flex: none; width: 320px; }
.gov .esc { width: 54px; height: 54px; object-fit: contain; }
.govtxt .l1 { display: block; font: 700 7.5px Arimo; letter-spacing: .14em; text-transform: uppercase; color: #5c5c5c; }
.govtxt .l2 { display: block; font: 700 20px Tinos, Times, serif; color: #3d3d3d; }
.minedu { font: 700 8.5px Arimo; letter-spacing: .06em; color: #6b6b6b; line-height: 1.35; }
.doctitle { flex: 1; text-align: center; font: 800 19px Arimo; letter-spacing: .06em; padding-top: 12px; }
.librobox { border: 2px solid #141414; border-collapse: collapse; flex: none; }
.librobox td { border: 1px solid #141414; font: 700 11px Arimo; padding: 6px 12px; }
.librobox td.val { width: 70px; text-align: center; border-left: 2px solid #141414; }
.dept-band { display: inline-block; background: #f6f200; width: 300px; text-align: center; font: 700 11px Arimo; padding: 4px 0; margin: 8px 0 6px; }
.fields { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 2px 10px; font: 700 11.5px Arimo; margin-bottom: 4px; }
.fields .rgt { display: grid; row-gap: 12px; }
table.central { border-collapse: collapse; width: 100%; table-layout: auto; }
.central th, .central td { border: 1px solid #141414; }
.central thead th { background: #c8d6ac; font: 700 10px Arimo; text-align: center; vertical-align: middle; padding: 4px; }
.central thead th.code { font-size: 9px; }
.central thead td.meta { background: #fff; padding: 0; }
.metain { display: flex; gap: 8px; align-items: center; font: 700 11px Arimo; padding: 4px 8px; min-height: 24px; }
.vt { writing-mode: vertical-rl; transform: rotate(180deg); display: inline-block; font: 700 9.5px Arimo; letter-spacing: .05em; white-space: normal; }
.central tbody td { padding: 3px 6px; font: 400 11.5px Arimo; height: 22px; white-space: nowrap; text-align: center; }
.central tbody td.nombre { text-align: left; white-space: normal; min-width: 240px; }
.central tbody td.n { font-weight: 700; background: #f4f6f2; width: 34px; }
td.grade { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 11px; }
td.grade.rep { color: #c0392b; font-weight: 700; }
td.grade.np { color: #b45309; font-weight: 700; }
td.estado { font: 800 11px Arimo; background: #f4f6f2; }
td.estado.st-AP { color: #1e7e34; } td.estado.st-RE { color: #c0392b; } td.estado.st-NP { color: #b45309; }
.legend { margin: 10px 0 0 40px; font: 600 10.5px Arimo; line-height: 1.55; }
.signs { display: flex; justify-content: space-between; gap: 30px; padding: 0 60px; margin-top: 70px; }
.sign { width: 240px; text-align: center; }
.sign .line { border-top: 1.6px solid #141414; margin-bottom: 5px; }
.sign .cap { font: 700 9.5px Arimo; letter-spacing: .08em; }
/* Reverso */
.docentes { display: grid; grid-template-columns: repeat(3, 1fr); row-gap: 110px; margin: 50px 40px 30px; }
.doc-item { text-align: center; }
.doc-item .dn { font: 700 10.5px Arimo; }
.doc-item .dm { font: 700 10.5px Arimo; margin-top: 7px; }
.doc-item.seventh { grid-column: 3; }
.stats-title { text-align: center; font: 800 13px Arimo; letter-spacing: .06em; margin: 50px 0 10px; }
table.stats { border-collapse: collapse; margin: 0 auto; width: 470px; }
.stats th, .stats td { border: 1px solid #141414; font: 700 10px Arimo; padding: 6px 9px; }
.stats thead th { background: #c8d6ac; }
.stats td.det { text-align: left; }
.stats td.c { text-align: center; width: 80px; }
/* Chrome del sistema (no imprime) */
.chips-bar { display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0 10px; }
.chip-stat { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border-radius: 999px; font: 600 12.5px 'Inter', sans-serif; border: 1px solid; }
.cs-blue { background: rgba(79,140,255,.12); color: var(--accent-2); border-color: rgba(79,140,255,.35); }
.cs-green { background: rgba(52,211,153,.12); color: var(--green); border-color: rgba(52,211,153,.35); }
.cs-gold { background: rgba(245,184,91,.10); color: var(--gold-light); border-color: rgba(245,184,91,.40); }
.cs-red { background: rgba(248,113,113,.12); color: var(--red); border-color: rgba(248,113,113,.35); }
.warn-box { background: rgba(251,191,36,.12); border: 1px solid rgba(251,191,36,.4); border-left: 3px solid var(--amber); color: var(--amber); padding: 12px 16px; border-radius: 10px; margin: 12px 0; font-size: 13px; }
.action-bar { display: flex; gap: 10px; align-items: center; margin: 14px 0; flex-wrap: wrap; }
.empty-state { max-width: 640px; margin: 40px auto; text-align: center; padding: 48px 24px; background: var(--surface); border: 1px dashed var(--border); border-radius: var(--radius); }
</style>

<div class="page-head" style="margin-bottom:18px;">
    <div>
        <h1>Centralizador de calificaciones</h1>
        <p class="subtitle">
            <?php if ($gestionSel): ?>
                Gestión <?php echo htmlspecialchars($gestionSel['anio']); ?>
                <?php if (!$esAbierta): ?><span class="badge badge-cerrado">cerrada · solo reimpresión</span><?php endif; ?>
            <?php else: ?> Sin gestión abierta <?php endif; ?>
            · Hoja Oficio horizontal · 20 alumnos por folio · reverso al final.
        </p>
    </div>
</div>

<div class="form-container" style="max-width:1000px;">
    <form method="GET" style="display:grid;grid-template-columns:repeat(4,1fr) auto;gap:12px;align-items:end;">
        <div class="form-group"><label>Gestión</label>
            <select name="gestion_id">
                <?php foreach ($gestiones as $g): ?>
                    <option value="<?php echo (int)$g['id']; ?>" <?php echo $gestionIdSel === (int)$g['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g['anio']); ?><?php echo ($g['estado'] ?? '') === 'abierta' ? ' (abierta)' : ' (cerrada)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Carrera</label>
            <select name="carrera_id">
                <option value="">Todas</option>
                <?php foreach ($opciones['carreras'] as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>" <?php echo $carreraId === (int)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Curso</label>
            <select name="curso_id">
                <option value="">— Elegí —</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>" <?php echo $cursoId === (int)$c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['carrera_nombre']); ?> · <?php echo (int)$c['anio_carrera']; ?>º · <?php echo htmlspecialchars($c['turno']); ?> · <?php echo htmlspecialchars($c['paralelo']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Periodo</label>
            <select name="periodo" <?php echo !$curso ? 'disabled' : ''; ?>>
                <?php foreach ($periodosPosibles as $p): ?>
                    <option value="<?php echo $p; ?>" <?php echo $periodoSel === $p ? 'selected' : ''; ?>>
                        <?php echo $esSemestral ? 'Semestre ' . romano($p) : $p . 'er Parcial'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Generar planilla</button>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/view/admin/planilla_centralizador.php">Limpiar</a>
        </div>
    </form>
</div>

<?php if (!$curso): ?>
    <div class="empty-state">
        <h3 style="color:var(--text);font-size:16px;margin-bottom:8px;">Elegí un curso para previsualizar la planilla</h3>
        <p style="color:var(--text-3);font-size:13.5px;">
            Se generará el anverso (folios de 20 alumnos) y el reverso con firmas y estadísticas,<br>
            listo para imprimir en hoja Oficio horizontal.
        </p>
    </div>
<?php else: ?>

    <?php $sinDocente = array_filter($materias, fn($m) => empty($docentePorMateria[$m['id']])); ?>
    <div class="no-print">
        <div class="chips-bar">
            <span class="chip-stat cs-blue"><?php echo htmlspecialchars($curso['carrera_nombre']); ?> · <?php echo (int)$curso['anio_carrera']; ?>º · <?php echo htmlspecialchars($curso['turno']); ?> · Paralelo <?php echo htmlspecialchars($curso['paralelo']); ?></span>
            <span class="chip-stat cs-gold">LIBRO <?php echo (int)$libro; ?> · GESTIÓN <?php echo htmlspecialchars($selloGestion); ?></span>
            <span class="chip-stat cs-green"><?php echo (int)$totalInscritos; ?> inscritos · <?php echo count($folios); ?> folio(s)</span>
            <?php if ($abandono > 0): ?><span class="chip-stat cs-red"><?php echo (int)$abandono; ?> abandono(s)</span><?php endif; ?>
            <?php if (!empty($sinDocente)): ?><span class="chip-stat cs-red">⚠ <?php echo count($sinDocente); ?> materia(s) sin docente</span><?php endif; ?>
        </div>
        <?php if ($totalInscritos === 0 || !empty($sinDocente)): ?>
            <div class="warn-box">
                <?php if ($totalInscritos === 0): ?>Sin estudiantes inscritos: la planilla saldrá en blanco.<?php endif; ?>
                <?php if (!empty($sinDocente)): ?>Materias sin docente: el reverso mostrará "Sin docente asignado" en esas firmas.<?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="action-bar">
            <button class="btn btn-primary" onclick="window.print()">Imprimir centralizador (Oficio horizontal)</button>
        </div>
    </div>

    <div class="print-area">
    <?php foreach ($folios as $fol): ?>
        <!-- ═══════════ ANVERSO · FOLIO <?php echo $fol['n']; ?> ═══════════ -->
        <div class="page">
            <div class="codreg"><span>Código de Registro:</span><span>80850061</span></div>
            <div class="p1-head">
                <div class="gov">
                    <img class="esc" src="<?= BASE_URL ?>/view/img/escudo_bolivia.png" alt="">
                    <div class="govtxt"><span class="l1">Estado Plurinacional de</span><span class="l2">BOLIVIA</span></div>
                    <div class="minedu">MINISTERIO<br>DE EDUCACIÓN</div>
                </div>
                <div class="doctitle">CENTRALIZADOR DE&nbsp; CALIFICACIONES</div>
                <table class="librobox"><tbody>
                    <tr><td>LIBRO N°</td><td class="val"><?php echo (int)$libro; ?></td></tr>
                    <tr><td>FOLIO N°</td><td class="val"><?php echo (int)$fol['n']; ?></td></tr>
                </tbody></table>
            </div>
            <div class="dept-band">Cochabamba - Bolivia</div>
            <div class="fields">
                <span>INSTITUCIÓN: INSTITUTO TECNOLÓGICO "PACCIOLI"</span>
                <span style="justify-self:center;">R.M. 0535/2023</span>
                <span class="rgt"><span>TURNO: <?php echo htmlspecialchars(strtoupper($curso['turno'] ?? '')); ?></span><span>CARÁCTER: PRIVADO</span></span>
            </div>

            <table class="central">
                <thead>
                    <tr>
                        <td class="meta" colspan="2"><div class="metain"><b>GESTIÓN:</b><span><?php echo htmlspecialchars($selloGestion); ?></span></div></td>
                        <th rowspan="6" style="width:46px;"><span class="vt">CÉDULA DE IDENTIDAD</span></th>
                        <?php foreach ($materias as $m): ?><th class="code"><?php echo htmlspecialchars($m['codigo']); ?></th><?php endforeach; ?>
                        <th rowspan="6" style="width:52px;"><span class="vt">ESTADO</span></th>
                        <th rowspan="6" style="width:110px;"><span class="vt">OBSERVACIONES</span></th>
                    </tr>
                    <tr>
                        <td class="meta" colspan="2"><div class="metain"><b>NIVEL:</b><span>TÉCNICO SUPERIOR</span></div></td>
                        <?php foreach ($materias as $m): ?><th rowspan="5" style="width:auto;"><span class="vt"><?php echo htmlspecialchars(strtoupper($m['nombre'])); ?></span></th><?php endforeach; ?>
                    </tr>
                    <tr><td class="meta" colspan="2"><div class="metain"><b>CARRERA:</b><span><?php echo htmlspecialchars(strtoupper($curso['carrera_nombre'])); ?></span></div></td></tr>
                    <tr><td class="meta" colspan="2"><div class="metain"><b>RÉGIMEN:</b><span><?php echo htmlspecialchars(strtoupper($esSemestral ? 'SEMESTRAL' : 'ANUALIZADO')); ?></span></div></td></tr>
                    <tr><td class="meta" colspan="2"><div class="metain"><b>CURSO:</b><span><?php echo htmlspecialchars(strtoupper(romano($anioCarrera))); ?></span></div></td></tr>
                    <tr><th style="width:34px;">N°</th><th style="text-align:left;">NÓMINA ESTUDIANTES</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($fol['filas'] as $i => $e): $num = $fol['inicio'] + $i + 1; $eid = $e ? (int)$e['id'] : 0; ?>
                        <tr>
                            <td class="n"><?php echo $num; ?></td>
                            <td class="nombre"><?php echo $e ? htmlspecialchars($e['nombre_completo']) : ''; ?></td>
                            <td><?php echo $e ? htmlspecialchars($e['ci']) : ''; ?></td>
                            <?php foreach ($materias as $mid => $m):
                                $v = $e ? ($notasMatriz[$eid][$mid] ?? null) : null;
                                $cls = $v === null ? 'np' : ($v < 61 ? 'rep' : '');
                            ?>
                                <td class="grade <?php echo $v === null && $e ? 'np' : $cls; ?>"><?php echo $v !== null ? number_format($v, 1) : ($e ? 'NP' : ''); ?></td>
                            <?php endforeach; ?>
                            <td class="estado <?php echo $e ? 'st-' . ($estadosEst[$eid] ?? '') : ''; ?>"><?php echo $e ? htmlspecialchars($estadosEst[$eid] ?? '') : ''; ?></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="legend">
                NP = No se Presento<br>AP = Aprobado<br>PRE = Prerequisito<br>RE = Reprobado
            </div>
            <div class="signs">
                <div class="sign"><div class="line"></div><div class="cap">JEFE(A) DE CARRERA</div></div>
                <div class="sign"><div class="line"></div><div class="cap">DIRECTOR(A) ACADÉMICO(A)</div></div>
                <div class="sign"><div class="line"></div><div class="cap">RECTOR(A)</div></div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- ═══════════ REVERSO (siempre al final) ═══════════ -->
    <div class="page">
        <div class="p1-head" style="margin-bottom:10px;">
            <div class="gov">
                <img class="esc" src="<?= BASE_URL ?>/view/img/escudo_bolivia.png" alt="">
                <div class="govtxt"><span class="l1">Estado Plurinacional de</span><span class="l2">BOLIVIA</span></div>
                <div class="minedu">MINISTERIO<br>DE EDUCACIÓN</div>
            </div>
            <div class="doctitle">CENTRALIZADOR DE&nbsp; CALIFICACIONES</div>
        </div>
        <div class="docentes">
            <?php $i = 0; foreach ($materias as $m): $i++; ?>
                <div class="doc-item <?php echo $i === 7 ? 'seventh' : ''; ?>">
                    <div class="dn"><?php echo htmlspecialchars($docentePorMateria[$m['id']] ? strtoupper($docentePorMateria[$m['id']]) : 'SIN DOCENTE ASIGNADO'); ?></div>
                    <div class="dm"><?php echo htmlspecialchars(strtoupper($m['nombre'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="stats-title">ESTADISTICAS</div>
        <table class="stats">
            <thead><tr><th>DETALLE</th><th>CANTIDAD</th><th>%</th></tr></thead>
            <tbody>
                <?php $pct = fn($n) => $totalInscritos > 0 ? round($n / $totalInscritos * 100, 1) : 0; ?>
                <tr><td class="det">ESTUDIANTES INSCRITOS</td><td class="c"><?php echo (int)$totalInscritos; ?></td><td class="c">100%</td></tr>
                <tr><td class="det">ESTUDIANTES APROBADOS</td><td class="c"><?php echo (int)$aprobados; ?></td><td class="c"><?php echo $pct($aprobados); ?>%</td></tr>
                <tr><td class="det">ESTUDIANTES REPROBADOS</td><td class="c"><?php echo (int)$reprobados; ?></td><td class="c"><?php echo $pct($reprobados); ?>%</td></tr>
                <tr><td class="det">ABANDONO</td><td class="c"><?php echo (int)$abandono; ?></td><td class="c"><?php echo $pct($abandono); ?>%</td></tr>
            </tbody>
        </table>
    </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>