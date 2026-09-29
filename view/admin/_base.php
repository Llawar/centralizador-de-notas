<?php
/**
 * Base común para vistas de administrador.
 * Incluye config, auth, csrf, flash, helper de acciones y guarda admin.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/acciones.php';
require_once __DIR__ . '/../../includes/passwords.php';

auth_guard('admin');