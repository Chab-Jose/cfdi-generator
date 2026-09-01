<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteConceptoImpuestosRetencion
{
    public float $Base = 0.0;
    public ?string $Impuesto = null;
    public ?string $TipoFactor = null;
    public float $TasaOCuota = 0.0;
    public float $Importe = 0.0;
}
