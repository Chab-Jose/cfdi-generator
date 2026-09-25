<?php
declare(strict_types=1);
namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;

interface CartaPorteMercanciasCalculatorInterface
{
    public function calcular(CartaPorteMercancias $mercancias): CartaPorteMercancias;
}