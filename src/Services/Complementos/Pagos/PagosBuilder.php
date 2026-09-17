<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Contracts\DoctoRelacionadoImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\PagoImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\PagosBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\PagosTotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;

class PagosBuilder implements PagosBuilderInterface
{
    public function __construct(
        private DoctoRelacionadoImpuestosCalculatorInterface $doctoRelacionadoImpuestosCalculator,
        private PagoImpuestosCalculatorInterface $pagoImpuestosCalculator,
        private PagosTotalesCalculatorInterface $pagosTotalesCalculator,
    ) {
    }

    public function build(Pagos $pagos): Pagos
    {
        foreach ($pagos->Pago as $pago) {
            foreach ($pago->DoctoRelacionado as $docto) {
                $this->doctoRelacionadoImpuestosCalculator->calcular($docto);
            }

            $this->pagoImpuestosCalculator->calcular($pago);
        }

        $this->pagosTotalesCalculator->calcular($pagos);

        return $pagos;
    }
}