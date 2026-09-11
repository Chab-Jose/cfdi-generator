<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

interface FactorImpuestoResolverInterface
{
    /**
     * Resuelve el Importe de un impuesto según su TipoFactor.
     * Regresa null cuando TipoFactor es "Exento" (no aplica Importe).
     */
    public function resolverImporte(
        string $tipoFactor,
        float $base,
        ?float $tasaOCuota,
        float $cantidadConcepto,
    ): ?float;
}