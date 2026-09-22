<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepciones;

interface PercepcionesCalculatorInterface
{
    public function calcular(NominaPercepciones $percepciones): NominaPercepciones;
}