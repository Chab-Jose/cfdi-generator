<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Contracts\DoctoRelacionadoImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\FactorImpuestoResolverInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionado;

class DoctoRelacionadoImpuestosCalculator implements DoctoRelacionadoImpuestosCalculatorInterface
{
    public function __construct(
        private FactorImpuestoResolverInterface $resolver,
    ) {
    }

    public function calcular(PagosPagoDoctoRelacionado $docto, bool $forzar = false): PagosPagoDoctoRelacionado
    {
        if ($docto->ImpuestosDR === null) {
            return $docto;
        }

        foreach ($docto->ImpuestosDR->TrasladosDR as $traslado) {
            if ($forzar || $traslado->ImporteDR === null) {
                // Nota: "Cuota" no es un caso típico en el contexto de Pagos
                // (los impuestos aquí derivan de una factura ya calculada),
                // pero se soporta pasando BaseDR como referencia si ocurriera.
                $traslado->ImporteDR = $this->resolver->resolverImporte(
                    $traslado->TipoFactorDR,
                    $traslado->BaseDR,
                    $traslado->TasaOCuotaDR,
                    cantidadConcepto: $traslado->BaseDR,
                );
            }
        }

        foreach ($docto->ImpuestosDR->RetencionesDR as $retencion) {
            if ($forzar || $retencion->ImporteDR === 0.0) {
                $retencion->ImporteDR = $this->resolver->resolverImporte(
                    $retencion->TipoFactorDR,
                    $retencion->BaseDR,
                    $retencion->TasaOCuotaDR,
                    cantidadConcepto: $retencion->BaseDR,
                ) ?? 0.0;
            }
        }

        return $docto;
    }
}