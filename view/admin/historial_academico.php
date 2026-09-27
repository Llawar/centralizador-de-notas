<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_guard('admin');
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/NotasModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';

$estudiantesModel = new EstudiantesModel();
$notasModel = new NotasModel();
$estudiantes = $estudiantesModel->getAll();

function promedioArray(array $vals) {
    $sum = 0; $n = 0;
    foreach ($vals as $v) { if ($v !== null) { $sum += $v; $n++; } }
    return $n > 0 ? round($sum / $n, 1) : null;
}
function fechaBoletin($ts) {
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}
function semestrePalabra($n) {
    $map = [1 => 'PRIMERO', 2 => 'SEGUNDO', 3 => 'TERCERO', 4 => 'CUARTO', 5 => 'QUINTO', 6 => 'SEXTO'];
    return $map[(int) $n] ?? 'PRIMERO';
}
function fechaAdmision($anio) { return '01/02/' . (int) $anio; }
function fechaConclusion($anio) { return '01/02/' . ((int)$anio + 3); }

$kardex = null;
$q = trim($_GET['q'] ?? '');
$estId = isset($_GET['estudiante_id']) && $_GET['estudiante_id']!=='' ? (int) $_GET['estudiante_id'] : 0;
// búsqueda por CI/matricula vía q
if ($q !== '' && !$estId) {
    foreach ($estudiantes as $e) {
        if (stripos($e['ci']??'',$q)!==false || stripos($e['matricula']??'',$q)!==false || stripos($e['nombre_completo']??'',$q)!==false) {
            $estId = (int) $e['id']; break;
        }
    }
}
if ($estId) {
    $est = $estudiantesModel->getById($estId);
    $notas = $est ? $notasModel->getResumenNotas($estId) : [];
    $kardex = ['estudiante'=>$est,'filas'=>[]];
    if ($est) {
        $grupos=[];
        foreach ($notas as $n) {
            if (($n['tipo']??'')!=='parcial') continue;
            $act=$n['nombre_actividad'];
            if (!in_array($act, ['1er Parcial','2do Parcial','3er Parcial','4to Parcial','Parcial'], true)) continue;
            $clave=(int)$n['materia_id'].'|'.(int)$n['gestion'].'|'.(int)$n['curso_id'];
            if (!isset($grupos[$clave])) $grupos[$clave]=['gestion'=>(int)$n['gestion'],'semestre'=>(int)($n['semestre']??0),'codigo'=>$n['codigo'],'materia'=>$n['materia'],'curso'=>$n['curso'],'notas'=>[],'nota'=>null];
            if ($act==='Parcial') $grupos[$clave]['nota']=(float)$n['nota']; else $grupos[$clave]['notas'][$act]=(float)$n['nota'];
        }
        foreach ($grupos as $g) {
            if ($g['nota']===null && !empty($g['notas'])) $g['nota']=promedioArray($g['notas']);
            $kardex['filas'][]=$g;
        }
        usort($kardex['filas'], function($a,$b){ if($a['gestion']!==$b['gestion']) return $a['gestion']-$b['gestion']; return strcmp($a['materia'],$b['materia']); });
        $acum=0;$n=0;$aprobadas=0;
        foreach ($kardex['filas'] as $f){ if($f['nota']!==null){ $acum+=$f['nota']; $n++; if($f['nota']>=61) $aprobadas++; } }
        $kardex['promedio']=$n>0? round($acum/$n,1):0;
        $kardex['aprobadas']=$aprobadas;
        $kardex['carrera']=$est && $notas ? ($notas[0]['carrera_nombre']??'') : '';
    }
}
$titulo='Historial Académico';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Historial Académico</h1>
        <p class="subtitle">Búsqueda por CI, matrícula o nombre del estudiante</p>

        <form method="GET" class="form-container" style="max-width:1100px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;">
            <div class="form-group" style="flex:1;min-width:220px;margin-bottom:0;">
                <label>Buscar (CI / matrícula / nombre)</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ej: 13531459 o matrícula">
            </div>
            <div class="form-group" style="min-width:260px;margin-bottom:0;">
                <label>o seleccionar estudiante</label>
                <select name="estudiante_id">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($estudiantes as $e): ?><option value="<?php echo (int)$e['id']; ?>" <?php echo $estId===(int)$e['id']?'selected':''; ?>><?php echo htmlspecialchars($e['nombre_completo']); ?> (<?php echo htmlspecialchars($e['ci']); ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Consultar</button>
            <?php if ($kardex): ?><button type="button" class="btn btn-ghost" onclick="window.print()">Imprimir</button><?php endif; ?>
        </form>

        <?php if ($kardex && $kardex['estudiante']): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_historial.css">
            <div class="sheet">
                <div class="sheet-head">
                    <img src="<?= BASE_URL ?>/view/img/escudo.jpg" alt="Escudo" class="sheet-logo">
                    <div class="sheet-school"><div class="inst-1">INSTITUTO TECNOLÓGICO</div><div class="inst-2">"PACCIOLI"</div></div>
                </div>
                <div class="sheet-title">HISTORIAL ACADÉMICO</div>
                <table class="kardex-enc">
                    <tr><td class="enc-lab">INSTITUTO:</td><td class="enc-val">INSTITUTO TECNOLÓGICO PACCIOLI</td><td colspan="2" class="enc-vacio"></td></tr>
                    <tr><td class="enc-lab">MATRICULA:</td><td class="enc-val"><?php echo htmlspecialchars($kardex['estudiante']['matricula']??''); ?></td><td colspan="2" class="enc-vacio"></td></tr>
                    <tr><td class="enc-lab">ESTUDIANTE:</td><td class="enc-val"><?php echo htmlspecialchars($kardex['estudiante']['nombre_completo']??''); ?></td><td colspan="2" class="enc-vacio"></td></tr>
                    <tr><td class="enc-lab">CARRERA:</td><td class="enc-val"><?php echo htmlspecialchars($kardex['carrera']); ?></td><td class="enc-lab">CEDULA DE IDENTIDAD:</td><td class="enc-val"><?php echo htmlspecialchars($kardex['estudiante']['ci']??''); ?></td></tr>
                    <tr><td class="enc-lab">NIVEL DE FORMACIÓN:</td><td class="enc-val">TÉCNICO SUPERIOR</td><td class="enc-lab">FECHA DE ADMISIÓN:</td><td class="enc-val"><?php echo fechaAdmision($kardex['estudiante']['anio_ingreso']??0); ?></td></tr>
                    <tr><td class="enc-lab">RÉGIMEN:</td><td class="enc-val">ANUALIZADO</td><td class="enc-lab">FECHA DE CONCLUSIÓN:</td><td class="enc-val"><?php echo fechaConclusion($kardex['estudiante']['anio_ingreso']??0); ?></td></tr>
                </table>
                <table class="tabla-kardex">
                    <thead><tr><th>N°</th><th>GESTIÓN ACADÉMICA</th><th>SEMESTRE/AÑO</th><th>CÓDIGO</th><th>ASIGNATURA</th><th>PRE REQUISITO</th><th>NOTA</th><th>PRUEBA RECUP.</th><th>OBSERVACIONES</th></tr></thead>
                    <tbody>
                        <?php if (empty($kardex['filas'])): ?><tr><td colspan="9" class="sin-datos">El estudiante no tiene notas registradas.</td></tr>
                        <?php else: $n=1; foreach ($kardex['filas'] as $f): $nota=$f['nota']; $notaFin=$nota!==null? round($nota):null; $obs=$notaFin!==null?($notaFin>=61?'APROBADO':'REPROBADO'):''; ?>
                        <tr><td><?php echo $n++; ?></td><td><?php echo (int)$f['gestion']; ?></td><td><?php echo $f['semestre']>0? semestrePalabra($f['semestre']):''; ?></td><td><?php echo htmlspecialchars($f['codigo']); ?></td><td class="td-nombre"><?php echo htmlspecialchars($f['materia']); ?></td><td>-</td><td><?php echo $nota!==null? number_format($nota,1):''; ?></td><td></td><td><?php echo $obs; ?></td></tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <div class="kardex-lugar"><span class="k-lbl">Lugar y fecha:</span><span class="k-val">Punata, <?php echo fechaBoletin(time()); ?></span></div>
                <div class="firma-autoridad"><div class="fa-linea"></div><div class="fa-label">Firma de autoridad Académica</div></div>
                <table class="tabla-escala">
                    <tr><td></td><td colspan="3" class="es-tit">ESCALA DE VALORACIÓN</td><td></td><td></td><td colspan="3" class="es-der">Carga horaria:&nbsp;&nbsp;3600hrs.</td></tr>
                    <tr><td></td><td>61 a 100</td><td colspan="2">APROBADO</td><td></td><td></td><td colspan="3" class="es-der">Asignaturas aprobadas:&nbsp;&nbsp;<?php echo (int)$kardex['aprobadas']; ?>/<?php echo count($kardex['filas']); ?></td></tr>
                    <tr><td></td><td>0 a 60</td><td colspan="2">REPROBADO</td><td colspan="2">Sello del Instituto</td><td colspan="3" class="es-der">Promedio de Calificaciones:&nbsp;&nbsp;<?php echo number_format($kardex['promedio'],1); ?></td></tr>
                    <tr><td></td><td>61</td><td colspan="2">NOTA MÍNIMA</td><td></td><td></td><td colspan="3"></td></tr>
                    <tr><td colspan="9" class="es-nota">Cualquier raspadura o enmienda invalida el presente documento.</td></tr>
                </table>
            </div>
        <?php elseif ($estId): ?>
            <div class="alert alert-error">Estudiante no encontrado.</div>
        <?php endif; ?>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
