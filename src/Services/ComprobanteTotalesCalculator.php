<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\TotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

class ComprobanteTotalesCalculator implements TotalesCalculatorInterface
{
    public function calcular(Comprobante $comprobante): Comprobante
    {
        $subtotal = $this->calcularSubtotal($comprobante->Conceptos);
        $descuento = $this->calcularDescuento($comprobante->Conceptos);

        $impuestosTrasladados = $comprobante->Impuestos->TotalImpuestosTrasladados ?? 0.0;
        $impuestosRetenidos = $comprobante->Impuestos->TotalImpuestosRetenidos ?? 0.0;

        $comprobante->SubTotal = $subtotal;
        $comprobante->Descuento = $descuento;
        $comprobante->Total = round(
            $subtotal - ($descuento ?? 0.0) + $impuestosTrasladados - $impuestosRetenidos,
            2,
            PHP_ROUND_HALF_UP
        );

        return $comprobante;
    }

    private function calcularSubtotal(array $conceptos): float
    {
        return round(
            array_sum(array_map(fn(ComprobanteConcepto $c) => $c->Importe, $conceptos)),
            2,
            PHP_ROUND_HALF_UP
        );
    }

    private function calcularDescuento(array $conceptos): ?float
    {
        $descuentos = array_filter(
            array_map(fn(ComprobanteConcepto $c) => $c->Descuento, $conceptos),
            fn($d) => $d !== null
        );

        return empty($descuentos) ? null : round(array_sum($descuentos), 2, PHP_ROUND_HALF_UP);
    }
}