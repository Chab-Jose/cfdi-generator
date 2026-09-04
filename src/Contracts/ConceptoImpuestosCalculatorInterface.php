<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

interface ConceptoCalculatorInterface
{
    /**
     * Calcula el Importe de un concepto (ValorUnitario * Cantidad).
     * Si $forzar es false, no sobreescribe un Importe ya definido manualmente.
     */
    public function calcularImporte(ComprobanteConcepto $concepto, int $decimales = 2, bool $forzar = false): float;
}