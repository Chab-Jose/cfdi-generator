<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\ComprobanteImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Domain\ImpuestoAgrupacionKey;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteImpuestos;
use ChabJose\CfdiGenerator\Models\ComprobanteImpuestosRetencion;
use ChabJose\CfdiGenerator\Models\ComprobanteImpuestosTraslado;

class ComprobanteImpuestosCalculator implements ComprobanteImpuestosCalculatorInterface
{
    public function calcular(Comprobante $comprobante): Comprobante
    {
        $trasladosAgrupados = [];
        $retencionesAgrupadas = [];

        foreach ($comprobante->Conceptos as $concepto) {
            if ($concepto->Impuestos === null) {
                continue;
            }

            foreach ($concepto->Impuestos->Traslados as $traslado) {
                $key = ImpuestoAgrupacionKey::paraTraslado(
                    $traslado->Impuesto,
                    $traslado->TipoFactor,
                    $traslado->TasaOCuota,
                )->toString();

                if (!isset($trasladosAgrupados[$key])) {
                    $trasladosAgrupados[$key] = new ComprobanteImpuestosTraslado();
                    $trasladosAgrupados[$key]->Impuesto = $traslado->Impuesto;
                    $trasladosAgrupados[$key]->TipoFactor = $traslado->TipoFactor;
                    $trasladosAgrupados[$key]->TasaOCuota = $traslado->TasaOCuota;
                }

                $trasladosAgrupados[$key]->Base += $traslado->Base;

                if ($traslado->Importe !== null) {
                    $trasladosAgrupados[$key]->Importe = ($trasladosAgrupados[$key]->Importe ?? 0.0) + $traslado->Importe;
                }
            }

            foreach ($concepto->Impuestos->Retenciones as $retencion) {
                $key = ImpuestoAgrupacionKey::paraRetencion($retencion->Impuesto)->toString();

                if (!isset($retencionesAgrupadas[$key])) {
                    $retencionesAgrupadas[$key] = new ComprobanteImpuestosRetencion();
                    $retencionesAgrupadas[$key]->Impuesto = $retencion->Impuesto;
                }

                $retencionesAgrupadas[$key]->Importe += $retencion->Importe;
            }
        }

        $impuestos = new ComprobanteImpuestos();
        $impuestos->Traslados = array_map(
            fn(ComprobanteImpuestosTraslado $t) => $this->redondearTraslado($t),
            array_values($trasladosAgrupados)
        );
        $impuestos->Retenciones = array_map(
            fn(ComprobanteImpuestosRetencion $r) => $this->redondearRetencion($r),
            array_values($retencionesAgrupadas)
        );

        $impuestos->TotalImpuestosTrasladados = empty($impuestos->Traslados)
            ? null
            : round(array_sum(array_map(fn($t) => $t->Importe ?? 0.0, $impuestos->Traslados)), 2, PHP_ROUND_HALF_UP);

        $impuestos->TotalImpuestosRetenidos = empty($impuestos->Retenciones)
            ? null
            : round(array_sum(array_map(fn($r) => $r->Importe, $impuestos->Retenciones)), 2, PHP_ROUND_HALF_UP);

        $comprobante->Impuestos = (empty($impuestos->Traslados) && empty($impuestos->Retenciones))
            ? null
            : $impuestos;

        return $comprobante;
    }

    private function redondearTraslado(ComprobanteImpuestosTraslado $t): ComprobanteImpuestosTraslado
    {
        $t->Base = round($t->Base, 2, PHP_ROUND_HALF_UP);
        $t->Importe = $t->Importe !== null ? round($t->Importe, 2, PHP_ROUND_HALF_UP) : null;
        return $t;
    }

    private function redondearRetencion(ComprobanteImpuestosRetencion $r): ComprobanteImpuestosRetencion
    {
        $r->Importe = round($r->Importe, 2, PHP_ROUND_HALF_UP);
        return $r;
    }
}