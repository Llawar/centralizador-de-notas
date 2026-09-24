<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_guard('admin');
require_once __DIR__ . '/../../model/CursosModel.php';

$cursosModel = new CursosModel();
$conn = getConnection();
$result = $conn->query("SELECT DISTINCT gestion FROM cursos ORDER BY gestion DESC");
$anios = [];
while ($row = $result->fetch_assoc()) { $anios[] = $row['gestion']; }
$titulo = 'Anios de Clase';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <h1>Anios de Clase</h1>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Gestion</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($anios as $a): ?>
                    <tr>
                        <td><?php echo (int) $a; ?></td>
                        <td><a href="<?= BASE_URL ?>/view/admin/gestion_cursos.php?gestion=<?php echo (int) $a; ?>" class="btn btn-primary">Ver Cursos</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
