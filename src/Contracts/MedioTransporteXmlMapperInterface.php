<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;

interface MedioTransporteXmlMapperInterface
{
    public function soporta(CartaPorteMercancias $mercancias): bool;

    public function mapear(\DOMDocument $doc, CartaPorteMercancias $mercancias): \DOMElement;
}