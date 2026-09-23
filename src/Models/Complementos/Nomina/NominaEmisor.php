<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaEmisor
{
    public ?string $Curp = null;
    public ?string $RegistroPatronal = null;
    public ?string $RfcPatronOrigen = null;
    public ?NominaEmisorEntidadSNCF $EntidadSNCF = null;
}