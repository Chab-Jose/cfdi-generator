<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorteCantidadTransporta
{
    public float $Cantidad = 0.0;
    public string $IDOrigen = '';
    public string $IDDestino = '';
    public ?string $CvesTransporte = null;
}