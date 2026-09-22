<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Contracts\DeduccionesCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\NominaBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\NominaTotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\PercepcionesCalculatorInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;

class NominaBuilder implements NominaBuilderInterface
{
    public function __construct(
        private PercepcionesCalculatorInterface $percepcionesCalculator,
        private DeduccionesCalculatorInterface $deduccionesCalculator,
        private NominaTotalesCalculatorInterface $totalesCalculator,
    ) {
    }

    public function build(Nomina $nomina): Nomina
    {
        if ($nomina->Percepciones !== null) {
            $this->percepcionesCalculator->calcular($nomina->Percepciones);
        }

        if ($nomina->Deducciones !== null) {
            $this->deduccionesCalculator->calcular($nomina->Deducciones);
        }

        $this->totalesCalculator->calcular($nomina);

        return $nomina;
    }
}