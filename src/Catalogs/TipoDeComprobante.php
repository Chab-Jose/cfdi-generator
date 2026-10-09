<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

/** Catálogo c_TipoDeComprobante (CFDI 4.0). */
enum TipoDeComprobante: string
{
    case Ingreso = 'I';
    case Egreso = 'E';
    case Traslado = 'T';
    case Nomina = 'N';
    case Pago = 'P';

    public function descripcion(): string
    {
        return match ($this) {
            self::Ingreso => 'Ingreso',
            self::Egreso => 'Egreso',
            self::Traslado => 'Traslado',
            self::Nomina => 'Nómina',
            self::Pago => 'Pago',  
        };
    }
}