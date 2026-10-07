<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogos;

/** Catálogo c_Exportacion (CFDI 4.0). */
enum Exportacion: string
{
    case NoAplica = '01';
    case DefinitivaConClaveA1 = '02';
    case DefinitivaConClaveDistintaDeA1 = '03';
    case Temporal = '04';

    public function descripcion(): string
    {
        return match ($this) {
            self::NoAplica => 'No aplica',
            self::DefinitivaConClaveA1 => 'Definitiva con clave de pedimento A1',
            self::DefinitivaConClaveDistintaDeA1 => 'Definitiva con clave de pedimento distinta a A1',
            self::Temporal => 'Temporal',
        };
    }
}