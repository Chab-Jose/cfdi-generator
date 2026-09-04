<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\ConceptoImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\FactorImpuestoResolverInterface;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

class ConceptoImpuestosCalculator implements ConceptoImpuestosCalculatorInterface
{
    public function __construct(
        private FactorImpuestoResolverInterface $resolver,
    ) {
    }

    public function calcular(ComprobanteConcepto $concepto, bool $forzar = false): ComprobanteConcepto
    {
        if ($concepto->Impuestos === null) {
            return $concepto;
        }

        foreach ($concepto->Impuestos->Traslados as $traslado) {
            if ($forzar || $traslado->Importe === null) {
                $traslado->Importe = $this->resolver->resolverImporte(
                    $traslado->TipoFactor,
                    $traslado->Base,
                    $traslado->TasaOCuota,
                    $concepto->Cantidad,
                );
            }
        }

        foreach ($concepto->Impuestos->Retenciones as $retencion) {
            if ($forzar || $retencion->Importe === 0.0) {
                $retencion->Importe = $this->resolver->resolverImporte(
                    $retencion->TipoFactor,
                    $retencion->Base,
                    $retencion->TasaOCuota,
                    $concepto->Cantidad,
                ) ?? 0.0;
            }
        }

        return $concepto;
    }
}