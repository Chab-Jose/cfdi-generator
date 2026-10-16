<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

enum ClaveHYP: string
{
    case CombustibleDiesel = '15101505';
    case GasolinaRegular = '15101514';
    case GasolinaPremium = '15101515';

    public function descripcion(): string
    {
        return match ($this) {
            self::CombustibleDiesel => 'Combustible diésel',
            self::GasolinaRegular => 'Gasolina regular menor a 91 octanos',
            self::GasolinaPremium => 'Gasolina premium mayor o igual a 91 octanos',
        };
    }
}