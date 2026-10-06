<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Contracts\CartaPorteBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\ComplementoBuilderInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;

class CartaPorteComplementoBuilderAdapter implements ComplementoBuilderInterface
{
    public function __construct(
        private CartaPorteBuilderInterface $cartaPorteBuilder,
    ) {
    }

    public function soporta(object $complemento): bool
    {
        return $complemento instanceof CartaPorte;
    }

    public function build(object $complemento): object
    {
        if (!$complemento instanceof CartaPorte) {
            throw new \InvalidArgumentException('CartaPorteComplementoBuilderAdapter solo procesa instancias de CartaPorte.');
        }

        return $this->cartaPorteBuilder->build($complemento);
    }
}