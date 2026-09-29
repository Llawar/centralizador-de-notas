<?php
require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../model/UsuariosModel.php';
require_once __DIR__ . '/../../model/DocentesModel.php';
require_once __DIR__ . '/../../model/EstudiantesModel.php';

$model = new UsuariosModel();
$docentesModel = new DocentesModel();
$estudiantesModel = new EstudiantesModel();
$cred = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $rol = $_POST['rol'] ?? 'admin';
        if ($rol === 'admin') {
            $model->crear($_POST['username'], $_POST['password'], 'admin');
            redirect("/view/admin/usuarios.php?msg=created");
        } elseif ($rol === 'docente') {
            $res = $docentesModel->crear($_POST['ci'], $_POST['nombre'], $_POST['anio_ingreso']);
            if ($res !== false) {
                $cred = ['titulo' => 'Docente creado', 'nombre' => $_POST['nombre'], 'usuario' => $_POST['ci'], 'password' => $res['password'] ?? '—'];
            } else {
                redirect("/view/admin/usuarios.php?msg=error");
            }
        } elseif ($rol === 'estudiante') {
            $res = $estudiantesModel->crear($_POST['ci'], $_POST['nombre'], $_POST['matricula'], $_POST['anio_ingreso']);
            if ($res !== false) {
                $cred = ['titulo' => 'Estudiante creado', 'nombre' => $_POST['nombre'], 'usuario' => $_POST['ci'], 'password' => $res['password'] ?? '—'];
            } else {
                redirect("/view/admin/usuarios.php?msg=error");
            }
        } else {
            redirect("/view/admin/usuarios.php?msg=error");
        }
    } elseif ($accion === 'editar') {
        $usr = $model->getUsuarioById((int) $_POST['id']);
        if (!$usr) redirect("/view/admin/usuarios.php?msg=error");
        $estado = $_POST['estado'] ?? 'activo';
        if (($usr['rol'] ?? '') === 'admin') {
            $model->actualizar((int) $usr['id'], $_POST['username'], $estado);
        } elseif (($usr['rol'] ?? '') === 'docente' && !empty($usr['docente_id'])) {
            $docentesModel->actualizar((int) $usr['docente_id'], $_POST['ci'], $_POST['nombre'], $_POST['anio_ingreso'], $estado);
            $model->actualizar((int) $usr['id'], $_POST['ci'], $estado);
        } elseif (($usr['rol'] ?? '') === 'estudiante' && !empty($usr['estudiante_id'])) {
            $estudiantesModel->actualizar((int) $usr['estudiante_id'], $_POST['ci'], $_POST['nombre'], $_POST['matricula'], $_POST['anio_ingreso'], $estado);
            $model->actualizar((int) $usr['id'], $_POST['ci'], $estado);
        }
        redirect("/view/admin/usuarios.php?msg=updated");
    } elseif ($accion === 'eliminar') {
        $usr = $model->getUsuarioById((int) $_POST['id']);
        if (!$usr) redirect("/view/admin/usuarios.php?msg=error");
        $model->eliminar((int) $usr['id']);
        if (!empty($usr['docente_id'])) $docentesModel->eliminar((int) $usr['docente_id']);
        if (!empty($usr['estudiante_id'])) $estudiantesModel->eliminar((int) $usr['estudiante_id']);
        redirect("/view/admin/usuarios.php?msg=deleted");
    } elseif ($accion === 'resetear') {
        $usr = $model->getUsuarioById((int) $_POST['id']);
        if (!$usr || ($usr['rol'] ?? '') === 'admin') redirect("/view/admin/usuarios.php?msg=error");
        $nueva = $model->resetearPassword((int) $usr['id']);
        if ($nueva !== false) {
            $nombre = $usr['username'];
            foreach ($model->getAll() as $row) {
                if ((int) $row['id'] === (int) $usr['id']) {
                    $nombre = $row['docente_nombre'] ?? $row['estudiante_nombre'] ?? $usr['username'];
                    break;
                }
            }
            $cred = ['titulo' => 'Contraseña reseteada', 'nombre' => $nombre, 'usuario' => $usr['username'], 'password' => $nueva];
        } else {
            redirect("/view/admin/usuarios.php?msg=error");
        }
    }
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET['q'] ?? '');

$res = $model->getAllPaginated($limit, $offset, $search);
$usuarios = $res['data'];
$total = $res['total'];
$totalPages = max(1, (int)ceil($total / $limit));

