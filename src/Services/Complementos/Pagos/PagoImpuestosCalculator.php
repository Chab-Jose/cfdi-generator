<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Contracts\PagoImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Domain\ImpuestoAgrupacionKey;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoImpuestosP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoImpuestosPRetencionP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoImpuestosPTrasladoP;
use ChabJose\CfdiGenerator\Utils\Xml\NormalizaValorCatalogoTrait;

class PagoImpuestosCalculator implements PagoImpuestosCalculatorInterface
{
    use NormalizaValorCatalogoTrait;

    public function calcular(PagosPago $pago): PagosPago
    {
        $trasladosAgrupados = [];
        $retencionesAgrupadas = [];

        foreach ($pago->DoctoRelacionado as $docto) {
            if ($docto->ImpuestosDR === null) {
                continue;
            }

            foreach ($docto->ImpuestosDR->TrasladosDR as $traslado) {
                $impuesto = $this->valorEscalar($traslado->ImpuestoDR);
                $tipoFactor = $this->valorEscalar($traslado->TipoFactorDR);

                $key = ImpuestoAgrupacionKey::paraTraslado(
                    $impuesto,
                    $tipoFactor,
                    $traslado->TasaOCuotaDR,
                )->toString();

                if (!isset($trasladosAgrupados[$key])) {
                    $trasladosAgrupados[$key] = new PagosPagoImpuestosPTrasladoP();
                    $trasladosAgrupados[$key]->ImpuestoP = $impuesto;
                    $trasladosAgrupados[$key]->TipoFactorP = $tipoFactor;
                    $trasladosAgrupados[$key]->TasaOCuotaP = $traslado->TasaOCuotaDR;
                }

                $trasladosAgrupados[$key]->BaseP += $traslado->BaseDR;

                if ($traslado->ImporteDR !== null) {
                    $trasladosAgrupados[$key]->ImporteP = ($trasladosAgrupados[$key]->ImporteP ?? 0.0) + $traslado->ImporteDR;
                }
            }

            foreach ($docto->ImpuestosDR->RetencionesDR as $retencion) {
                $impuesto = $this->valorEscalar($retencion->ImpuestoDR);
                $key = ImpuestoAgrupacionKey::paraRetencion($impuesto)->toString();

                if (!isset($retencionesAgrupadas[$key])) {
                    $retencionesAgrupadas[$key] = new PagosPagoImpuestosPRetencionP();
                    $retencionesAgrupadas[$key]->ImpuestoP = $impuesto;
                }

                $retencionesAgrupadas[$key]->ImporteP += $retencion->ImporteDR;
            }
        }

        if (empty($trasladosAgrupados) && empty($retencionesAgrupadas)) {
            return $pago;
        }

        $impuestosP = new PagosPagoImpuestosP();
        $impuestosP->TrasladosP = array_map(
            fn(PagosPagoImpuestosPTrasladoP $t) => $this->redondearTraslado($t),
            array_values($trasladosAgrupados)
        );
        $impuestosP->RetencionesP = array_map(
            fn(PagosPagoImpuestosPRetencionP $r) => $this->redondearRetencion($r),
            array_values($retencionesAgrupadas)
        );

        $pago->ImpuestosP = $impuestosP;

        return $pago;
    }

    private function redondearTraslado(PagosPagoImpuestosPTrasladoP $t): PagosPagoImpuestosPTrasladoP
    {
        $t->BaseP = round($t->BaseP, 2, PHP_ROUND_HALF_UP);
        $t->ImporteP = $t->ImporteP !== null ? round($t->ImporteP, 2, PHP_ROUND_HALF_UP) : null;
        return $t;
    }

    private function redondearRetencion(PagosPagoImpuestosPRetencionP $r): PagosPagoImpuestosPRetencionP
    {
        $r->ImporteP = round($r->ImporteP, 2, PHP_ROUND_HALF_UP);
        return $r;
    }
}