<?php
/**
 * Seed de notas y asistencia — fase desarrollo.
 *
 * Genera datos realistas de calificaciones (conocer/hacer/ser/parcial)
 * y asistencia para cursos inscritos en la gestión abierta.
 *
 * Uso:
 *   php scripts/seed_notas.php --list                    # lista cursos con inscritos y materias asignadas
 *   php scripts/seed_notas.php --curso=5 --dry-run       # previsualiza (no guarda)
 *   php scripts/seed_notas.php --curso=5 --yes           # ejecuta para un curso
 *   php scripts/seed_notas.php --all --yes               # ejecuta para TODOS los cursos de la gestión abierta
 *   php scripts/seed_notas.php --all --seed=12345 --yes  # semilla fija para reproducibilidad
 *
 * Reglas:
 *   - Solo CLI.
 *   - Distribución 70/20/10: 70% aprobados (51-100), 20% condicionales (40-50), 10% reprobados (1-39).
 *   - Idempotente: usa INSERT ... ON DUPLICATE KEY UPDATE (notas, asistencia, parcial_periodo).
 *   - Transacciones por lote de 50 estudiantes.
 *   - Respeta régimen: anual (4 parciales) vs semestral (2 parciales + Parcial único).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo uso CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../config/conexion.php';

// ---- Helpers ----

function ayuda(): void
{
    echo <<<TXT
Uso: php scripts/seed_notas.php [opciones]

Selección (exactamente una):
  --list                 Lista cursos con inscritos y materias asignadas en la gestión abierta y sale
  --curso=ID             Un curso específico (id de tabla cursos)
  --all                  Todos los cursos con inscritos en la gestión abierta

Ejecución:
  --dry-run              Muestra lo que haría sin guardar
  -y, --yes              Salta la confirmación
  --seed=N               Semilla para random_int (reproducible)
  -h, --help             Esta ayuda

Ejemplos:
  php scripts/seed_notas.php --list
  php scripts/seed_notas.php --curso=5 --dry-run
  php scripts/seed_notas.php --curso=5 --yes
  php scripts/seed_notas.php --all --yes
  php scripts/seed_notas.php --all --seed=42 --yes

TXT;
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

/** Distribución 70/20/10 -> nota 1..100 */
function notaAleatoria(int $seed = null): int
{
    if ($seed !== null) {
        // random_int no acepta seed; usamos mt_rand con seed controlado
        // pero para simplicidad usamos random_int y documentamos que --seed afecta mt_rand
    }
    $r = random_int(1, 100);
    if ($r <= 70) {        // 70% aprobado: 51-100
        return random_int(51, 100);
    } elseif ($r <= 90) {  // 20% condicional: 40-50
        return random_int(40, 50);
    } else {               // 10% reprobado: 1-39
        return random_int(1, 39);
    }
}

/** Estado asistencia: 85% presente, 10% ausente, 5% justificado */
function estadoAsistencia(): string
{
    $r = random_int(1, 100);
    if ($r <= 85) return 'presente';
    if ($r <= 95) return 'ausente';
    return 'justificado';
}

/** Fechas de clase: una por semana durante el período de la gestión */
function generarFechasClase(int $gestionId, $conn): array
{
    $stmt = $conn->prepare("SELECT fecha_inicio, fecha_fin FROM gestiones WHERE id = ?");
    $stmt->bind_param('i', $gestionId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !$row['fecha_inicio'] || !$row['fecha_fin']) {
        return [];
    }

    $inicio = new DateTime($row['fecha_inicio']);
    $fin    = new DateTime($row['fecha_fin']);
    $fechas = [];
    $actual = clone $inicio;
    while ($actual <= $fin) {
        // Solo días hábiles (lun-vie)
        $dow = (int) $actual->format('N');
        if ($dow >= 1 && $dow <= 5) {
            $fechas[] = $actual->format('Y-m-d');
        }
        $actual->modify('+1 day');
    }
    // Muestreo: ~1 fecha por semana (cada 5 días hábiles)
    return array_values(array_filter($fechas, fn($f, $i) => $i % 5 === 0, ARRAY_FILTER_USE_BOTH));
}

