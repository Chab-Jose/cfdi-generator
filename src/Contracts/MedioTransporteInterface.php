<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;

interface MedioTransporteInterface
{
    /** Indica si este medio de transporte está presente en Mercancias. */
    public function estaPresente(CartaPorteMercancias $mercancias): bool;

    /** Nombre legible, usado en mensajes de error. */
    public function nombre(): string;
}