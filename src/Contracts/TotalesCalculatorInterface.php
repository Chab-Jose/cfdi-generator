<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Comprobante;

interface TotalesCalculatorInterface
{
    /**
     * Calcula SubTotal, Descuento y Total del comprobante
     * a partir de sus Conceptos e Impuestos.
     */
    public function calcular(Comprobante $comprobante): Comprobante;
}