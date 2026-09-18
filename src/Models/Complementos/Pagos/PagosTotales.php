<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

class PagosTotales
{
    public float $MontoTotalPagos = 0;

    public ?float $TotalRetencionesIVA = null;
    public ?float $TotalRetencionesISR = null;
    public ?float $TotalRetencionesIEPS = null;
    public ?float $TotalTrasladosBaseIVA16 = null;
    public ?float $TotalTrasladosImpuestoIVA16 = null;
    public ?float $TotalTrasladosBaseIVA8 = null;
    public ?float $TotalTrasladosImpuestoIVA8 = null;
    public ?float $TotalTrasladosBaseIVA0 = null;
    public ?float $TotalTrasladosImpuestoIVA0 = null;
    public ?float $TotalTrasladosBaseIVAExento = null;
}