$titulo = 'Usuarios';
require_once __DIR__ . '/../../includes/layout_header.php';
?>
        <div class="page-head" style="margin-bottom:18px;">
            <div>
                <h1>Usuarios</h1>
                <p class="subtitle">Centro único de gestión: altas, ediciones, bajas y reseteo de contraseñas. Docentes/estudiantes entran con C.I. + clave de 6.</p>
            </div>
            <div class="head-actions">
                <form method="GET" style="display:flex;gap:8px;">
                    <input type="search" name="q" id="qUsuarios" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar usuario, nombre o CI…" style="padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.05);color:var(--text);width:280px;">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <?php if ($search): ?><a class="btn btn-ghost" href="<?= BASE_URL ?>/view/admin/usuarios.php">Limpiar</a><?php endif; ?>
                </form>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('modalCrear').classList.add('active')">+ Nuevo Usuario</button>
            </div>
        </div>
        <?= flash_html(['created'=>'Usuario creado.','updated'=>'Usuario actualizado exitosamente.','deleted'=>'Usuario eliminado exitosamente.','error'=>'No se pudo procesar la solicitud.']) ?>
        <?php if ($cred !== null) echo modal_credencial($cred['titulo'], $cred['nombre'], $cred['usuario'], (string) $cred['password']); ?>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>N°</th><th>Nombre completo</th><th>C.I.</th><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody id="tbodyUsuarios">
                    <?php $n = $offset + 1; foreach ($usuarios as $u):
                        $esAdmin = ($u['rol'] ?? '') === 'admin';
                        $nombre = $u['docente_nombre'] ?? $u['estudiante_nombre'] ?? $u['username'];
                        $ci = $u['docente_ci'] ?? $u['estudiante_ci'] ?? '—';
                        $matricula = $u['estudiante_matricula'] ?? '';
                        $anio = $u['docente_anio'] ?? $u['estudiante_anio'] ?? date('Y');
                    ?>
                    <tr>
                        <td><?php echo $n++; ?></td>
                        <td><?php echo htmlspecialchars($nombre); ?></td>
                        <td><?php echo htmlspecialchars((string) $ci); ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['rol']); ?></td>
                        <td><?php echo htmlspecialchars($u['estado']); ?></td>
                        <td>
                            <?php if ($esAdmin): ?>
                                <?= acciones_columna((int)$u['id'], ['edit','delete'], [
                                    'edit' => ['onclick' => "editarUsuario({$u['id']},'admin','".htmlspecialchars($u['username'], ENT_QUOTES)."','','".htmlspecialchars($u['username'], ENT_QUOTES)."','','".htmlspecialchars((string)date('Y'), ENT_QUOTES)."','".htmlspecialchars($u['estado'], ENT_QUOTES)."')"],
                                    'delete' => ['form_action'=>'usuarios.php','confirm'=>'¿Eliminar este administrador?']
                                ]) ?>
                            <?php else: ?>
                                <?= acciones_columna((int)$u['id'], ['edit','reset','delete'], [
                                    'edit' => ['onclick' => "editarUsuario({$u['id']},'".htmlspecialchars($u['rol'], ENT_QUOTES)."','".htmlspecialchars($nombre, ENT_QUOTES)."','".htmlspecialchars((string)$ci, ENT_QUOTES)."','".htmlspecialchars($u['username'], ENT_QUOTES)."','".htmlspecialchars((string)$matricula, ENT_QUOTES)."','".htmlspecialchars((string)$anio, ENT_QUOTES)."','".htmlspecialchars($u['estado'], ENT_QUOTES)."')"],
                                    'reset' => ['form_action' => 'usuarios.php'],
                                    'delete' => ['form_action'=>'usuarios.php','confirm'=>'¿Eliminar este usuario y su ficha? Se borrarán también sus inscripciones/asignaciones.']
                                ]) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usuarios)): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-3);">No hay usuarios</td></tr>
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
        <p style="text-align:center;color:var(--text-3);font-size:12px;margin-top:8px;">Mostrando <?php echo $offset+1; ?>–<?php echo min($offset+$limit,$total); ?> de <?php echo $total; ?> usuarios</p>
        <?php endif; ?>
    <div id="modalCrear" class="modal-overlay">
        <div class="modal">
            <h3>Nuevo Usuario</h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="crear">
                <div class="form-group"><label>Rol</label>
                    <select name="rol" id="crearRol" onchange="toggleCrear()" required>
                        <option value="docente">Docente</option>
                        <option value="estudiante">Estudiante</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group" id="gNombre"><label>Nombre completo</label><input type="text" name="nombre"></div>
                <div class="form-group" id="gCi"><label>C.I.</label><input type="text" name="ci" pattern="[0-9]+" title="Solo números"></div>
                <div class="form-group" id="gMatricula" style="display:none;"><label>Matrícula</label><input type="text" name="matricula"></div>
                <div class="form-group" id="gAnio" style="display:none;"><label>Año Ingreso</label><input type="number" name="anio_ingreso" value="<?php echo date('Y'); ?>" min="2000" max="2100"></div>
                <div class="form-group" id="gUsername" style="display:none;"><label>Usuario (admin)</label><input type="text" name="username"></div>
                <div class="form-group" id="gPassword" style="display:none;"><label>Contraseña (admin)</label><input type="password" name="password"></div>
                <p class="subtitle" id="notaClave">Se generará una contraseña aleatoria de 6 caracteres (se muestra una sola vez).</p>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Crear usuario</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCrear').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <div id="modalEditar" class="modal-overlay">
        <div class="modal">
            <h3>Editar Usuario <span id="editRolLbl" style="font-size:13px;opacity:.7;"></span></h3>
            <form method="POST">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editId">
                <div class="form-group" id="eGNombre"><label>Nombre completo</label><input type="text" name="nombre" id="editNombre"></div>
                <div class="form-group" id="eGCi"><label>C.I. (será también su usuario)</label><input type="text" name="ci" id="editCi"></div>
                <div class="form-group" id="eGUsername" style="display:none;"><label>Usuario</label><input type="text" name="username" id="editUsername"></div>
                <div class="form-group" id="eGMatricula" style="display:none;"><label>Matrícula</label><input type="text" name="matricula" id="editMatricula"></div>
                <div class="form-group" id="eGAnio" style="display:none;"><label>Año Ingreso</label><input type="number" name="anio_ingreso" id="editAnio" min="2000" max="2100"></div>
                <div class="form-group"><label>Estado</label><select name="estado" id="editEstado"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalEditar').classList.remove('active')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function toggleCrear() {
        var rol = document.getElementById('crearRol').value;
        var esAdmin = (rol === 'admin');
        var esEst = (rol === 'estudiante');
        document.getElementById('gNombre').style.display = esAdmin ? 'none' : '';
        document.getElementById('gCi').style.display = esAdmin ? 'none' : '';
        document.getElementById('gMatricula').style.display = esEst ? '' : 'none';
        document.getElementById('gAnio').style.display = esAdmin ? 'none' : '';
        document.getElementById('gUsername').style.display = esAdmin ? '' : 'none';
        document.getElementById('gPassword').style.display = esAdmin ? '' : 'none';
        document.getElementById('notaClave').style.display = esAdmin ? 'none' : '';
    }
    toggleCrear();
    function editarUsuario(id, rol, nombre, ci, username, matricula, anio, estado) {
        document.getElementById('editId').value = id;
        document.getElementById('editRolLbl').textContent = '(' + rol + ')';
        var esAdmin = (rol === 'admin');
        var esEst = (rol === 'estudiante');
        document.getElementById('eGNombre').style.display = esAdmin ? 'none' : '';
        document.getElementById('eGCi').style.display = esAdmin ? 'none' : '';
        document.getElementById('eGUsername').style.display = esAdmin ? '' : 'none';
        document.getElementById('eGMatricula').style.display = esEst ? '' : 'none';
        document.getElementById('eGAnio').style.display = esAdmin ? 'none' : '';
        document.getElementById('editNombre').value = nombre;
        document.getElementById('editCi').value = ci;
        document.getElementById('editUsername').value = username;
        document.getElementById('editMatricula').value = matricula;
        document.getElementById('editAnio').value = anio;
        document.getElementById('editEstado').value = estado;
        document.getElementById('modalEditar').classList.add('active');
    }
    (function(){ var q=document.getElementById('qUsuarios'); var tb=document.getElementById('tbodyUsuarios'); if(q&&tb){ q.addEventListener('input',function(){ var t=q.value.toLowerCase(); Array.from(tb.rows).forEach(function(r){ r.style.display=r.textContent.toLowerCase().includes(t)?'':'none'; }); }); } document.querySelectorAll('.modal-overlay').forEach(function(m){ if(m.id==='modalCredencial') return; m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('active'); }); }); })();
    </script>
<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>