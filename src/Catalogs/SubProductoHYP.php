<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

enum SubProductoHYP: string
{
    case Sp16 = 'SP16';
    case Sp17 = 'SP17';
    case Sp18 = 'SP18';
    case Sp19 = 'SP19';
    case Sp22 = 'SP22';
    case Sp23 = 'SP23';
    case Sp24 = 'SP24';
    case Sp25 = 'SP25';
    case Sp48 = 'SP48';

    public function descripcion(): string
    {
        return match ($this) {
            self::Sp16 => 'Gasolina regular menor a 91 octanos',
            self::Sp17 => 'Gasolina premium mayor o igual a 91 octanos',
            self::Sp18 => 'Diésel automotriz',
            self::Sp19 => 'Diésel marino',
            self::Sp22 => 'IFO380',
            self::Sp23 => 'Diésel industrial',
            self::Sp24 => 'Diésel de Ultra Bajo Azufre (DUBA)',
            self::Sp25 => 'Diésel agrícola',
            self::Sp48 => 'Gasóleo doméstico',
        };
    }
}