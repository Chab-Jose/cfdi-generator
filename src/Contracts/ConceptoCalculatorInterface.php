<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

interface ConceptoImpuestosCalculatorInterface
{
    /**
     * Calcula el Importe de cada Traslado y Retención del concepto,
     * según su TipoFactor (Tasa, Cuota, Exento), a partir de Base y TasaOCuota.
     * No sobreescribe un Importe ya definido manualmente, salvo que $forzar sea true.
     */
    public function calcular(ComprobanteConcepto $concepto, bool $forzar = false): ComprobanteConcepto;
}