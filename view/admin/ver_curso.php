<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';
require_once __DIR__ . '/../../model/NotasModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';

$cursosModel = new CursosModel();
$materiasModel = new MateriasModel();
$docentesModel = new DocentesModel();
$estudiantesModel = new EstudiantesModel();
$notasModel = new NotasModel();
$parcialModel = new ParcialPeriodoModel();

$id = (int) ($_GET['id'] ?? 0);
$curso = $cursosModel->getById($id);
if (!$curso) { redirect('/view/admin/gestion_cursos.php'); }
$gestion = (int) ($curso['gestion'] ?? date('Y'));
$tipo = $curso['carrera_tipo'] ?? 'anual';
$ciclo = ParcialPeriodoModel::ciclo($tipo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'inscribir') {
        $estudiantesModel->inscribirCurso((int) $_POST['estudiante_id'], $id, (int) ($_POST['semestre'] ?? 1));
        redirect("/view/admin/ver_curso.php?id=$id&tab=estudiantes&msg=created");
    } elseif ($accion === 'asignar') {
        $cursosModel->asignarDocenteMateria((int) $_POST['docente_id'], (int) $_POST['materia_id'], $id);
        $parcialModel->asegurarPeriodosParaCursoMateria($id, (int) $_POST['materia_id'], $gestion);
        redirect("/view/admin/ver_curso.php?id=$id&tab=materias&msg=created");
    } elseif ($accion === 'abrir_parcial') {
        $parcialModel->abrir($id, $gestion, $_POST['parcial'], (int) $_SESSION['user_id']);
        redirect("/view/admin/ver_curso.php?id=$id&tab=parciales&msg=updated");
    } elseif ($accion === 'cerrar_parcial') {
        $parcialModel->cerrar($id, $gestion, $_POST['parcial']);
        redirect("/view/admin/ver_curso.php?id=$id&tab=parciales&msg=updated");
    }
}

$tab = $_GET['tab'] ?? 'info';
$estudiantes = $cursosModel->getEstudiantes($id);
$materiasImpartidas = $cursosModel->getMateriasImpartidas($id);
$docentes = $docentesModel->getAll();
$todosEstudiantes = $estudiantesModel->getAll();
$resumenParcial = $parcialModel->resumenCurso($id, $gestion, $tipo);

// Para planillas: centralizador
$centralMaterias = $cursosModel->getMateriasPorCurso($id); // solo asignadas con docente (para cabecera)
if (empty($centralMaterias)) {
    // fallback a impartidas para mostrar aunque sin docente
    $centralMaterias = array_map(fn($r)=>['id'=>$r['id'],'nombre'=>$r['nombre'],'codigo'=>$r['codigo']], $materiasImpartidas);
}
// Mapa notas finales por estudiante x materia
$centralNotas = []; // [estudiante_id][materia_id] => nota final
$asignaciones = $cursosModel->getDocenteMaterias($id);
$docentePorMateria = [];
foreach ($asignaciones as $a) { $docentePorMateria[(int)$a['materia_id']] = $a['docente_nombre']; }
foreach ($estudiantes as $est) {
    foreach ($centralMaterias as $mat) {
        $all = $notasModel->getNotasEstudiante((int)$est['id'], $id, (int)$mat['id']);
        $cicloVals = [];
        $parcialUnico = null;
        foreach ($all as $n) {
            if (($n['tipo']??'')!=='parcial') continue;
            if ($n['nombre_actividad']==='Parcial') $parcialUnico=(float)$n['nota'];
            elseif (in_array($n['nombre_actividad'], $ciclo, true)) $cicloVals[]=(float)$n['nota'];
        }
        $final = null;
        if (!empty($cicloVals)) $final = round(array_sum($cicloVals)/count($cicloVals), 1);
        elseif ($parcialUnico!==null) $final=$parcialUnico;
        $centralNotas[(int)$est['id']][(int)$mat['id']]=$final;
    }
}
// estadísticas
$totalInscritos = count($estudiantes);
$aprobados = 0; $reprobados = 0;
foreach ($estudiantes as $est) {
    $prom = null; $count=0; $sum=0;
    foreach ($centralMaterias as $mat) {
        $v=$centralNotas[(int)$est['id']][(int)$mat['id']] ?? null;
        if ($v!==null){ $sum+=$v; $count++; }
    }
    if ($count>0){ $prom=$sum/$count; if($prom>=61) $aprobados++; else $reprobados++; }
}
$abandono = 0; // placeholder sin estado explícito

