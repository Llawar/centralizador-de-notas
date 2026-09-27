<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CarrerasModel.php';
require_once __DIR__ . '/../../model/MateriasModel.php';
require_once __DIR__ . '/../../model/CursosModel.php';

$carrerasModel = new CarrerasModel();
$materiasModel = new MateriasModel();
$cursosModel = new CursosModel();

$id = (int) ($_GET['id'] ?? 0);
$carrera = $carrerasModel->getById($id);
if (!$carrera) { redirect('/view/admin/gestion_carreras.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear_materia') {
        $materiasModel->crear($_POST['nombre'], $_POST['codigo'], $id, (int) ($_POST['anio_carrera'] ?? 1));
        redirect("/view/admin/ver_carrera.php?id=$id&msg=created#materias");
    } elseif ($accion === 'eliminar_materia') {
        $materiasModel->eliminar((int) $_POST['materia_id']);
        redirect("/view/admin/ver_carrera.php?id=$id&msg=deleted#materias");
    }
}

$tab = $_GET['tab'] ?? 'info';
$materias1 = $materiasModel->getByAnio($id, 1);
$materias2 = $materiasModel->getByAnio($id, 2);
$materias3 = $materiasModel->getByAnio($id, 3);
$cursos = $cursosModel->getByCarrera($id);

$titulo = 'Detalle Carrera — ' . $carrera['nombre'];
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <a href="<?= BASE_URL ?>/view/admin/gestion_carreras.php" class="btn btn-ghost" style="margin-bottom:12px;">← Volver a Carreras</a>
        <div class="page-head" style="margin-bottom:10px;">
            <div>
                <h1><?php echo htmlspecialchars($carrera['nombre']); ?></h1>
                <p class="subtitle">Tipo: <?php echo htmlspecialchars($carrera['tipo']); ?> · Duración: <?php echo (int) $carrera['duracion']; ?> años · Estado: <?php echo htmlspecialchars($carrera['estado']); ?></p>
            </div>
            <a href="<?= BASE_URL ?>/view/admin/gestion_materias.php?carrera_id=<?php echo (int) $id; ?>" class="btn btn-ghost">Gestionar materias</a>
        </div>
        <?= flash_html(['created'=>'Operación exitosa.','deleted'=>'Eliminado.']) ?>

        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <a class="btn <?php echo $tab==='info'?'btn-primary':'btn-ghost'; ?>" href="?id=<?php echo (int)$id; ?>&tab=info">Info</a>
            <a class="btn <?php echo $tab==='materias'?'btn-primary':'btn-ghost'; ?>" href="?id=<?php echo (int)$id; ?>&tab=materias">Materias del plan (1/2/3)</a>
            <a class="btn <?php echo $tab==='cursos'?'btn-primary':'btn-ghost'; ?>" href="?id=<?php echo (int)$id; ?>&tab=cursos">Cursos</a>
        </div>

        <?php if ($tab === 'info'): ?>
            <div class="form-container" style="max-width:700px;">
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($carrera['nombre']); ?></p>
                <p><strong>Tipo:</strong> <?php echo htmlspecialchars($carrera['tipo']); ?> (<?php echo $carrera['tipo']==='semestral'?'2 parciales':'4 parciales'; ?>)</p>
                <p><strong>Duración:</strong> <?php echo (int) $carrera['duracion']; ?> años</p>
                <p><strong>Estado:</strong> <?php echo htmlspecialchars($carrera['estado']); ?></p>
            </div>
        <?php elseif ($tab === 'materias'): ?>
            <div id="materias"></div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h3>Materias por año</h3>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalMat').classList.add('active')">+ Nueva Materia</button>
            </div>
            <?php foreach ([1=> $materias1, 2=> $materias2, 3=> $materias3] as $anio => $lista): ?>
                <h4 style="margin:16px 0 8px;color:var(--accent-2);"><?php echo $anio; ?>° Año — <?php echo count($lista); ?> materias</h4>
                <?php if (empty($lista)): ?><p style="color:var(--text-3);">Sin materias en <?php echo $anio; ?>°.</p>
                <?php else: ?>
                <div class="tabla-contenedor" style="margin-bottom:12px;">
                    <table><thead><tr><th>Código</th><th>Nombre</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($lista as $m): ?><tr><td><?php echo htmlspecialchars($m['codigo']); ?></td><td><?php echo htmlspecialchars($m['nombre']); ?></td><td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?')"><?php echo csrf_campo(); ?><input type="hidden" name="accion" value="eliminar_materia"><input type="hidden" name="materia_id" value="<?php echo (int)$m['id']; ?>"><button class="btn btn-danger" style="padding:5px 10px;font-size:12px;">Eliminar</button></form>
                        </td></tr><?php endforeach; ?>
                    </tbody></table>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div id="modalMat" class="modal-overlay">
                <div class="modal">
                    <h3>Nueva Materia — <?php echo htmlspecialchars($carrera['nombre']); ?></h3>
                    <form method="POST">
                        <?php echo csrf_campo(); ?>
                        <input type="hidden" name="accion" value="crear_materia">
                        <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required></div>
                        <div class="form-group"><label>Código</label><input type="text" name="codigo" required></div>
                        <div class="form-group"><label>Año de carrera</label><select name="anio_carrera" required><option value="1">1er Año</option><option value="2">2do Año</option><option value="3">3er Año</option></select></div>
                        <div class="form-actions"><button type="submit" class="btn btn-success">Crear</button><button type="button" class="btn btn-danger" onclick="document.getElementById('modalMat').classList.remove('active')">Cancelar</button></div>
                    </form>
                </div>
            </div>
            <script>document.querySelectorAll('.modal-overlay').forEach(function(m){ m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active');});});</script>
        <?php elseif ($tab === 'cursos'): ?>
            <div class="tabla-contenedor"><table><thead><tr><th>Nombre</th><th>Año</th><th>Turno</th><th>Paralelo</th><th>Gestión</th><th>Acción</th></tr></thead>
            <tbody><?php foreach ($cursos as $c): ?><tr><td><?php echo htmlspecialchars($c['nombre']); ?></td><td><?php echo (int)$c['anio_carrera']; ?>°</td><td><?php echo htmlspecialchars($c['turno']); ?></td><td><?php echo htmlspecialchars($c['paralelo']); ?></td><td><?php echo (int)$c['gestion']; ?></td><td><?= acciones_columna((int)$c['id'], ['view'], ['view' => ['href' => BASE_URL . '/view/admin/ver_curso.php?id=' . (int)$c['id'], 'label' => 'Ver curso']]) ?></td></tr><?php endforeach; ?></tbody>
            </table></div>
            <?php if (empty($cursos)): ?><p style="color:var(--text-3);margin-top:8px;">Sin cursos para esta carrera. <a href="<?= BASE_URL ?>/view/admin/gestion_cursos.php" style="color:var(--accent-2);">Crear curso</a></p><?php endif; ?>
        <?php endif; ?>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
