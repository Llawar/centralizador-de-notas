<?php
require_once __DIR__ . '/../config/app.php';

if (!function_exists('auth_guard')) {
    /**
     * Verifica sesión y rol; redirige a login si falla.
     * @param string|array $roles Rol único o lista de roles permitidos
     */
    function auth_guard(string|array $roles): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $allowed = is_array($roles) ? $roles : [$roles];
        $userId = $_SESSION['user_id'] ?? null;
        $rol = $_SESSION['rol'] ?? null;
        if (empty($userId) || empty($rol) || !in_array($rol, $allowed, true)) {
            redirect('/index.php?error=session');
        }
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'rol' => $_SESSION['rol'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'referer_id' => $_SESSION['referer_id'] ?? null,
        ];
    }
}