// Entrega: selector materia
$entregaMateriaId = isset($_GET['materia_id']) ? (int) $_GET['materia_id'] : (int) ($centralMaterias[0]['id'] ?? 0);
$entregaMateria = null;
foreach ($centralMaterias as $m) if ((int)$m['id']===$entregaMateriaId) { $entregaMateria=$m; break; }
$entregaFilas = [];
if ($entregaMateria) {
    $filas = $notasModel->getParcialesPorCurso($id, $entregaMateriaId);
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

$titulo = 'Detalle Curso — ' . $curso['nombre'];
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <a href="<?= BASE_URL ?>/view/admin/gestion_cursos.php" class="btn btn-ghost" style="margin-bottom:12px;">← Volver a Cursos</a>
        <div class="page-head" style="margin-bottom:10px;">
            <div>
                <h1><?php echo htmlspecialchars($curso['nombre']); ?> · <?php echo htmlspecialchars($curso['carrera_nombre']); ?></h1>
                <p class="subtitle"><?php echo (int)$curso['anio_carrera']; ?>° Año · Turno <?php echo htmlspecialchars($curso['turno']); ?> · Paralelo <?php echo htmlspecialchars($curso['paralelo']); ?> · Gestión <?php echo (int)$curso['gestion']; ?> · <?php echo htmlspecialchars($tipo); ?></p>
            </div>
        </div>
        <?= flash_html(['created'=>'Operación exitosa.','updated'=>'Actualizado.','deleted'=>'Eliminado.']) ?>

        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <?php foreach (['info'=>'Info','materias'=>'Materias impartidas','estudiantes'=>'Estudiantes','parciales'=>'Parciales','planillas'=>'Planillas'] as $k=>$lbl): ?>
                <a class="btn <?php echo $tab===$k?'btn-primary':'btn-ghost'; ?>" href="?id=<?php echo (int)$id; ?>&tab=<?php echo $k; ?>"><?php echo $lbl; ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($tab==='info'): ?>
            <div class="form-container" style="max-width:700px;">
                <p><strong>Carrera:</strong> <?php echo htmlspecialchars($curso['carrera_nombre']); ?></p>
                <p><strong>Nombre curso:</strong> <?php echo htmlspecialchars($curso['nombre']); ?></p>
                <p><strong>Año de carrera:</strong> <?php echo (int)$curso['anio_carrera']; ?>°</p>
                <p><strong>Turno:</strong> <?php echo htmlspecialchars($curso['turno']??'mañana'); ?> (<?php echo ($curso['turno']??'mañana')==='mañana'?'08:00-12:00':'13:00-17:00'; ?>)</p>
                <p><strong>Paralelo:</strong> <?php echo htmlspecialchars($curso['paralelo']); ?></p>
                <p><strong>Gestión:</strong> <?php echo (int)$curso['gestion']; ?> · <strong>Semestre:</strong> <?php echo (int)($curso['semestre']??1); ?></p>
                <p><strong>Tipo carrera:</strong> <?php echo htmlspecialchars($tipo); ?> (<?php echo implode(', ', $ciclo); ?>)</p>
            </div>

        <?php elseif ($tab==='materias'): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h3>Materias impartidas (plan <?php echo (int)$curso['anio_carrera']; ?>° año)</h3>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalAsignar').classList.add('active')">+ Asignar docente</button>
            </div>
            <div class="tabla-contenedor">
                <table><thead><tr><th>Código</th><th>Materia</th><th>Docente</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($materiasImpartidas as $mi): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mi['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($mi['nombre']); ?></td>
                        <td><?php echo $mi['docente_nombre'] ? htmlspecialchars($mi['docente_nombre']) : '<span style="color:var(--text-3);">Sin asignar</span>'; ?></td>
                        <td><?php echo $mi['docente_nombre'] ? '<span class="badge-abierto">Asignado</span>' : '<span class="badge-cerrado">Pendiente</span>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody></table>
            </div>
            <?php if (empty($materiasImpartidas)): ?><p style="color:var(--text-3);">No hay materias para este año de carrera. <a href="<?= BASE_URL ?>/view/admin/ver_carrera.php?id=<?php echo (int)$curso['carrera_id']; ?>&tab=materias" style="color:var(--accent-2);">Crear en Detalle Carrera</a></p><?php endif; ?>
            <div id="modalAsignar" class="modal-overlay">
                <div class="modal">
                    <h3>Asignar docente a materia</h3>
                    <form method="POST">
                        <?php echo csrf_campo(); ?>
                        <input type="hidden" name="accion" value="asignar">
                        <div class="form-group"><label>Docente</label><select name="docente_id" required><?php foreach ($docentes as $d): ?><option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['nombre_completo']); ?></option><?php endforeach; ?></select></div>
                        <div class="form-group"><label>Materia del plan (<?php echo (int)$curso['anio_carrera']; ?>°)</label><select name="materia_id" required><?php foreach ($materiasImpartidas as $mi): ?><option value="<?php echo (int)$mi['id']; ?>"><?php echo htmlspecialchars($mi['nombre']); ?> (<?php echo htmlspecialchars($mi['codigo']); ?>)</option><?php endforeach; ?></select></div>
                        <div class="form-actions"><button type="submit" class="btn btn-success">Asignar</button><button type="button" class="btn btn-danger" onclick="document.getElementById('modalAsignar').classList.remove('active')">Cancelar</button></div>
                    </form>
                </div>
            </div>

        <?php elseif ($tab==='estudiantes'): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h3>Estudiantes — <?php echo count($estudiantes); ?> inscritos</h3>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalInscribir').classList.add('active')">+ Inscribir estudiante</button>
            </div>
            <div class="tabla-contenedor">
                <table><thead><tr><th>#</th><th>Nombre</th><th>C.I.</th><th>Matrícula</th></tr></thead>
                <tbody>
                    <?php $n=1; foreach ($estudiantes as $e): ?><tr><td><?php echo $n++; ?></td><td><?php echo htmlspecialchars($e['nombre_completo']); ?></td><td><?php echo htmlspecialchars($e['ci']); ?></td><td><?php echo htmlspecialchars($e['matricula']); ?></td></tr><?php endforeach; ?>
                </tbody></table>
            </div>
            <div id="modalInscribir" class="modal-overlay">
                <div class="modal">
                    <h3>Inscribir estudiante en este curso</h3>
                    <form method="POST">
                        <?php echo csrf_campo(); ?>
                        <input type="hidden" name="accion" value="inscribir">
                        <div class="form-group"><label>Estudiante</label><select name="estudiante_id" required><?php foreach ($todosEstudiantes as $te): ?><option value="<?php echo (int)$te['id']; ?>"><?php echo htmlspecialchars($te['nombre_completo']); ?> (<?php echo htmlspecialchars($te['ci']); ?>)</option><?php endforeach; ?></select></div>
                        <?php if ($tipo==='semestral'): ?><div class="form-group"><label>Semestre</label><input type="number" name="semestre" value="1" min="1" max="2"></div><?php endif; ?>
                        <div class="form-actions"><button type="submit" class="btn btn-success">Inscribir</button><button type="button" class="btn btn-danger" onclick="document.getElementById('modalInscribir').classList.remove('active')">Cancelar</button></div>
                    </form>
                </div>
            </div>
            <p style="margin-top:8px;color:var(--text-3);font-size:12px;">Inscripción crea fila en <code>estudiantes_cursos</code> para este curso (paralelo <?php echo htmlspecialchars($curso['paralelo']); ?> ya definido).</p>

        <?php elseif ($tab==='parciales'): ?>
            <h3>Estados de parciales — Gestión <?php echo (int)$gestion; ?></h3>
            <div class="tabla-contenedor">
                <table><thead><tr><th>Parcial</th><th>Total filas</th><th>Abiertos</th><th>Enviados</th><th>Cerrados</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach ($ciclo as $p): $r=$resumenParcial[$p]??['total'=>0,'abiertos'=>0,'enviados'=>0,'cerrados'=>0]; ?>
                    <tr><td><?php echo htmlspecialchars($p); ?></td><td><?php echo (int)$r['total']; ?></td><td><?php echo (int)$r['abiertos']; ?></td><td><?php echo (int)$r['enviados']; ?></td><td><?php echo (int)$r['cerrados']; ?></td><td>
                        <form method="POST" style="display:inline;"><?php echo csrf_campo(); ?><input type="hidden" name="accion" value="abrir_parcial"><input type="hidden" name="parcial" value="<?php echo htmlspecialchars($p,ENT_QUOTES); ?>"><button class="btn btn-success" style="padding:5px 10px;font-size:12px;">Abrir</button></form>
                        <form method="POST" style="display:inline;"><?php echo csrf_campo(); ?><input type="hidden" name="accion" value="cerrar_parcial"><input type="hidden" name="parcial" value="<?php echo htmlspecialchars($p,ENT_QUOTES); ?>"><button class="btn btn-danger" style="padding:5px 10px;font-size:12px;">Cerrar</button></form>
                    </td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            </div>

        <?php elseif ($tab==='planillas'): ?>
            <style>
            .sheet{background:#fff;color:#111;padding:18px;border-radius:8px;margin-bottom:16px;overflow-x:auto;}
            .sheet h2{font-size:15px;text-align:center;margin-bottom:8px;}
            .sheet .meta{font-size:12px;margin-bottom:10px;}
            .sheet table{width:100%;border-collapse:collapse;font-size:12px;}
            .sheet th,.sheet td{border:1px solid #333;padding:4px 6px;text-align:center;}
            .sheet th{background:#eee;}
            .firmas-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:12px;font-size:11px;}
            .firma-bloque{border:1px solid #333;padding:10px;text-align:center;min-height:60px;}
            @media print{ body *{visibility:hidden;} .print-area, .print-area *{visibility:visible;} .print-area{position:absolute;left:0;top:0;width:100%;} .no-print{display:none !important;} }
            </style>
            <div class="no-print" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
                <button class="btn btn-primary" onclick="window.print()">Imprimir planillas</button>
                <a class="btn btn-ghost" href="?id=<?php echo (int)$id; ?>&tab=planillas">Recargar</a>
            </div>
            <div class="print-area">
            <!-- CENTRALIZADOR ANVERSO -->
            <div class="sheet">
                <h2>CENTRALIZADOR DE CALIFICACIONES — ANVERSO</h2>
                <div class="meta">
                    <strong>INSTITUCIÓN:</strong> Instituto Tecnológico PACCIOLI &nbsp; | &nbsp; <strong>CARRERA:</strong> <?php echo htmlspecialchars($curso['carrera_nombre']); ?> &nbsp; | &nbsp; <strong>CURSO:</strong> <?php echo htmlspecialchars($curso['nombre']); ?> <?php echo htmlspecialchars($curso['paralelo']); ?> &nbsp; | &nbsp; <strong>GESTIÓN:</strong> <?php echo (int)$gestion; ?> &nbsp; | &nbsp; <strong>TURNO:</strong> <?php echo htmlspecialchars($curso['turno']??'mañana'); ?>
                </div>
                <table>
                    <thead><tr><th>N°</th><th>NÓMINA ESTUDIANTES</th><th>CÉDULA</th><?php foreach ($centralMaterias as $cm): ?><th><?php echo htmlspecialchars($cm['codigo']); ?><br><span style="font-size:9px;"><?php echo htmlspecialchars($cm['nombre']); ?></span></th><?php endforeach; ?><th>ESTADO</th><th>OBS.</th></tr></thead>
                    <tbody>
                        <?php $n=1; foreach ($estudiantes as $e): ?>
                        <tr><td><?php echo $n++; ?></td><td style="text-align:left;"><?php echo htmlspecialchars($e['nombre_completo']); ?></td><td><?php echo htmlspecialchars($e['ci']); ?></td>
                        <?php foreach ($centralMaterias as $cm): $v=$centralNotas[(int)$e['id']][(int)$cm['id']] ?? null; ?><td><?php echo $v!==null? number_format($v,1):''; ?></td><?php endforeach; ?>
                        <td>—</td><td></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($estudiantes)): ?><tr><td colspan="<?php echo 5+count($centralMaterias); ?>">Sin estudiantes inscritos</td></tr><?php endif; ?>
                    </tbody>
                </table>
                <p style="font-size:11px;margin-top:6px;">NP=No se Presentó · AP=Aprobado · PRE=Prerrequisito &nbsp; | &nbsp; Firma Jefe Carrera / Director Académico / Rector</p>
            </div>
            <!-- CENTRALIZADOR REVERSO -->
            <div class="sheet">
                <h2>CENTRALIZADOR DE CALIFICACIONES — REVERSO</h2>
                <p style="font-size:12px;">Firmas por materia</p>
                <div class="firmas-grid">
                    <?php foreach ($centralMaterias as $cm): ?>
                    <div class="firma-bloque">
                        <div style="border-bottom:1px solid #333;margin:18px 10px 6px;"></div>
                        <div><?php echo htmlspecialchars($docentePorMateria[(int)$cm['id']] ?? 'Sin docente'); ?></div>
                        <div style="font-weight:bold;"><?php echo htmlspecialchars($cm['nombre']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <h3 style="margin-top:14px;font-size:13px;">Estadísticas</h3>
                <table style="width:60%;"><thead><tr><th>DETALLE</th><th>CANTIDAD</th><th>%</th></tr></thead>
                <tbody>
                    <tr><td>ESTUDIANTES INSCRITOS</td><td><?php echo (int)$totalInscritos; ?></td><td>100%</td></tr>
                    <tr><td>ESTUDIANTES APROBADOS</td><td><?php echo (int)$aprobados; ?></td><td><?php echo $totalInscritos? round($aprobados/$totalInscritos*100,1):0; ?>%</td></tr>
                    <tr><td>ESTUDIANTES REPROBADOS</td><td><?php echo (int)$reprobados; ?></td><td><?php echo $totalInscritos? round($reprobados/$totalInscritos*100,1):0; ?>%</td></tr>
                    <tr><td>ABANDONO</td><td><?php echo (int)$abandono; ?></td><td>0%</td></tr>
                </tbody></table>
            </div>
            <!-- ENTREGA DE CALIFICACIONES -->
            <div class="sheet">
                <h2>ENTREGA DE CALIFICACIONES</h2>
                <form method="GET" class="no-print" style="margin-bottom:10px;">
                    <input type="hidden" name="id" value="<?php echo (int)$id; ?>"><input type="hidden" name="tab" value="planillas">
                    <label>Materia: <select name="materia_id" onchange="this.form.submit()"><?php foreach ($centralMaterias as $cm): ?><option value="<?php echo (int)$cm['id']; ?>" <?php echo $entregaMateria && (int)$cm['id']===(int)$entregaMateria['id']?'selected':''; ?>><?php echo htmlspecialchars($cm['nombre']); ?></option><?php endforeach; ?></select></label>
                </form>
                <div class="meta">
                    <strong>CARRERA:</strong> <?php echo htmlspecialchars($curso['carrera_nombre']); ?> &nbsp; | &nbsp; <strong>MATERIA:</strong> <?php echo htmlspecialchars($entregaMateria['nombre'] ?? '—'); ?> &nbsp; | &nbsp; <strong>TURNO:</strong> <?php echo htmlspecialchars($curso['turno']??'mañana'); ?> &nbsp; | &nbsp;
                    <strong>NOTA APROBACIÓN:</strong> 61 &nbsp; | &nbsp; <strong>GESTIÓN:</strong> <?php echo (int)$gestion; ?> &nbsp; | &nbsp; <strong>DOCENTE:</strong> <?php echo htmlspecialchars($docentePorMateria[$entregaMateriaId] ?? '—'); ?>
                </div>
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

        <script>document.querySelectorAll('.modal-overlay').forEach(function(m){ m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active');});});</script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
