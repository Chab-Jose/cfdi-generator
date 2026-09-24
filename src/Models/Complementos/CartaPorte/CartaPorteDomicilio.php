<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorteDomicilio
{
    public ?string $Calle = null;
    public ?string $NumeroExterior = null;
    public ?string $NumeroInterior = null;
    public ?string $Colonia = null;
    public ?string $Localidad = null;
    public ?string $Referencia = null;
    public ?string $Municipio = null;
    public string $Estado = '';
    public string $Pais = '';
    public string $CodigoPostal = '';
}