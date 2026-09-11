<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteImpuestosTraslado
{
    public float $Base = 0.0;
    public string $Impuesto;
    public string $TipoFactor;
    public ?float $TasaOCuota = null;
    public ?float $Importe = null;
}
