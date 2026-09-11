<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\FactorImpuestoResolverInterface;

class FactorImpuestoResolver implements FactorImpuestoResolverInterface
{
    private const TASA = 'Tasa';
    private const CUOTA = 'Cuota';
    private const EXENTO = 'Exento';

    public function resolverImporte(
        string $tipoFactor,
        float $base,
        ?float $tasaOCuota,
        float $cantidadConcepto,
    ): ?float {
        return match ($tipoFactor) {
            self::EXENTO => null,
            self::TASA => $this->redondear($base * ($tasaOCuota ?? 0.0)),
            self::CUOTA => $this->redondear($cantidadConcepto * ($tasaOCuota ?? 0.0)),
            default => throw new \InvalidArgumentException("TipoFactor no reconocido: {$tipoFactor}"),
        };
    }

    private function redondear(float $valor): float
    {
        return round($valor, 2, PHP_ROUND_HALF_UP);
    }
}