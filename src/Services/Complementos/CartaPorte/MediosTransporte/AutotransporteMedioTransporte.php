<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte;

use ChabJose\CfdiGenerator\Contracts\MedioTransporteInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;

class AutotransporteMedioTransporte implements MedioTransporteInterface
{
    public function estaPresente(CartaPorteMercancias $mercancias): bool
    {
        return $mercancias->Autotransporte !== null;
    }

    public function nombre(): string
    {
        return 'Autotransporte';
    }
}