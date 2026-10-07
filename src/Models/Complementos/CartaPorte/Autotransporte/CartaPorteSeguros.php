<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte;

class CartaPorteSeguros
{
    public string $AseguraRespCivil = '';
    public string $PolizaRespCivil = '';
    public ?string $AseguraMedAmbiente = null;
    public ?string $PolizaMedAmbiente = null;
    public ?string $AseguraCarga = null;
    public ?string $PolizaCarga = null;
    public ?float $PrimaSeguro = null;
}