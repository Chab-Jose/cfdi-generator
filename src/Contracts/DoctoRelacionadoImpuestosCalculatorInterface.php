<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;


use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionado;

interface DoctoRelacionadoImpuestosCalculatorInterface
{
    /**
     * Calcula ImporteDR de cada TrasladoDR/RetencionDR según su TipoFactorDR,
     * a partir de BaseDR y TasaOCuotaDR.
     */
    public function calcular(PagosPagoDoctoRelacionado $docto, bool $forzar = false): PagosPagoDoctoRelacionado;
}