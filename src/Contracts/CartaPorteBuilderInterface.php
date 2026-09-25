<?php
declare(strict_types=1);
namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;

interface CartaPorteBuilderInterface
{
    public function build(CartaPorte $cartaPorte): CartaPorte;
}