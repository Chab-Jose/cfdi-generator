<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

/** Catálogo c_ObjetoImp (CFDI 4.0). */
enum ObjetoImp: string
{
    case NoObjetoDeImpuesto = '01';
    case SiObjetoDeImpuesto = '02';
    case SiObjetoDeImpuestoYNoObligadoAlDesglose = '03';
    case SiObjetoDeImpuestoYNoCausaImpuesto = '04';

    public function descripcion(): string
    {
        return match ($this) {
            self::NoObjetoDeImpuesto => 'No objeto de impuesto',
            self::SiObjetoDeImpuesto => 'Sí objeto de impuesto',
            self::SiObjetoDeImpuestoYNoObligadoAlDesglose => 'Sí objeto de impuesto y no obligado al desglose',
            self::SiObjetoDeImpuestoYNoCausaImpuesto => 'Sí objeto del impuesto y no causa impuesto',
        };
    }
}