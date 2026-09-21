<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaDeduccion
{
    public string $TipoDeduccion = '';
    public string $Clave = '';
    public string $Concepto = '';
    public float $Importe = 0.0;
}