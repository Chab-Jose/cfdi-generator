<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

class PagosPagoDoctoRelacionado
{
    public ?PagosPagoDoctoRelacionadoImpuestosDR $ImpuestosDR = null;

    public string $IdDocumento;
    public string $MonedaDR;
    public string $NumParcialidad;
    public float $ImpSaldoAnt = 0.0;
    public float $ImpPagado = 0.0;
    public float $ImpSaldoInsoluto = 0.0;
    public string $ObjetoImpDR;

    public ?string $Serie = null;
    public ?string $Folio = null;
    public float $EquivalenciaDR = 0.0;
}
