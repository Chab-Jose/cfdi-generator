<?php

namespace ChabJose\CfdiGenerator\Models;

use ChabJose\CfdiGenerator\Catalogs\Impuesto;
use ChabJose\CfdiGenerator\Catalogs\TipoFactor;

class ComprobanteConceptoImpuestosTraslado
{
    public float $Base = 0.0;
    public string|Impuesto $Impuesto;
    public string|TipoFactor $TipoFactor;
    public ?float $TasaOCuota = null;
    public ?float $Importe = null;
}
