<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaOtroPago
{
    public string $TipoOtroPago = '';
    public string $Clave = '';
    public string $Concepto = '';
    public float $Importe = 0.0;

    public ?NominaOtroPagoSubsidioAlEmpleo $SubsidioAlEmpleo = null;
    public ?NominaOtroPagoCompensacionSaldosAFavor $CompensacionSaldosAFavor = null;
}