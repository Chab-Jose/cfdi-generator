<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorteDetalleMercancia
{
    public string $UnidadPesoMerc = '';
    public float $PesoBruto = 0.0;
    public float $PesoNeto = 0.0;
    public float $PesoTara = 0.0;
    public ?int $NumPiezas = null;
}