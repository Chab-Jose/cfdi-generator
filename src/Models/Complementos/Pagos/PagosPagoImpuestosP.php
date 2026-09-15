<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

class PagosPagoImpuestosP
{
    /** @var PagosPagoImpuestosPRetencionP[] */
    public array $RetencionesP = [];

    /** @var PagosPagoImpuestosPTrasladoP[] */
    public array $TrasladosP = [];

    public function addRetencionP(PagosPagoImpuestosPRetencionP $retencionP): self
    {
        $this->RetencionesP[] = $retencionP;
        return $this;
    }

    public function addTrasladoDR(PagosPagoImpuestosPTrasladoP $trasladoP): self
    {
        $this->TrasladosP[] = $trasladoP;
        return $this;
    }
}
