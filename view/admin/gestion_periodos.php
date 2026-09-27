<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/CursosModel.php';
require_once __DIR__ . '/../../model/ParcialPeriodoModel.php';

$cursosModel = new CursosModel();
$parcialModel = new ParcialPeriodoModel();
$adminId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion  = $_POST['accion'] ?? '';
    $cursoId = (int) ($_POST['curso_id'] ?? 0);
    $gestion = (int) ($_POST['gestion'] ?? 0);
    $parcial = $_POST['parcial'] ?? '';

    if ($cursoId && $parcial !== '') {
        if ($accion === 'abrir') {
            $parcialModel->abrir($cursoId, $gestion, $parcial, $adminId);
            redirect("/view/admin/gestion_periodos.php?curso_id=$cursoId&msg=abierto");
        } elseif ($accion === 'cerrar') {
            $parcialModel->cerrar($cursoId, $gestion, $parcial);
            redirect("/view/admin/gestion_periodos.php?curso_id=$cursoId&msg=cerrado");
        }
    }
}

$gestionFiltro = isset($_GET['gestion']) && $_GET['gestion'] !== '' ? (int) $_GET['gestion'] : null;
$cursos = $gestionFiltro ? $cursosModel->getByGestion($gestionFiltro) : $cursosModel->getAll();
$cursoSelId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

$resumenes = [];
foreach ($cursos as $c) {
    $resumenes[$c['id']] = $parcialModel->resumenCurso((int) $c['id'], (int) ($c['gestion'] ?? date('Y')), $c['carrera_tipo'] ?? 'anual');
}
$titulo = 'Gestionar Parciales';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Gestionar Parciales (Temporadas)</h1>

        <?= flash_html(['abierto'=>'Parcial abierto: los docentes ya pueden registrar sus notas.','cerrado'=>'Parcial cerrado.']) ?>

        <p class="ayuda" style="margin-bottom:15px;">
            Abre un parcial para que los docentes del curso puedan registrar notas de ese parcial. Cuando todas las materias "envían" sus notas, el siguiente parcial se abre automáticamente.
        </p>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Carrera</th>
                        <th>Tipo</th>
                        <th>Gestión</th>
                        <th colspan="5">Parciales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cursos)): ?>
                        <tr><td colspan="9">No hay cursos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cursos as $c):
                            $tipo = $c['carrera_tipo'] ?? 'anual';
                            $res = $resumenes[$c['id']] ?? [];
                            ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong> - <?php echo htmlspecialchars($c['paralelo']); ?></td>
                            <td><?php echo htmlspecialchars($c['carrera_nombre']); ?></td>
                            <td><?php echo $tipo === 'semestral' ? 'Semestral' : 'Anual'; ?></td>
                            <td><?php echo (int) $c['gestion']; ?></td>
                            <?php foreach (ParcialPeriodoModel::opciones($tipo) as $parcial):
                                $d = $res[$parcial] ?? ['total' => 0, 'abiertos' => 0, 'enviados' => 0, 'cerrados' => 0];
                                $abiertos = (int) $d['abiertos'];
                                $enviados = (int) $d['enviados'];
                                $total    = (int) $d['total'];
                                $texto = $abiertos > 0 ? 'ABIERTO' : ($enviados >= $total && $total > 0 ? 'ENVIADO' : 'CERRADO');
                                $cls = $abiertos > 0 ? 'badge-abierto' : ($enviados >= $total && $total > 0 ? 'badge-enviado' : 'badge-cerrado');
                                ?>
                                <td style="text-align:center; vertical-align:middle;">
                                    <div>
                                        <span class="<?php echo $cls; ?>" style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; color:#fff; margin-bottom:4px;"><?php echo $texto; ?></span>
                                    </div>
                                    <form method="POST" style="display:inline;">
                                        <?php echo csrf_campo(); ?>
                                        <input type="hidden" name="accion" value="<?php echo $abiertos > 0 ? 'cerrar' : 'abrir'; ?>">
                                        <input type="hidden" name="curso_id" value="<?php echo (int) $c['id']; ?>">
                                        <input type="hidden" name="gestion" value="<?php echo (int) $c['gestion']; ?>">
                                        <input type="hidden" name="parcial" value="<?php echo htmlspecialchars($parcial); ?>">
                                        <button type="submit" class="btn <?php echo $abiertos > 0 ? 'btn-danger' : 'btn-primary'; ?>" style="padding:2px 8px; font-size:11px;">
                                            <?php echo $abiertos > 0 ? 'Cerrar' : 'Abrir'; ?>
                                        </button>
                                    </form>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
