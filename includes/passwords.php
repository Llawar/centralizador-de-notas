<?php
/**
 * Helper de contraseñas del sistema.
 *
 * - generar_password(): clave aleatoria de 6 (A-Z + 0-9) con random_int().
 *   36^6 = 2.176.782.336 combinaciones. El texto plano solo vive en la
 *   respuesta HTTP del alta/reseteo; en BD solo se guarda el hash.
 */

function generar_password(int $longitud = 6): string
{
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $n = strlen($alfabeto);
    $clave = '';
    for ($i = 0; $i < $longitud; $i++) {
        $clave .= $alfabeto[random_int(0, $n - 1)];
    }
    return $clave;
}

/**
 * Modal de credencial única: se muestra una sola vez tras crear o resetear.
 * Incluye Copiar e Imprimir tirilla. $nombre/$usuario/$password ya escapados aquí.
 */
function modal_credencial(string $titulo, string $nombre, string $usuario, string $password): string
{
    $t = htmlspecialchars($titulo);
    $n = htmlspecialchars($nombre);
    $u = htmlspecialchars($usuario);
    $p = htmlspecialchars($password);
    $fecha = date('d/m/Y H:i');
    return <<<HTML
    <div id="modalCredencial" class="modal-overlay active">
        <div class="modal" id="credencialBox">
            <h3>{$t}</h3>
            <p class="subtitle">Se muestra una sola vez. Entrégala al usuario en mano.</p>
            <div class="form-group"><label>Nombre</label><input type="text" value="{$n}" readonly onclick="this.select()"></div>
            <div class="form-group"><label>Usuario (C.I.)</label><input type="text" value="{$u}" readonly onclick="this.select()"></div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="text" id="credPass" value="{$p}" readonly onclick="this.select()" style="font-family:monospace;font-size:22px;letter-spacing:4px;text-align:center;font-weight:700;">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-primary" onclick="copiarCredencial()">Copiar</button>
                <button type="button" class="btn btn-success" onclick="imprimirCredencial()">Imprimir</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCredencial').classList.remove('active')">Cerrar</button>
            </div>
            <div id="tirilla" style="display:none;">
                <p style="text-align:center;font-weight:700;">Instituto Tecnológico PACCIOLI<br>Sistema Centralizador de Notas</p>
                <p>Nombre: {$n}<br>Usuario (C.I.): {$u}<br>Contraseña: {$p}</p>
                <p>Fecha: {$fecha}<br><br>Firma entrega: __________________<br>Firma recibe: __________________</p>
            </div>
        </div>
    </div>
    <script>
    function copiarCredencial(){ var i=document.getElementById('credPass'); i.select(); var t=i.value; if(navigator.clipboard){ navigator.clipboard.writeText(t); } else { document.execCommand('copy'); } }
    function imprimirCredencial(){ var c=document.getElementById('tirilla').innerHTML; var w=window.open('','_blank','width=400,height=500'); w.document.write('<html><head><title>Credencial PACCIOLI</title></head><body>'+c+'<\/body><\/html>'); w.document.close(); w.focus(); w.print(); }
    </script>
HTML;
}
