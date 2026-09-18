<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;

interface PagosTotalesCalculatorInterface
{
    /**
     * Agrega todos los Pago[] al nodo Totales, convirtiendo a MXN
     * cualquier Pago en moneda extranjera usando su TipoCambioP.
     */
    public function calcular(Pagos $pagos): Pagos;
}