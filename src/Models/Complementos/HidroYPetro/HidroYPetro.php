<?php

declare(strict_types=1);

namespace App\Jose\CfdiGenerator\Models\Complementos\HidroYPetro;

use ChabJose\CfdiGenerator\Catalogs\ClaveHYP;
use ChabJose\CfdiGenerator\Catalogs\SubProductoHYP;
use ChabJose\CfdiGenerator\Catalogs\TipoPermiso;

final class HidroYPetro
{
    public string $Version = '1.0';
    public string|TipoPermiso $TipoPermiso;
    public string $NumeroPermiso;
    public string|ClaveHYP $ClaveHYP;
    public string|SubProductoHYP $SubProductoHYP;
}