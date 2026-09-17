<?php
/**
 * Config central de rutas de la app.
 *
 * Una sola fuente de verdad para el prefijo web (BASE_URL).
 * Se autodetecta comparando el directorio del proyecto contra
 * DOCUMENT_ROOT, asi que si renombras la carpeta no tenes que
 * editar nada.
 *
 * Filesystem (require/include) -> seguir usando __DIR__.
 * Web (header/href/src/action/fetch) -> usar url() / redirect() / BASE_URL.
 */

if (!defined('BASE_URL')) {
    $base = '';
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']);
        $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: (__DIR__ . '/..'));
        $docRoot = rtrim($docRoot, '/');
        if (str_starts_with($appRoot, $docRoot)) {
            $base = substr($appRoot, strlen($docRoot));
        }
    }
    $base = '/' . trim($base, '/');
    if ($base === '/') {
        $base = '';
    }
    define('BASE_URL', $base);
}

if (!function_exists('url')) {
    /**
     * Construye una URL web desde la raiz de la app.
     * Ej: url('/view/admin/dashboard.php') -> /mi-carpeta/view/admin/dashboard.php
     */
    function url(string $path = ''): string
    {
        if ($path === '' || $path === '/') {
            return BASE_URL === '' ? '/' : BASE_URL;
        }
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /** Alias semantico para css/js/img. */
    function asset(string $path = ''): string
    {
        return url($path);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect + exit en una sola llamada.
     * Ej: redirect('/view/admin/dashboard.php');
     */
    function redirect(string $path = ''): never
    {
        header('Location: ' . url($path));
        exit;
    }
}
