<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

class Pagos
{
    public ?PagosTotales $Totales = null;

    /** @var PagosPago[] */
    public array $Pago = [];

    public string $Version = '2.0';

    public function addPago(PagosPago $pago): self
    {
        $this->Pago[] = $pago;
        return $this;

    }
}
