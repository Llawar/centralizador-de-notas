<?php
/**
 * Helper: columna de acciones (Editar / Eliminar / Ver) para tablas CRUD.
 *
 * Uso en vista:
 *   <td><?= acciones_columna($id, ['edit','delete'], $config) ?></td>
 *
 * @param int    $id        ID del registro
 * @param array  $mostrar   Qué botones mostrar: subset de ['edit','delete','view']
 * @param array  $config    Overrides por acción + common
 * @return string           HTML listo para echo
 */
function acciones_columna(int $id, array $mostrar = ['edit', 'delete', 'view'], array $config = []): string
{
    // Config por defecto (defaults sensatos)
    $defaults = [
        'edit'  => [
            'icon'   => 'i-edit',
            'label'  => 'Editar',
            'class'  => 'btn-primary',
            'onclick' => '',          // obligatorio si se usa 'edit'
            'attrs'  => '',
        ],
        'delete' => [
            'icon'      => 'i-trash',
            'label'     => 'Eliminar',
            'class'     => 'btn-danger',
            'form_action' => '',      // URL del form (p.ej. 'gestion_carreras.php')
            'confirm'   => '¿Eliminar este registro?',
            'attrs'     => '',
        ],
        'view' => [
            'icon'  => 'i-eye',
            'label' => 'Ver',
            'class' => 'btn-ghost',
            'href'  => '',            // obligatorio si se usa 'view'
            'attrs' => '',
        ],
        'common' => [
            'btn_size' => 'sm',       // sm | md | lg  (afecta padding/fuente)
            'gap'      => 6,          // px ENTRE botones (no dentro)
            'wrapper'  => 'inline',   // inline | stack
        ],
    ];

    // Merge recursivo simple (config gana sobre defaults)
    $cfg = $defaults;
    foreach ($defaults as $key => $val) {
        if (isset($config[$key]) && is_array($config[$key])) {
            $cfg[$key] = array_merge($val, $config[$key]);
        }
    }
    if (isset($config['common']) && is_array($config['common'])) {
        $cfg['common'] = array_merge($defaults['common'], $config['common']);
    }

    // Tamaños de botón (sin gap interno)
    $sizeMap = [
        'sm' => 'padding:6px 10px;font-size:12px;',
        'md' => 'padding:8px 14px;font-size:13.5px;',
        'lg' => 'padding:10px 18px;font-size:15px;',
    ];
    $btnStyle = $sizeMap[$cfg['common']['btn_size']] ?? $sizeMap['sm'];
    $gap = (int) $cfg['common']['gap'];
    $wrapperStyle = "display:inline-flex;align-items:center;gap:{$gap}px;";

    $buttons = [];

    // ----- EDIT -----
    if (in_array('edit', $mostrar, true)) {
        $e = $cfg['edit'];
        $onclick = $e['onclick'] ?: "console.warn('acciones_columna: edit sin onclick')";
        $attrs = $e['attrs'] ? ' ' . $e['attrs'] : '';
        $buttons[] = "<button type=\"button\" class=\"btn {$e['class']}\" style=\"{$btnStyle}\" onclick=\"{$onclick}\"{$attrs}>"
                   . "<svg class=\"ic\" style=\"width:14px;height:14px;\"><use href=\"#{$e['icon']}\"></use></svg>"
                   . htmlspecialchars($e['label'])
                   . "</button>";
    }

    // ----- DELETE -----
    if (in_array('delete', $mostrar, true)) {
        $d = $cfg['delete'];
        $action = $d['form_action'] ? 'action="' . htmlspecialchars($d['form_action']) . '"' : '';
        $confirm = $d['confirm'] ? 'onsubmit="return confirm(\'' . addslashes($d['confirm']) . '\')"' : '';
        $attrs = $d['attrs'] ? ' ' . $d['attrs'] : '';
        $buttons[] = "<form method=\"POST\" style=\"display:inline;\" {$action} {$confirm}>"
                   . csrf_campo()
                   . "<input type=\"hidden\" name=\"accion\" value=\"eliminar\">"
                   . "<input type=\"hidden\" name=\"id\" value=\"{$id}\">"
                   . "<button type=\"submit\" class=\"btn {$d['class']}\" style=\"{$btnStyle}\"{$attrs}>"
                   . "<svg class=\"ic\" style=\"width:14px;height:14px;\"><use href=\"#{$d['icon']}\"></use></svg>"
                   . htmlspecialchars($d['label'])
                   . "</button>"
                   . "</form>";
    }

    // ----- VIEW -----
    if (in_array('view', $mostrar, true)) {
        $v = $cfg['view'];
        $href = $v['href'] ? 'href="' . htmlspecialchars($v['href']) . '"' : 'href="#"';
        $attrs = $v['attrs'] ? ' ' . $v['attrs'] : '';
        $buttons[] = "<a class=\"btn {$v['class']}\" style=\"{$btnStyle}\" {$href}{$attrs}>"
                   . "<svg class=\"ic\" style=\"width:14px;height:14px;\"><use href=\"#{$v['icon']}\"></use></svg>"
                   . htmlspecialchars($v['label'])
                   . "</a>";
    }

    // Wrapper con gap ENTRE botones (solo si hay 2+)
    if (count($buttons) <= 1) {
        return implode('', $buttons);
    }
    return "<div style=\"{$wrapperStyle}\">" . implode('', $buttons) . "</div>";
}