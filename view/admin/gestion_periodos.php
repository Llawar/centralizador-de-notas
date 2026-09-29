<?php
/**
 * GESTIÓN DE PERÍODOS (apertura/cierre de parciales por curso)
 * Reglas aplicadas:
 *  - Solo la GESTIÓN ABIERTA permite abrir/cerrar periodos (cerradas = solo lectura).
 *  - Secuencialidad: el parcial N solo se abre si el N-1 está ENVIADO o CERRADO.
 *  - Un parcial ENVIADO solo se reabre con confirmación explícita.
 *  - Al cerrar se avisa cuántas materias quedan sin enviar notas.
 *  - Tablas separadas por régimen (semestral / anualizado) para que las columnas
 *    de parciales queden alineadas y etiquetadas.
 *  - _base.php ya incluye config/auth/csrf/flash y ejecuta auth_guard('admin').
 */
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';
require_once __DIR__ . '/../../model/GestionesModel.php';

$cursosModel    = new CursosModel();
$parcialModel   = new ParcialPeriodoModel();
$gestionesModel = new GestionesModel();
$adminId        = (int) $_SESSION['user_id'];

/* ───────── Estado de un parcial a partir del resumen del modelo ───────── */
function estadoParcial(?array $d): string {
    $d     = $d ?? [];
    $total = (int)($d['total']    ?? 0);
    $ab    = (int)($d['abiertos'] ?? 0);
    $env   = (int)($d['enviados'] ?? 0);
    $cie   = (int)($d['cerrados'] ?? 0);
    if ($ab > 0)                                   return 'ABIERTO';
    if ($total > 0 && $env >= $total)              return 'ENVIADO';
    if ($total > 0 && ($env + $cie) >= $total)     return 'CERRADO';
    return 'SIN_ABRIR';
}
function datosParcial(?array $d): array {
    $d = $d ?? [];
    return [
        'total'   => (int)($d['total']    ?? 0),
        'env'     => (int)($d['enviados'] ?? 0),
        'pend'    => max(0, (int)($d['total'] ?? 0) - (int)($d['enviados'] ?? 0)),
    ];
}

/* ───────── Filtro de gestión (ahora sí con UI) ───────── */
$gestiones      = $gestionesModel->getAll() ?: [];
$gestionAbierta = $gestionesModel->getAbierta();
$gestionAbiertaAnio = $gestionAbierta ? (int)$gestionAbierta['anio'] : 0;
$gestionFiltro  = isset($_GET['gestion']) && $_GET['gestion'] !== '' ? (int)$_GET['gestion'] : $gestionAbiertaAnio;
$gFiltro = null;
foreach ($gestiones as $g) { if ((int)$g['anio'] === $gestionFiltro) { $gFiltro = $g; break; } }
$gestionId = $gFiltro ? (int)$gFiltro['id'] : 0;
$gestionEsAbierta = ($gFiltro['estado'] ?? '') === 'abierta';
$cursoSelId     = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$msgGet         = $_GET['msg'] ?? '';

/* ───────── Acciones POST ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion  = $_POST['accion']  ?? '';
    $cursoId = (int)($_POST['curso_id'] ?? 0);
    $parcial = $_POST['parcial'] ?? '';

    $back = "/view/admin/gestion_periodos.php?curso_id=$cursoId"
          . ($gestionFiltro ? "&gestion=$gestionFiltro" : '');

    $curso = $cursoId ? $cursosModel->getById($cursoId) : null;
    if (!$curso) {
        redirect("$back&msg=curso_invalido");
    }

    $tipo      = $curso['carrera_tipo'] ?? 'anual';
    $opciones  = ParcialPeriodoModel::opciones($tipo);

    /* Regla: gestión cerrada = solo lectura */
    if (!$gFiltro || !$gestionEsAbierta) {
        redirect("$back&msg=gestion_cerrada");
    }

    /* Regla: el parcial debe existir en el régimen del curso */
    if (!in_array($parcial, $opciones, true)) {
        redirect("$back&msg=parcial_invalido");
    }

    if ($accion === 'cerrar') {
        $parcialModel->cerrarGestion($cursoId, $gestionId, $parcial);
        redirect("$back&msg=cerrado");
    }

    if ($accion === 'abrir') {
        /* Regla de secuencialidad: el parcial anterior debe estar ENVIADO o CERRADO */
        $idx = array_search($parcial, $opciones, true);
        if ($idx > 0) {
            $resPrev  = $parcialModel->resumenCursoGestion($cursoId, $gestionId, $tipo);
            $stPrev   = estadoParcial($resPrev[$opciones[$idx - 1]] ?? null);
            if ($stPrev === 'ABIERTO' || $stPrev === 'SIN_ABRIR') {
                redirect("$back&msg=secuencia");
            }
        }
        $parcialModel->abrirGestion($cursoId, $gestionId, $parcial, $adminId);
        redirect("$back&msg=abierto");
    }
}

