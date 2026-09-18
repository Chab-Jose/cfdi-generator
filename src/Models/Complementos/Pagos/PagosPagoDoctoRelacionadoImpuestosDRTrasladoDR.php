<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

class PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR
{
    public float $BaseDR = 0.0;
    public string $ImpuestoDR;
    public string $TipoFactorDR;

    public ?float $TasaOCuotaDR = null;
    public ?float $ImporteDR = null;
}
