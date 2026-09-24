<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/NotasModel.php';
require_once __DIR__ . '/ParcialPeriodoModel.php';

final class ServicioNotas
{
    /**
     * Única fuente de verdad para el cálculo de TEORIA/PRACTICA/PARCIAL.
     * Replica la lógica de recalcFila: si hay nota directa de parcial, 30/70;
     * si no, suma Conocer/Hacer(+Ser) si existen Conocer 1 / Hacer 1.
     * @return array{teoria:?float,practica:?float,parcial:?float}
     */
    public static function recalcFila(int $estId, int $cursoId, int $materiaId, int $gestion, string $carreraTipo): array
    {
        $notasModel = new NotasModel();
        $parcialModel = new ParcialPeriodoModel();
        $allNotas = $notasModel->getNotasEstudiante($estId, $cursoId, $materiaId);

        $conocerSum = 0.0; $hacerSum = 0.0; $serSum = 0.0;
        $tieneConocer1 = false;
        $tieneHacer1 = false;
        foreach ($allNotas as $n) {
            if (($n['tipo'] ?? '') === 'conocer') {
                $conocerSum += (float) $n['nota'];
                if (($n['nombre_actividad'] ?? '') === 'Conocer 1') $tieneConocer1 = true;
            } elseif (($n['tipo'] ?? '') === 'hacer') {
                $hacerSum += (float) $n['nota'];
                if (($n['nombre_actividad'] ?? '') === 'Hacer 1') $tieneHacer1 = true;
            } elseif (($n['tipo'] ?? '') === 'ser') {
                $serSum += (float) $n['nota'];
            }
        }

        $activo = $parcialModel->parcialActivo($cursoId, $materiaId, $gestion, $carreraTipo);
        $label = $activo !== null ? $activo : 'Parcial';
        $parcialDirecto = null;
        foreach ($allNotas as $n) {
            if (($n['tipo'] ?? '') === 'parcial' && ($n['nombre_actividad'] ?? '') === $label) {
                $parcialDirecto = (float) $n['nota'];
                break;
            }
        }

        if ($parcialDirecto !== null) {
            $teoria  = round($parcialDirecto * 0.30, 1);
            $practica = round($parcialDirecto * 0.70, 1);
            $parcial = $parcialDirecto;
        } else {
            $teoria   = $tieneConocer1 ? round($conocerSum, 0) : null;
            $practica = $tieneHacer1   ? round($hacerSum + $serSum, 0) : null;
            $parcial  = ($teoria !== null && $practica !== null) ? round($teoria + $practica, 0) : null;
        }

        return [
            'teoria'  => $teoria !== null ? (float) $teoria : null,
            'practica' => $practica !== null ? (float) $practica : null,
            'parcial' => $parcial !== null ? (float) $parcial : null,
        ];
    }

    public static function fmtNota(?float $v): string
    {
        if ($v === null || $v === '') return '';
        return rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
    }

    public static function getNota(array $notas, string $tipo, string $nombreActividad): ?float
    {
        foreach ($notas as $n) {
            if (($n['tipo'] ?? '') === $tipo && ($n['nombre_actividad'] ?? '') === $nombreActividad) {
                return (float) $n['nota'];
            }
        }
        return null;
    }

    public static function notaCelda(array $notas, string $tipo, string $nombre): string
    {
        $v = self::getNota($notas, $tipo, $nombre);
        return $v !== null ? self::fmtNota($v) : '';
    }

    public static function semestreTexto(int $n): string
    {
        $map = [1 => 'PRIMER SEMESTRE', 2 => 'SEGUNDO SEMESTRE', 3 => 'TERCER SEMESTRE',
                4 => 'CUARTO SEMESTRE', 5 => 'QUINTO SEMESTRE', 6 => 'SEXTO SEMESTRE'];
        return $map[$n] ?? 'PRIMER SEMESTRE';
    }
}
