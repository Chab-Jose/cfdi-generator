<?php

namespace ChabJose\CfdiGenerator\Models;

use ChabJose\CfdiGenerator\Catalogs\Impuesto;
use ChabJose\CfdiGenerator\Catalogs\TipoFactor;

class ComprobanteConceptoImpuestosRetencion
{
    public float $Base = 0.0;
    public string|Impuesto $Impuesto;
    public string|TipoFactor $TipoFactor;
    public float $TasaOCuota = 0.0;
    public float $Importe = 0.0;
}