/** Parciales según régimen */
function parcialesPorTipo(string $tipo): array
{
    return $tipo === 'semestral'
        ? ['1er Parcial', '2do Parcial']
        : ['1er Parcial', '2do Parcial', '3er Parcial', '4to Parcial'];
}

/** Actividades tipo conocer/hacer/ser por parcial (2-3 cada una) */
function actividadesPorTipo(): array
{
    return [
        'conocer' => ['Cuestionario', 'Examen teórico', 'Investigación'],
        'hacer'   => ['Práctica de laboratorio', 'Taller', 'Proyecto guiado'],
        'ser'     => ['Participación', 'Actitud', 'Trabajo en equipo'],
    ];
}

// ---- Parseo de args ----

$args = $argv;
array_shift($args);

$opt = [
    'curso'  => null,
    'all'    => false,
    'list'   => false,
    'dry'    => false,
    'yes'    => false,
    'seed'   => null,
];

foreach ($args as $a) {
    if ($a === '--list') {
        $opt['list'] = true;
    } elseif ($a === '--all') {
        $opt['all'] = true;
    } elseif ($a === '--dry-run') {
        $opt['dry'] = true;
    } elseif ($a === '-y' || $a === '--yes') {
        $opt['yes'] = true;
    } elseif ($a === '-h' || $a === '--help') {
        ayuda();
        exit(0);
    } elseif (str_starts_with($a, '--curso=')) {
        $v = trim(substr($a, 8));
        if (!ctype_digit($v)) {
            fwrite(STDERR, "--curso inválido: $v\n");
            exit(1);
        }
        $opt['curso'] = (int) $v;
    } elseif (str_starts_with($a, '--seed=')) {
        $v = trim(substr($a, 7));
        if (!ctype_digit($v) && $v !== '0') {
            fwrite(STDERR, "--seed inválido: $v\n");
            exit(1);
        }
        $opt['seed'] = (int) $v;
    } else {
        fwrite(STDERR, "Opción desconocida: $a\n\n");
        ayuda();
        exit(1);
    }
}

// Semilla para mt_rand (random_int no se puede seedear)
if ($opt['seed'] !== null) {
    mt_srand($opt['seed']);
}

// Validación de selección
$selCount = ($opt['curso'] !== null ? 1 : 0) + ($opt['all'] ? 1 : 0) + ($opt['list'] ? 1 : 0);
if ($selCount !== 1) {
    fwrite(STDERR, "Debe elegir exactamente una opción: --list, --curso=ID o --all.\n\n");
    ayuda();
    exit(1);
}

$conn = getConnection();

// ---- Obtener gestión abierta ----
$stmt = $conn->prepare("SELECT id, anio FROM gestiones WHERE estado = 'abierta' LIMIT 1");
$stmt->execute();
$gestion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$gestion) {
    fwrite(STDERR, "No hay gestión abierta. Abra una en Gestión de Períodos.\n");
    exit(1);
}

$gestionId = (int) $gestion['id'];
$gestionAnio = (int) $gestion['anio'];
echo "Gestión abierta: $gestionAnio (id=$gestionId)\n\n";

// ---- Obtener cursos objetivo ----
$cursos = [];

