<?php
declare(strict_types=1);
namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;

interface CartaPorteTotalesCalculatorInterface
{
    public function calcular(CartaPorte $cartaPorte): CartaPorte;
}