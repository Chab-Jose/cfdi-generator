<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\ConceptoCalculatorInterface;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

class ConceptoCalculator implements ConceptoCalculatorInterface
{
    public function calcularImporte(ComprobanteConcepto $concepto, int $decimales = 2, bool $forzar = false): float
    {
        if (!$forzar && $concepto->Importe !== 0.0) {
            // El usuario ya definió un Importe manualmente; se respeta.
            return $concepto->Importe;
        }

        $importe = round($concepto->ValorUnitario * $concepto->Cantidad, $decimales, PHP_ROUND_HALF_UP);
        $concepto->Importe = $importe;

        return $importe;
    }
}