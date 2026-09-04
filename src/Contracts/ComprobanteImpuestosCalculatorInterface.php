<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Comprobante;

interface ComprobanteImpuestosCalculatorInterface
{
    /**
     * Agrupa los Traslados/Retenciones de todos los Conceptos por
     * (Impuesto, TipoFactor, TasaOCuota), suma sus Base e Importe,
     * y calcula TotalImpuestosTrasladados / TotalImpuestosRetenidos
     * en el nodo cfdi:Impuestos del Comprobante.
     */
    public function calcular(Comprobante $comprobante): Comprobante;
}