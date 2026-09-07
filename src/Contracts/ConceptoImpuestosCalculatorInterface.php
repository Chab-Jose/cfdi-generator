<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

interface ConceptoImpuestosCalculatorInterface
{
    /**
     * Calcula el Importe de un concepto (ValorUnitario * Cantidad).
     * Si $forzar es false, no sobreescribe un Importe ya definido manualmente.
     */    
    public function calcular(ComprobanteConcepto $concepto, bool $forzar = false): ComprobanteConcepto;
}