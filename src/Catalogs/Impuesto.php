<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

/** Catálogo c_Impuesto (CFDI 4.0). */
enum Impuesto: string
{
    case ISR = '001';
    case IVA = '002';
    case IEPS = '003';

    public function descripcion(): string
    {
        return match ($this) {
            self::ISR => 'ISR',
            self::IVA => 'IVA',
            self::IEPS => 'IEPS',
        };
    }
}