<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/NotasModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';
require_once __DIR__ . '/../../model/GestionesModel.php';

$cursosModel = new CursosModel();
$notasModel = new NotasModel();
$gestionesModel = new GestionesModel();
$gestionAbierta = $gestionesModel->getAbierta();
$gestionId = $gestionAbierta ? (int)$gestionAbierta['id'] : 0;

$carreraId = isset($_GET['carrera_id']) && $_GET['carrera_id'] !== '' ? (int)$_GET['carrera_id'] : null;
$cursoId = isset($_GET['curso_id']) && $_GET['curso_id'] !== '' ? (int)$_GET['curso_id'] : 0;
$materiaId = isset($_GET['materia_id']) && $_GET['materia_id'] !== '' ? (int)$_GET['materia_id'] : 0;

$opciones = $cursosModel->getFilterOptions();
$cursos = $cursosModel->getByFilters($carreraId, null, null, null);

$curso = $cursoId ? $cursosModel->getById($cursoId) : null;
$centralMaterias = [];
$entregaMateria = null;
$entregaFilas = [];
$ciclo = ['1er Parcial','2do Parcial','3er Parcial','4to Parcial'];
$docentePorMateria = [];
if ($curso && $gestionId) {
    $tipo = $curso['carrera_tipo'] ?? 'anual';
    $ciclo = ParcialPeriodoModel::ciclo($tipo);
    $imp = $cursosModel->getMateriasImpartidas($cursoId, $gestionId);
    $centralMaterias = array_map(fn($r)=>['id'=>(int)$r['id'],'nombre'=>$r['nombre'],'codigo'=>$r['codigo'],'docente'=>$r['docente_nombre'] ?? null], $imp);
    foreach ($centralMaterias as $cm) $docentePorMateria[$cm['id']] = $cm['docente'];
    if (!$materiaId && !empty($centralMaterias)) $materiaId = (int)$centralMaterias[0]['id'];
    foreach ($centralMaterias as $m) if ((int)$m['id']===$materiaId) { $entregaMateria=$m; break; }
    if ($entregaMateria) {
        $filas = $notasModel->getParcialesPorCurso($cursoId, $materiaId);
        $map = [];
        foreach ($filas as $f) {
            $eid=(int)$f['estudiante_id'];
            if (!isset($map[$eid])) $map[$eid]=['nombre'=>$f['nombre_completo'],'matricula'=>$f['matricula']??'','notas'=>[],'parcialUnico'=>null];
            if ($f['nota']===null) continue;
            if ($f['nombre_actividad']==='Parcial') $map[$eid]['parcialUnico']=(float)$f['nota'];
            elseif (in_array($f['nombre_actividad'], $ciclo, true)) $map[$eid]['notas'][$f['nombre_actividad']]=(float)$f['nota'];
        }
        foreach ($map as $eid=>&$d) {
            $prom = !empty($d['notas']) ? round(array_sum($d['notas'])/count($d['notas']),1) : $d['parcialUnico'];
            $notaFin = $prom!==null? round($prom): null;
            $d['promedio']=$prom; $d['notaFin']=$notaFin; $d['obs']=$notaFin!==null?($notaFin>=61?'APROBADO':'REPROBADO'):'';
        }
        unset($d);
        $entregaFilas=$map;
    }
}

