<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Comprobante;

interface ComprobanteBuilderInterface
{
    /**
     * Completa cálculos derivados (Importes, SubTotal, Total) y
     * valida que los campos requeridos por el SAT estén presentes.
     *
     * @throws \ChabJose\CfdiGenerator\Exceptions\ComprobanteValidationException
     */
    public function build(Comprobante $comprobante): Comprobante;
}