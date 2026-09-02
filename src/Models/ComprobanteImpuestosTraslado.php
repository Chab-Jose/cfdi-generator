<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteImpuestosTraslado
{
    public float $Base = 0.0;
    public string $Impuesto;
    public string $TipoFactor;
    public ?float $TasaOCuota;
    public ?float $Importe;
}