$titulo = 'Entrega de calificaciones';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <div>
                <h1>Entrega de calificaciones</h1>
                <p class="subtitle">Gestión <?php echo $gestionAbierta ? htmlspecialchars($gestionAbierta['anio']) : '—'; ?> · Elegí curso + materia y el acta se dibuja directo aquí.</p>
            </div>
        </div>
        <div class="form-container" style="max-width:1000px;">
            <form method="GET" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
                <div class="form-group"><label>Carrera</label><select name="carrera_id"><option value="">Todas</option><?php foreach ($opciones['carreras'] as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $carreraId===(int)$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Curso</label><select name="curso_id"><option value="">— Elegí —</option><?php foreach ($cursos as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $cursoId===(int)$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['carrera_nombre']); ?> · <?php echo (int)$c['anio_carrera']; ?>º · <?php echo htmlspecialchars($c['turno']); ?> · <?php echo htmlspecialchars($c['paralelo']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Materia</label><select name="materia_id"><option value="">— Elegí curso primero —</option><?php foreach ($centralMaterias as $cm): ?><option value="<?php echo (int)$cm['id']; ?>" <?php echo $materiaId===(int)$cm['id']?'selected':''; ?>><?php echo htmlspecialchars($cm['nombre']); ?></option><?php endforeach; ?></select></div>
                <div style="display:flex;gap:8px;"><button type="submit" class="btn btn-primary">Ver</button><a class="btn btn-ghost" href="<?= BASE_URL ?>/view/admin/planilla_entrega.php">Limpiar</a></div>
            </form>
        </div>
        <?php if ($curso && $entregaMateria): ?>
        <style>
        .sheet{background:#fff;color:#111;padding:18px;border-radius:8px;margin-bottom:16px;overflow-x:auto;}
        .sheet h2{font-size:15px;text-align:center;margin-bottom:8px;}
        .sheet .meta{font-size:12px;margin-bottom:10px;}
        .sheet table{width:100%;border-collapse:collapse;font-size:12px;}
        .sheet th,.sheet td{border:1px solid #333;padding:4px 6px;text-align:center;}
        .sheet th{background:#eee;}
        @media print{ body *{visibility:hidden;} .print-area, .print-area *{visibility:visible;} .print-area{position:absolute;left:0;top:0;width:100%;} .no-print{display:none !important;} }
        </style>
        <div class="no-print" style="display:flex;gap:8px;margin:12px 0;"><button class="btn btn-primary" onclick="window.print()">Imprimir entrega</button></div>
        <div class="print-area">
        <div class="sheet">
            <h2>ENTREGA DE CALIFICACIONES</h2>
            <div class="meta"><strong>CARRERA:</strong> <?php echo htmlspecialchars($curso['carrera_nombre']); ?> &nbsp; | &nbsp; <strong>MATERIA:</strong> <?php echo htmlspecialchars($entregaMateria['nombre'] ?? '—'); ?> &nbsp; | &nbsp; <strong>TURNO:</strong> <?php echo htmlspecialchars($curso['turno']??'mañana'); ?> &nbsp; | &nbsp; <strong>NOTA APROBACIÓN:</strong> 61 &nbsp; | &nbsp; <strong>GESTIÓN:</strong> <?php echo $gestionAbierta ? htmlspecialchars($gestionAbierta['anio']) : ''; ?> &nbsp; | &nbsp; <strong>DOCENTE:</strong> <?php echo htmlspecialchars($docentePorMateria[$materiaId] ?? '—'); ?></div>
            <table>
                <thead><tr><th>N°</th><th>APELLIDOS Y NOMBRES</th><?php foreach ($ciclo as $pc): ?><th><?php echo htmlspecialchars(strtoupper($pc)); ?></th><?php endforeach; ?><th>PROMEDIO</th><th>INSTANCIA</th><th>NOTA FINAL</th><th>OBSERVACIÓN</th></tr></thead>
                <tbody>
                    <?php $n=1; foreach ($entregaFilas as $ef): ?>
                    <tr><td><?php echo $n++; ?></td><td style="text-align:left;"><?php echo htmlspecialchars($ef['nombre']); ?></td>
                    <?php foreach ($ciclo as $pc): ?><td><?php echo isset($ef['notas'][$pc])? number_format($ef['notas'][$pc],1):''; ?></td><?php endforeach; ?>
                    <td><?php echo $ef['promedio']!==null? number_format($ef['promedio'],1):''; ?></td><td>REGULAR</td><td><?php echo $ef['notaFin']!==null? (int)$ef['notaFin']:''; ?></td><td><?php echo htmlspecialchars($ef['obs']); ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($entregaFilas)): ?><tr><td colspan="<?php echo 5+count($ciclo); ?>">Sin datos para esta materia</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
        <?php endif; ?>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