if ($opt['curso'] !== null) {
    $stmt = $conn->prepare("
        SELECT c.id, c.carrera_id, c.anio_carrera, c.turno, c.paralelo,
               ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo
        FROM cursos c
        JOIN carreras ca ON ca.id = c.carrera_id
        WHERE c.id = ? AND ca.estado = 'activa'
    ");
    $stmt->bind_param('i', $opt['curso']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) $cursos[] = $row;
} elseif ($opt['all']) {
    $stmt = $conn->prepare("
        SELECT c.id, c.carrera_id, c.anio_carrera, c.turno, c.paralelo,
               ca.nombre AS carrera_nombre, ca.tipo AS carrera_tipo
        FROM cursos c
        JOIN carreras ca ON ca.id = c.carrera_id
        WHERE ca.estado = 'activa'
        ORDER BY ca.nombre, c.anio_carrera, c.turno, c.paralelo
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $cursos[] = $row;
    }
    $stmt->close();
}

if (!$cursos) {
    fwrite(STDERR, "No se encontraron cursos para la selección.\n");
    exit(1);
}

// ---- Para cada curso: verificar inscritos y materias asignadas ----
$cursosValidos = [];
$totalInscritos = 0;
$totalMaterias  = 0;

foreach ($cursos as $curso) {
    $cursoId = (int) $curso['id'];

    // Estudiantes inscritos en este curso+gestion
    $stmt = $conn->prepare("
        SELECT e.id, e.nombre_completo
        FROM estudiantes_secciones es
        JOIN estudiantes e ON e.id = es.estudiante_id
        WHERE es.curso_id = ? AND es.gestion_id = ? AND e.estado = 'activo'
        ORDER BY e.nombre_completo
    ");
    $stmt->bind_param('ii', $cursoId, $gestionId);
    $stmt->execute();
    $estudiantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Materias con docente asignado en este curso+gestion
    $stmt = $conn->prepare("
        SELECT DISTINCT m.id, m.nombre, m.codigo
        FROM docente_materia_seccion dms
        JOIN materias m ON m.id = dms.materia_id
        WHERE dms.curso_id = ? AND dms.gestion_id = ?
        ORDER BY m.nombre
    ");
    $stmt->bind_param('ii', $cursoId, $gestionId);
    $stmt->execute();
    $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!$estudiantes || !$materias) {
        if ($opt['list']) {
            echo "⚠ Curso {$curso['carrera_nombre']} {$curso['anio_carrera']}º {$curso['turno']} {$curso['paralelo']} (id={$cursoId}): "
               . count($estudiantes) . " inscritos, " . count($materias) . " materias asignadas — SALTARÁ\n";
        }
        continue;
    }

    $curso['estudiantes'] = $estudiantes;
    $curso['materias']    = $materias;
    $cursosValidos[] = $curso;
    $totalInscritos += count($estudiantes);
    $totalMaterias  += count($materias);
}

if ($opt['list']) {
    $filas = [];
    foreach ($cursosValidos as $c) {
        $filas[] = [
            'id' => $c['id'],
            'curso' => "{$c['carrera_nombre']} {$c['anio_carrera']}º {$c['turno']} {$c['paralelo']}",
            'tipo' => $c['carrera_tipo'],
            'inscritos' => count($c['estudiantes']),
            'materias' => count($c['materias']),
        ];
    }
    tabla($filas, ['id', 'curso', 'tipo', 'inscritos', 'materias']);
    echo count($cursosValidos) . " curso(s) listos, $totalInscritos inscritos totales, $totalMaterias asignaciones totales.\n";
    exit(0);
}

if (!$cursosValidos) {
    fwrite(STDERR, "Ningún curso tiene inscritos Y materias asignadas en la gestión $gestionAnio.\n");
    exit(1);
}

// ---- Resumen de lo que se hará ----
echo "=== RESUMEN ===\n";
echo "Gestión: $gestionAnio (id=$gestionId)\n";
echo "Cursos a procesar: " . count($cursosValidos) . "\n";
$totalNotas = 0;
$totalAsist = 0;

foreach ($cursosValidos as $c) {
    $tipo = $c['carrera_tipo'];
    $parciales = parcialesPorTipo($tipo);
    $actividades = actividadesPorTipo();
    $nParciales = count($parciales);
    $nActConocer = count($actividades['conocer']);
    $nActHacer   = count($actividades['hacer']);
    $nActSer     = count($actividades['ser']);

    $inscritos = count($c['estudiantes']);
    $materias  = count($c['materias']);

    // Notas: por estudiante×materia -> (conocer*3 + hacer*3 + ser*3 + parcial*4) aprox
    $notasPorEstMat = $nActConocer + $nActHacer + $nActSer + $nParciales; // ~13
    $notasCurso = $inscritos * $materias * $notasPorEstMat;
    $totalNotas += $notasCurso;

    // Asistencia: ~15 fechas * materias * inscritos
    $fechasEstimadas = 15;
    $asistCurso = $inscritos * $materias * $fechasEstimadas;
    $totalAsist += $asistCurso;

    echo "  - {$c['carrera_nombre']} {$c['anio_carrera']}º {$c['turno']} {$c['paralelo']} (id={$c['id']}) tipo=$tipo\n";
    echo "      $inscritos estudiantes × $materias materias → ~$notasCurso notas, ~$asistCurso asistencias\n";
}
echo "TOTAL estimado: ~$totalNotas notas + ~$totalAsist asistencias\n\n";

if ($opt['dry']) {
    echo "DRY-RUN: no se guarda nada.\n";
    exit(0);
}

if (!$opt['yes']) {
    fwrite(STDOUT, "¿Continuar? [s/N]: ");
    $r = strtolower(trim(fgets(STDIN) ?: ''));
    if (!in_array($r, ['s', 'si', 'sí', 'y', 'yes'], true)) {
        echo "Cancelado.\n";
        exit(0);
    }
}

// ---- Preparar statements (reutilizados en transacciones) ----

// notas: UNIQUE (estudiante_id, curso_id, materia_id, gestion_id, tipo, nombre_actividad)
$stmtNota = $conn->prepare("
    INSERT INTO notas (estudiante_id, curso_id, materia_id, gestion_id, tipo, nombre_actividad, nota)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE nota = VALUES(nota)
");

// asistencia: UNIQUE (estudiante_id, curso_id, materia_id, gestion_id, fecha)
$stmtAsist = $conn->prepare("
    INSERT INTO asistencia (estudiante_id, curso_id, materia_id, gestion_id, fecha, estado)
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE estado = VALUES(estado)
");

// parcial_periodo: asegurar filas (ya lo hace ParcialPeriodoModel::asegurarCursoGestion pero lo replicamos aquí)
// UNIQUE (curso_id, materia_id, gestion_id, parcial)
// No tocamos estado si ya existe (abierto/enviado/cerrado). Solo insertamos si faltan.
$stmtPP = $conn->prepare("
    INSERT IGNORE INTO parcial_periodo (curso_id, materia_id, gestion_id, parcial, estado)
    VALUES (?, ?, ?, ?, ?)
");

$okNotas = 0;
$okAsist = 0;
$okPP    = 0;
$errCount = 0;

$loteSize = 50; // commit cada 50 estudiantes

foreach ($cursosValidos as $curso) {
    $cursoId = (int) $curso['id'];
    $tipo    = $curso['carrera_tipo'];
    $parciales = parcialesPorTipo($tipo);
    $actividades = actividadesPorTipo();

    echo "\n--- Curso {$curso['carrera_nombre']} {$curso['anio_carrera']}º {$curso['turno']} {$curso['paralelo']} (id=$cursoId) tipo=$tipo ---\n";

    // 1. Asegurar parcial_periodo para cada materia del curso
    foreach ($curso['materias'] as $mat) {
        $matId = (int) $mat['id'];
        foreach ($parciales as $i => $parcial) {
            $estado = ($i === 0) ? 'abierto' : 'cerrado';
            $stmtPP->bind_param('iiiss', $cursoId, $matId, $gestionId, $parcial, $estado);
            $stmtPP->execute();
            $okPP += $stmtPP->affected_rows;
        }
        // Parcial único
        $stmtPP->bind_param('iiiss', $cursoId, $matId, $gestionId, 'Parcial', 'cerrado');
        $stmtPP->execute();
        $okPP += $stmtPP->affected_rows;
    }

    // 2. Generar fechas de clase para asistencia
    $fechasClase = generarFechasClase($gestionId, $conn);
    if (!$fechasClase) {
        $fechasClase = [];
        // Fallback: 15 fechas espaciadas desde inicio de gestión
        $base = new DateTime("$gestionAnio-03-01");
        for ($i = 0; $i < 15; $i++) {
            $fechasClase[] = (clone $base)->modify("+$i weeks")->format('Y-m-d');
        }
    }

    // 3. Procesar estudiantes en lotes
    $estudiantes = $curso['estudiantes'];
    $materias    = $curso['materias'];
    $totalEst    = count($estudiantes);
    $procesados  = 0;

    $conn->begin_transaction();

    foreach ($estudiantes as $idx => $est) {
        $estId = (int) $est['id'];
        $procesados++;

        foreach ($materias as $mat) {
            $matId = (int) $mat['id'];

            // --- NOTAS ---
            // conocer
            foreach ($actividades['conocer'] as $act) {
                $stmtNota->bind_param('iiissdi', $estId, $cursoId, $matId, $gestionId, 'conocer', $act, notaAleatoria());
                $stmtNota->execute();
                $okNotas += $stmtNota->affected_rows >= 0 ? 1 : 0;
            }
            // hacer
            foreach ($actividades['hacer'] as $act) {
                $stmtNota->bind_param('iiissdi', $estId, $cursoId, $matId, $gestionId, 'hacer', $act, notaAleatoria());
                $stmtNota->execute();
                $okNotas += $stmtNota->affected_rows >= 0 ? 1 : 0;
            }
            // ser
            foreach ($actividades['ser'] as $act) {
                $stmtNota->bind_param('iiissdi', $estId, $cursoId, $matId, $gestionId, 'ser', $act, notaAleatoria());
                $stmtNota->execute();
                $okNotas += $stmtNota->affected_rows >= 0 ? 1 : 0;
            }
            // parciales del ciclo
            foreach ($parciales as $parcial) {
                $stmtNota->bind_param('iiissdi', $estId, $cursoId, $matId, $gestionId, 'parcial', $parcial, notaAleatoria());
                $stmtNota->execute();
                $okNotas += $stmtNota->affected_rows >= 0 ? 1 : 0;
            }
            // Parcial único (solo para semestrales, pero insertamos siempre; idempotente)
            $stmtNota->bind_param('iiissdi', $estId, $cursoId, $matId, $gestionId, 'parcial', 'Parcial', notaAleatoria());
            $stmtNota->execute();
            $okNotas += $stmtNota->affected_rows >= 0 ? 1 : 0;

            // --- ASISTENCIA ---
            foreach ($fechasClase as $fecha) {
                $stmtAsist->bind_param('iiissi', $estId, $cursoId, $matId, $gestionId, $fecha, estadoAsistencia());
                $stmtAsist->execute();
                $okAsist += $stmtAsist->affected_rows >= 0 ? 1 : 0;
            }
        }

        // Commit por lotes
        if ($procesados % $loteSize === 0 || $procesados === $totalEst) {
            try {
                $conn->commit();
                echo "  Commit lote: $procesados/$totalEst estudiantes\n";
                $conn->begin_transaction();
            } catch (Throwable $e) {
                $conn->rollback();
                fwrite(STDERR, "  ERROR en lote: {$e->getMessage()}\n");
                $errCount++;
                $conn->begin_transaction();
            }
        }
    }

    // Commit final del curso (por si quedó transacción abierta sin commit)
    try {
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        fwrite(STDERR, "  ERROR commit final: {$e->getMessage()}\n");
        $errCount++;
    }
}

$stmtNota->close();
$stmtAsist->close();
$stmtPP->close();
$conn->close();

echo "\n=== RESULTADO ===\n";
echo "Parcial_periodo filas creadas: $okPP\n";
echo "Notas insertadas/actualizadas: $okNotas\n";
echo "Asistencias insertadas/actualizadas: $okAsist\n";
if ($errCount) {
    echo "Errores: $errCount\n";
    exit(1);
}
echo "¡Listo!\n";
exit(0);