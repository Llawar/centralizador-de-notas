<?php
session_start();
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/csrf.php';
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['rol']) {
        case 'admin': redirect("/view/admin/dashboard.php"); break;
        case 'docente': redirect("/view/docente/dashboard.php"); break;
        case 'estudiante': redirect("/view/estudiante/dashboard.php"); break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión - Instituto Tecnológico PACCIOLI</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos_login.css">
</head>
<body>
<?php include __DIR__ . '/includes/icons.php'; ?><canvas id="canvas" aria-hidden="true"></canvas>
<div class="login-container">
  <div class="login-card">
    <div class="login-header">
      <img src="<?= BASE_URL ?>/view/img/escudo.jpg" alt="Escudo" class="logo">
      <h1>Instituto Tecnológico <span>PACCIOLI</span></h1>
      <h2>Sistema Centralizador de Notas</h2>
    </div>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-error">
        <?php switch($_GET['error']){ case 'complete': echo 'Complete todos los campos.'; break; case 'invalid': echo 'Usuario o contraseña incorrectos.'; break; case 'session': echo 'Debe iniciar sesión.'; break; default: echo 'Error desconocido.'; } ?>
      </div>
    <?php endif; ?>
    <form action="<?= BASE_URL ?>/controller/LoginController.php" method="POST" class="login-form">
      <?php echo csrf_campo(); ?>
      
      <!-- Input Usuario -->
      <div class="input-group">
        <!-- Icono SVG inline seguro -->
        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <input type="text" name="username" placeholder="Usuario o Correo" required autofocus autocomplete="off">
      </div>

      <!-- Input Contraseña -->
      <div class="input-group">
        <!-- Icono SVG inline seguro (Candado) -->
        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        <input type="password" name="password" placeholder="Contraseña" required>
      </div>

      <button type="submit" class="btn-login">Iniciar Sesión</button>
    </form>
  </div>
</div>
<script src="<?= BASE_URL ?>/js/fondo.js"></script>
</body>
</html>
