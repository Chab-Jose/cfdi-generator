<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

use ChabJose\CfdiGenerator\Catalogs\Impuesto;
use ChabJose\CfdiGenerator\Catalogs\TipoFactor;

class PagosPagoDoctoRelacionadoImpuestosDRRetencionDR
{
    public float $BaseDR = 0.0;
    public string|Impuesto $ImpuestoDR;
    public string|TipoFactor $TipoFactorDR;
    public float $TasaOCuotaDR = 0.0;
    public float $ImporteDR = 0.0;
}
