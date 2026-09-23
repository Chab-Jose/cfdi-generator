<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeducciones;

interface DeduccionesCalculatorInterface
{
    public function calcular(NominaDeducciones $deducciones): NominaDeducciones;
}