<?php
if (!function_exists('flash_html')) {
    /**
     * Renderiza mensaje flash basado en $_GET['msg'].
     * @param array $mapa Mensajes personalizados ['created'=>'Texto', ...]
     */
    function flash_html(array $mapa = []): string
    {
        $msg = $_GET['msg'] ?? '';
        if ($msg === '') {
            return '';
        }
        $default = [
            'created' => 'Registro creado exitosamente.',
            'updated' => 'Registro actualizado exitosamente.',
            'deleted' => 'Registro eliminado exitosamente.',
        ];
        $mapa = array_merge($default, $mapa);
        if (!isset($mapa[$msg])) {
            return '';
        }
        return '<div class="alert alert-success">' . htmlspecialchars($mapa[$msg], ENT_QUOTES) . '</div>';
    }
}
