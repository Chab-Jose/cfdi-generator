<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;

interface PagoImpuestosCalculatorInterface
{
    /**
     * Agrupa los ImpuestosDR de todos los DoctoRelacionado de UN Pago
     * en su nodo ImpuestosP (Traslados agrupados por Impuesto+TipoFactor+TasaOCuota,
     * Retenciones agrupadas solo por Impuesto).
     */
    public function calcular(PagosPago $pago): PagosPago;
}