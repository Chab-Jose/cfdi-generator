<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Contracts\CartaPorteTotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteUbicacion;

class CartaPorteTotalesCalculator implements CartaPorteTotalesCalculatorInterface
{
    /** Clave del catálogo c_TipoUbicacion para "Destino" */
    private const DESTINO = '02';

    /**
     * TotalDistRec = suma de DistanciaRecorrida únicamente de las
     * Ubicaciones marcadas como Destino (TipoUbicacion = "02"),
     * confirmado contra la guía de llenado oficial del SAT.
     */
    public function calcular(CartaPorte $cartaPorte): CartaPorte
    {
        $ubicacionesDestino = array_filter(
            $cartaPorte->Ubicaciones,
            fn(CartaPorteUbicacion $u) => $u->TipoUbicacion === self::DESTINO
        );

        $distancias = array_filter(
            array_map(fn(CartaPorteUbicacion $u) => $u->DistanciaRecorrida, $ubicacionesDestino),
            fn($d) => $d !== null
        );

        if (!empty($distancias)) {
            $cartaPorte->TotalDistRec = round(array_sum($distancias), 3, PHP_ROUND_HALF_UP);
        }

        return $cartaPorte;
    }
}