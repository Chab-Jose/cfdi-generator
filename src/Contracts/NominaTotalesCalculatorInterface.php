<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;

interface NominaTotalesCalculatorInterface
{
    public function calcular(Nomina $nomina): Nomina;
}