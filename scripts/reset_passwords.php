<?php
/**
 * Reset de contraseñas — fase desarrollo.
 *
 * Usa el mismo password_hash() que UsuariosModel::cambiarPassword(),
 * asi que el login (password_verify) sigue andando.
 *
 * Uso:
 *   php scripts/reset_passwords.php --list
 *   php scripts/reset_passwords.php --id=5 --password=Nueva123 --yes
 *   php scripts/reset_passwords.php --ids=1,3,7 --password=Nueva123
 *   php scripts/reset_passwords.php --rango=10-20 --password=Nueva123
 *   php scripts/reset_passwords.php --rol=estudiante --password=Nueva123 --yes
 *   php scripts/reset_passwords.php --ids=1,2,3            (genera aleatoria por usuario)
 *   php scripts/reset_passwords.php --id=5 --dry-run       (muestra sin guardar)
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo uso CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../config/conexion.php';

const ROLES_VALIDOS = ['admin', 'docente', 'estudiante'];

function ayuda(): void
{
    echo <<<TXT
Uso: php scripts/reset_passwords.php [opciones]

Selección (al menos una, se pueden combinar --id/--ids/--rango):
  --list              Lista usuarios (id, username, rol, estado) y sale
  --id=5              Un ID
  --ids=1,3,7         Lista de IDs separados por coma
  --rango=10-20       Rango inclusivo
  --rol=ROL           Filtra por rol (admin|docente|estudiante).
                      Si es la única selección, afecta a todos de ese rol.
  --all               Todos los usuarios (pide confirmación)

Contraseña:
  --password=XXX      Misma clave para todos los seleccionados.
                      Si se omite, genera una aleatoria por usuario y la muestra.

Ejecución:
  --dry-run           Muestra lo que haría sin guardar
  -y, --yes           Salta la confirmación
  -h, --help          Esta ayuda

Ejemplos:
  php scripts/reset_passwords.php --list
  php scripts/reset_passwords.php --id=5 --password=Admin123 --yes
  php scripts/reset_passwords.php --ids=1,3,7 --password=Dev12345
  php scripts/reset_passwords.php --rango=10-20 --password=Dev12345 --yes

TXT;
}

/** Genera clave dev legible: Dev-a1b2c3 */
function claveAleatoria(): string
{
    return 'Dev-' . bin2hex(random_bytes(3));
}

function tabla(array $filas, array $cab): void
{
    $anchos = array_map('strlen', $cab);
    foreach ($filas as $f) {
        foreach (array_values($f) as $i => $v) {
            $anchos[$i] = max($anchos[$i], strlen((string) $v));
        }
    }
    $fmt = '';
    foreach ($anchos as $a) {
        $fmt .= '%-' . ($a + 2) . 's';
    }
    $fmt .= PHP_EOL;
    printf($fmt, ...$cab);
    printf($fmt, ...array_map(fn($a) => str_repeat('-', $a), $anchos));
    foreach ($filas as $f) {
        printf($fmt, ...array_values($f));
    }
}

// ---- parseo de args ----
$args = $argv;
array_shift($args);
$opt = ['ids' => [], 'rol' => null, 'all' => false, 'list' => false, 'dry' => false, 'yes' => false, 'password' => null];

foreach ($args as $a) {
    if ($a === '--list') $opt['list'] = true;
    elseif ($a === '--all') $opt['all'] = true;
    elseif ($a === '--dry-run') $opt['dry'] = true;
    elseif ($a === '-y' || $a === '--yes') $opt['yes'] = true;
    elseif ($a === '-h' || $a === '--help') {
        ayuda();
        exit(0);
    } elseif (str_starts_with($a, '--id=')) {
        $v = trim(substr($a, 5));
        if (!ctype_digit($v)) {
            fwrite(STDERR, "--id inválido: $v\n");
            exit(1);
        }
        $opt['ids'][] = (int) $v;
    } elseif (str_starts_with($a, '--ids=')) {
        foreach (explode(',', substr($a, 6)) as $v) {
            $v = trim($v);
            if ($v === '') continue;
            if (!ctype_digit($v)) {
                fwrite(STDERR, "--ids inválido: $v\n");
                exit(1);
            }
            $opt['ids'][] = (int) $v;
        }
    } elseif (str_starts_with($a, '--rango=')) {
        $r = trim(substr($a, 8));
        if (!preg_match('/^(\d+)-(\d+)$/', $r, $m)) {
            fwrite(STDERR, "--rango inválido (formato INI-FIN): $r\n");
            exit(1);
        }
        [$ini, $fin] = [(int) $m[1], (int) $m[2]];
        if ($ini > $fin) {
            fwrite(STDERR, "--rango invertido: $r\n");
            exit(1);
        }
        if ($fin - $ini > 10000) {
            fwrite(STDERR, "--rango demasiado grande (máx 10000).\n");
            exit(1);
        }
        for ($i = $ini; $i <= $fin; $i++) $opt['ids'][] = $i;
    } elseif (str_starts_with($a, '--rol=')) {
        $r = strtolower(trim(substr($a, 6)));
        if (!in_array($r, ROLES_VALIDOS, true)) {
            fwrite(STDERR, "--rol inválido (admin|docente|estudiante): $r\n");
            exit(1);
        }
        $opt['rol'] = $r;
    } elseif (str_starts_with($a, '--password=')) {
        $opt['password'] = substr($a, 11);
        if (strlen($opt['password']) < 4) {
            fwrite(STDERR, "--password muy corta (mín 4 caracteres).\n");
            exit(1);
        }
    } else {
        fwrite(STDERR, "Opción desconocida: $a\n\n");
        ayuda();
        exit(1);
    }
}

