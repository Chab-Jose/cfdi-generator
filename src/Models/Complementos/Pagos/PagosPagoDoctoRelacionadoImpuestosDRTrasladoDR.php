<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

use ChabJose\CfdiGenerator\Catalogs\Impuesto;
use ChabJose\CfdiGenerator\Catalogs\TipoFactor;

class PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR
{
    public float $BaseDR = 0.0;
    public string|Impuesto $ImpuestoDR;
    public string|TipoFactor $TipoFactorDR;

    public ?float $TasaOCuotaDR = null;
    public ?float $ImporteDR = null;
}
