<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

/** Catálogo c_MetodoPago (CFDI 4.0). */
enum MetodoPago: string
{
    case PagoEnUnaSolaExhibicion = 'PUE';
    case PagoEnParcialidadesODiferido = 'PPD';

    public function descripcion(): string
    {
        return match ($this) {
            self::PagoEnUnaSolaExhibicion => 'Pago en una sola exhibición',
            self::PagoEnParcialidadesODiferido => 'Pago en parcialidades o diferido',
        };
    }
}