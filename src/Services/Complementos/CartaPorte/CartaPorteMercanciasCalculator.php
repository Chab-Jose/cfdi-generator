<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Contracts\CartaPorteMercanciasCalculatorInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancia;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;

class CartaPorteMercanciasCalculator implements CartaPorteMercanciasCalculatorInterface
{
    public function calcular(CartaPorteMercancias $mercancias): CartaPorteMercancias
    {
        $mercancias->PesoBrutoTotal = $this->redondear(
            array_sum(array_map(fn(CartaPorteMercancia $m) => $m->PesoEnKg, $mercancias->Mercancia))
        );

        $mercancias->NumTotalMercancias = count($mercancias->Mercancia);

        return $mercancias;
    }

    private function redondear(float $valor): float
    {
        return round($valor, 3, PHP_ROUND_HALF_UP);
    }
}