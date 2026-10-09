<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Utils\Xml;

trait NormalizaValorCatalogoTrait
{
    /**
     * Normaliza un valor de catálogo: si es un enum respaldado (BackedEnum),
     * devuelve su ->value; si ya es string o null, lo regresa tal cual.
     */
    private function valorEscalar(string|\BackedEnum|null $valor): ?string
    {
        return $valor instanceof \BackedEnum ? $valor->value : $valor;
    }
}