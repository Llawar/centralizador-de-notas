<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';

$model = new EstudiantesModel();
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');

$res = $model->getAllPaginated($limit, $offset, $search);
$estudiantes = $res['data'];
$total = $res['total'];
$totalPages = max(1, (int)ceil($total / $limit));

$titulo = 'Estudiantes';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <div>
                <h1>Estudiantes</h1>
                <p class="subtitle">Consulta del alumnado (todas las gestiones). Las altas, ediciones, bajas y reseteos se gestionan en Usuarios.</p>
            </div>
            <div class="head-actions">
                <form method="GET" style="display:flex;gap:8px;">
                    <input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar por nombre, CI o matrícula…" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.05);color:var(--text);width:280px;">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <?php if ($search): ?><a class="btn btn-ghost" href="<?= BASE_URL ?>/view/admin/gestion_estudiantes.php">Limpiar</a><?php endif; ?>
                </form>
            </div>
        </div>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>N°</th><th>C.I.</th><th>Nombre</th><th>Matrícula</th><th>Año Ingreso</th><th>Estado</th><th>Inscripción</th></tr></thead>
                <tbody id="tbodyEstudiantes">
                    <?php $n = $offset + 1; foreach ($estudiantes as $e): ?>
                    <tr>
                        <td><?php echo $n++; ?></td>
                        <td><?php echo htmlspecialchars($e['ci']); ?></td>
                        <td><?php echo htmlspecialchars($e['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($e['matricula']); ?></td>
                        <td><?php echo htmlspecialchars($e['anio_ingreso']); ?></td>
                        <td><?php echo htmlspecialchars($e['estado']); ?></td>
                        <td>
                            <?= acciones_columna((int)$e['id'], ['view'], [
                                'view' => ['href' => BASE_URL . '/view/admin/inscripciones.php?estudiante_id=' . (int)$e['id'], 'label' => 'Inscribir']
                            ]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($estudiantes)): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-3);">No hay estudiantes</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav class="pagination" style="display:flex;justify-content:center;gap:6px;margin-top:16px;flex-wrap:wrap;">
            <?php if ($page > 1): ?>
                <a class="btn btn-ghost" href="?page=<?php echo $page-1; ?><?php echo $search?'&q='.urlencode($search):''; ?>">« Anterior</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($p = $start; $p <= $end; $p++):
            ?>
                <a class="btn <?php echo $p === $page ? 'btn-primary' : 'btn-ghost'; ?>" href="?page=<?php echo $p; ?><?php echo $search?'&q='.urlencode($search):''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a class="btn btn-ghost" href="?page=<?php echo $page+1; ?><?php echo $search?'&q='.urlencode($search):''; ?>">Siguiente »</a>
            <?php endif; ?>
        </nav>
        <p style="text-align:center;color:var(--text-3);font-size:12px;margin-top:8px;">Mostrando <?php echo $offset+1; ?>–<?php echo min($offset+$limit,$total); ?> de <?php echo $total; ?> estudiantes</p>
        <?php endif; ?>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>