<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

interface ConceptoCalculatorInterface
{
    /**
     * Calcula el Importe de cada Traslado y Retención del concepto,
     * según su TipoFactor (Tasa, Cuota, Exento), a partir de Base y TasaOCuota.
     * No sobreescribe un Importe ya definido manualmente, salvo que $forzar sea true.
     */
    public function calcularImporte(ComprobanteConcepto $concepto, int $decimales = 2, bool $forzar = false): float;
}