$conn = getConnection();

// ---- --list ----
if ($opt['list']) {
    $sql = "SELECT id, username, rol, estado FROM usuarios";
    $params = [];
    $types = '';
    if ($opt['rol']) {
        $sql .= " WHERE rol = ?";
        $params[] = $opt['rol'];
        $types = 's';
    }
    $sql .= " ORDER BY id";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $filas = [];
    while ($u = $res->fetch_assoc()) $filas[] = $u;
    $stmt->close();
    if (!$filas) {
        echo "Sin usuarios.\n";
        exit(0);
    }
    tabla($filas, ['id', 'username', 'rol', 'estado']);
    echo count($filas) . " usuario(s).\n";
    exit(0);
}

// ---- construir selección ----
$opt['ids'] = array_values(array_unique($opt['ids']));

if (!$opt['ids'] && !$opt['rol'] && !$opt['all']) {
    fwrite(STDERR, "Nada que hacer: pasá --id, --ids, --rango, --rol o --all.\n\n");
    ayuda();
    exit(1);
}

// Resolver IDs reales en BD
$usuarios = [];
if ($opt['all'] || ($opt['rol'] && !$opt['ids'])) {
    // Todos o todos de un rol
    $sql = "SELECT id, username, rol, estado FROM usuarios";
    $params = [];
    $types = '';
    if ($opt['rol']) {
        $sql .= " WHERE rol = ?";
        $params[] = $opt['rol'];
        $types = 's';
    }
    $sql .= " ORDER BY id";
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($u = $res->fetch_assoc()) $usuarios[] = $u;
    $stmt->close();
} else {
    // IDs explícitos (+ filtro rol si se pasó)
    $ph = implode(',', array_fill(0, count($opt['ids']), '?'));
    $sql = "SELECT id, username, rol, estado FROM usuarios WHERE id IN ($ph)";
    $params = $opt['ids'];
    $types = str_repeat('i', count($params));
    if ($opt['rol']) {
        $sql .= " AND rol = ?";
        $params[] = $opt['rol'];
        $types .= 's';
    }
    $sql .= " ORDER BY id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $vistos = [];
    while ($u = $res->fetch_assoc()) {
        $usuarios[] = $u;
        $vistos[(int) $u['id']] = true;
    }
    $stmt->close();
    $faltan = array_filter($opt['ids'], fn($id) => !isset($vistos[$id]));
    if ($faltan) {
        echo "Aviso: IDs no encontrados" . ($opt['rol'] ? " (con rol {$opt['rol']})" : "") . ": " . implode(',', $faltan) . PHP_EOL;
    }
}

if (!$usuarios) {
    fwrite(STDERR, "Ningún usuario coincide con la selección.\n");
    exit(1);
}

// ---- claves a asignar ----
$claves = [];
foreach ($usuarios as $u) {
    $claves[$u['id']] = $opt['password'] ?? claveAleatoria();
}

$preview = [];
foreach ($usuarios as $u) {
    $preview[] = ['id' => $u['id'], 'username' => $u['username'], 'rol' => $u['rol'], 'nueva_password' => $claves[$u['id']]];
}
tabla($preview, ['id', 'username', 'rol', 'nueva_password']);
echo count($preview) . " usuario(s) a actualizar" . ($opt['dry'] ? " (dry-run, no se guarda)" : "") . ".\n";

if ($opt['dry']) exit(0);

if (!$opt['yes']) {
    fwrite(STDOUT, "¿Continuar? [s/N]: ");
    $r = strtolower(trim(fgets(STDIN) ?: ''));
    if (!in_array($r, ['s', 'si', 'sí', 'y', 'yes'], true)) {
        echo "Cancelado.\n";
        exit(0);
    }
}

// ---- update ----
$ok = 0;
$upd = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
foreach ($usuarios as $u) {
    $hash = password_hash($claves[$u['id']], PASSWORD_DEFAULT);
    $id = (int) $u['id'];
    $upd->bind_param('si', $hash, $id);
    if ($upd->execute() && $upd->affected_rows >= 0) {
        // verificación real contra lo guardado
        $chk = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($row && password_verify($claves[$u['id']], $row['password'])) {
            echo "OK  id={$u['id']} {$u['username']}\n";
            $ok++;
        } else {
            fwrite(STDERR, "FAIL id={$u['id']} (no verifica hash)\n");
        }
    } else {
        fwrite(STDERR, "FAIL id={$u['id']} ({$upd->error})\n");
    }
}
$upd->close();

echo "Listo: $ok/" . count($usuarios) . " actualizados.\n";
exit($ok === count($usuarios) ? 0 : 1);