/* ───────── Datos de vista ─────────
   Cursos = catálogo fijo (getAll); la gestión la aporta el filtro. */
$cursos = $cursosModel->getAll();

$resumenes = [];
foreach ($cursos as $c) {
    $resumenes[(int)$c['id']] = $gestionId
        ? $parcialModel->resumenCursoGestion(
            (int)$c['id'],
            $gestionId,
            $c['carrera_tipo'] ?? 'anual'
        )
        : [];
}

/* Agrupamos por régimen para que las columnas de parciales queden alineadas */
$grupos = ['semestral' => [], 'anual' => []];
foreach ($cursos as $c) {
    $t = ($c['carrera_tipo'] ?? 'anual') === 'semestral' ? 'semestral' : 'anual';
    $grupos[$t][] = $c;
}

$titulo = 'Gestión de Períodos';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
<style>
    .btn:disabled { opacity: .45; cursor: not-allowed; }
    tr.row-highlight td { background: rgba(79, 140, 255, .10); }
    .badge-estado { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; color: #fff; margin-bottom: 4px; }
    .leyenda-periodos { display: flex; gap: 14px; flex-wrap: wrap; margin: 10px 2px 0; font-size: 12px; color: var(--text-2); }
    .leyenda-periodos span { display: inline-flex; align-items: center; gap: 6px; }
</style>

<div class="page-head" style="margin-bottom:18px;">
    <div>
        <h1>Gestión de Períodos</h1>
        <p class="subtitle">Apertura y cierre de periodos de evaluación por curso · solo la gestión abierta permite movimientos.</p>
    </div>
</div>

<?php
/* Avisos por resultado de acción (vía GET, independientes del flash de sesión) */
$mensajes = [
    'abierto'          => ['success', 'Período abierto: los docentes del curso ya pueden registrar sus notas.'],
    'cerrado'          => ['success', 'Período cerrado.'],
    'gestion_cerrada'  => ['error',   'La gestión de ese curso está CERRADA: los períodos son de solo lectura (solo reimpresión de planillas).'],
    'secuencia'        => ['error',   'No se puede abrir: el período anterior aún está abierto o nunca se abrió. Los períodos se abren en orden.'],
    'parcial_invalido' => ['error',   'El período indicado no corresponde al régimen de la carrera.'],
    'curso_invalido'   => ['error',   'El curso indicado no existe.'],
];
if ($msgGet !== '' && isset($mensajes[$msgGet])):
    [$cls, $txt] = $mensajes[$msgGet];
?>
    <div class="alert alert-<?php echo $cls === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($txt); ?></div>
<?php endif; ?>

<p class="ayuda" style="margin-bottom:15px; color:var(--text-2); font-size:13px;">
    Abrí un período para que los docentes del curso registren notas. Los períodos se abren <strong>en orden</strong>:
    el siguiente se habilita cuando el anterior queda <strong>ENVIADO</strong> (todas las materias enviaron) o <strong>CERRADO</strong>.
    Un período <strong>ENVIADO</strong> puede reabrirse solo con confirmación explícita.
</p>

<div class="form-container" style="max-width:420px; margin-bottom:20px;">
    <form method="GET" style="display:flex; gap:10px; align-items:flex-end;">
        <div class="form-group" style="flex:1; margin-bottom:0;">
            <label>Gestión</label>
            <select name="gestion" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php foreach ($gestiones as $g): ?>
                    <option value="<?php echo (int)$g['anio']; ?>" <?php echo $gestionFiltro === (int)$g['anio'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g['anio']); ?><?php echo ($g['estado'] ?? '') === 'abierta' ? ' (abierta)' : ' (cerrada)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <?php if ($gestionFiltro): ?>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/view/admin/gestion_periodos.php">Ver todas</a>
        <?php endif; ?>
    </form>
</div>

<?php
$labelsGrupo = ['semestral' => 'Carreras semestrales', 'anual' => 'Carreras anualizadas (por parciales)'];
foreach ($grupos as $tipoGrupo => $lista):
    if (empty($lista)) continue;
    $ops   = ParcialPeriodoModel::opciones($tipoGrupo);
    $nCols = 5 + count($ops);
?>
    <h2 style="font-size:15px; font-weight:700; margin:26px 0 10px;"><?php echo $labelsGrupo[$tipoGrupo]; ?></h2>
    <div class="tabla-contenedor">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Curso</th>
                    <th rowspan="2">Carrera</th>
                    <th rowspan="2">Turno</th>
                    <th rowspan="2">Gestión</th>
                    <th colspan="<?php echo count($ops); ?>">Períodos de evaluación</th>
                </tr>
                <tr>
                    <?php foreach ($ops as $op): ?>
                        <th style="text-align:center;"><?php echo htmlspecialchars($op); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $c):
                    $cId   = (int)$c['id'];
                    $anioC = $gestionFiltro;
                    $res   = $resumenes[$cId] ?? [];

                    /* ¿La gestión del filtro está abierta? (cursos son catálogo fijo) */
                    $gestionAbiertaCurso = $gestionEsAbierta;

                    /* Secuencialidad: índice del primer período no consolidado */
                    $estados = [];
                    foreach ($ops as $op) $estados[$op] = estadoParcial($res[$op] ?? null);
                ?>
                    <tr id="curso-<?php echo $cId; ?>" <?php echo $cursoSelId === $cId ? 'class="row-highlight"' : ''; ?>>
                        <td>
                            <strong><?php echo htmlspecialchars($c['carrera_nombre']); ?> · <?php echo (int)$c['anio_carrera']; ?>º</strong>
                            - <?php echo htmlspecialchars($c['paralelo']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($c['carrera_nombre']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($c['turno'] ?? '')); ?></td>
                        <td>
                            <?php echo $anioC; ?>
                            <?php if (!$gestionAbiertaCurso): ?>
                                <span class="badge badge-cerrado" style="margin-left:6px;">cerrada</span>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($ops as $op):
                            $st = $estados[$op];
                            $dp = datosParcial($res[$op] ?? null);

                            /* Badge de estado */
                            if ($st === 'ABIERTO')        { $badge = 'badge-abierto';  $texto = 'ABIERTO'; }
                            elseif ($st === 'ENVIADO')    { $badge = 'badge-enviado';  $texto = 'ENVIADO'; }
                            elseif ($st === 'CERRADO')    { $badge = 'badge-cerrado';  $texto = 'CERRADO'; }
                            else                          { $badge = 'badge-cerrado';  $texto = 'SIN ABRIR'; }

                            /* ¿Se puede abrir? (secuencialidad) */
                            $idxOp   = array_search($op, $ops, true);
                            $puedeAbrir = true;
                            $motivoBloq = '';
                            if ($idxOp > 0) {
                                $stPrev = $estados[$ops[$idxOp - 1]];
                                if ($stPrev === 'ABIERTO' || $stPrev === 'SIN_ABRIR') {
                                    $puedeAbrir = false;
                                    $motivoBloq = 'Espera a que el período anterior quede ENVIADO o CERRADO';
                                }
                            }

                            /* Confirmaciones con contexto */
                            if ($st === 'ABIERTO') {
                                $confirm = $dp['pend'] > 0
                                    ? "Cerrar {$op}: quedan {$dp['pend']} materia(s) sin enviar notas. ¿Continuar?"
                                    : "Cerrar {$op}?";
                            } elseif ($st === 'ENVIADO') {
                                $confirm = "{$op} está CONSOLIDADO (todas las materias enviaron notas). ¿Reabrirlo?";
                            } else {
                                $confirm = $dp['pend'] > 0
                                    ? "{$op} fue cerrado con {$dp['pend']} materia(s) sin enviar. ¿Reabrirlo?"
                                    : "Abrir {$op}?";
                            }
                        ?>
                            <td style="text-align:center; vertical-align:middle;">
                                <div><span class="badge-estado <?php echo $badge; ?>"><?php echo $texto; ?></span></div>
                                <?php if (!$gestionAbiertaCurso): ?>
                                    <span style="font-size:11px; color:var(--text-3);">solo lectura</span>
                                <?php elseif ($st === 'ABIERTO'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars($confirm, ENT_QUOTES); ?>');">
                                        <?php echo csrf_campo(); ?>
                                        <input type="hidden" name="accion" value="cerrar">
                                        <input type="hidden" name="curso_id" value="<?php echo $cId; ?>">
                                        <input type="hidden" name="parcial" value="<?php echo htmlspecialchars($op, ENT_QUOTES); ?>">
                                        <button type="submit" class="btn btn-danger" style="padding:2px 8px; font-size:11px;">Cerrar</button>
                                    </form>
                                <?php elseif ($st === 'SIN_ABRIR'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars($confirm, ENT_QUOTES); ?>');">
                                        <?php echo csrf_campo(); ?>
                                        <input type="hidden" name="accion" value="abrir">
                                        <input type="hidden" name="curso_id" value="<?php echo $cId; ?>">
                                        <input type="hidden" name="parcial" value="<?php echo htmlspecialchars($op, ENT_QUOTES); ?>">
                                        <button type="submit" class="btn btn-primary" style="padding:2px 8px; font-size:11px;"
                                            <?php echo $puedeAbrir ? '' : 'disabled title="' . htmlspecialchars($motivoBloq, ENT_QUOTES) . '"'; ?>>
                                            Abrir
                                        </button>
                                    </form>
                                <?php else: /* ENVIADO o CERRADO → reabrir con confirmación */ ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo htmlspecialchars($confirm, ENT_QUOTES); ?>');">
                                        <?php echo csrf_campo(); ?>
                                        <input type="hidden" name="accion" value="abrir">
                                        <input type="hidden" name="curso_id" value="<?php echo $cId; ?>">
                                        <input type="hidden" name="parcial" value="<?php echo htmlspecialchars($op, ENT_QUOTES); ?>">
                                        <button type="submit" class="btn btn-ghost" style="padding:2px 8px; font-size:11px;">Reabrir</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<?php if (empty($cursos)): ?>
    <div class="tabla-contenedor">
        <table><tbody><tr><td style="text-align:center; padding:26px; color:var(--text-3);">
            No hay cursos registrados para este filtro.
        </td></tr></tbody></table>
    </div>
<?php endif; ?>

<div class="leyenda-periodos">
    <span><span class="badge-estado badge-abierto">ABIERTO</span> docentes cargando notas</span>
    <span><span class="badge-estado badge-enviado">ENVIADO</span> todas las materias enviaron (consolidado)</span>
    <span><span class="badge-estado badge-cerrado">CERRADO</span> cerrado con materias sin enviar</span>
    <span><span class="badge-estado badge-cerrado">SIN ABRIR</span> aún no habilitado</span>
</div>

<?php if ($cursoSelId): ?>
    <script>
        document.getElementById('curso-<?php echo $cursoSelId; ?>')
            ?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>