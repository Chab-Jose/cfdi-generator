<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

/** Catálogo c_TipoFactor (CFDI 4.0). */
enum TipoFactor: string
{
    case Tasa = 'Tasa';
    case Cuota = 'Cuota';
    case Exento = 'Exento';
}