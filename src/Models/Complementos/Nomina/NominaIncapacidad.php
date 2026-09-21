<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaIncapacidad
{
    public int $DiasIncapacidad = 0;
    public string $TipoIncapacidad = '';
    public ?float $ImporteMonetario = null;
}