<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

class PagosPagoDoctoRelacionadoImpuestosDR
{
    /** @var PagosPagoDoctoRelacionadoImpuestosDRRetencionDR[] */
    public array $RetencionesDR = [];

    /** @var PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR[] */
    public array $TrasladosDR = [];

    public function addRetencionDR(PagosPagoDoctoRelacionadoImpuestosDRRetencionDR $retencionDR): self
    {
        $this->RetencionesDR[] = $retencionDR;
        return $this;
    }

    public function addTrasladoDR(PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR $trasladoDR): self
    {
        $this->TrasladosDR[] = $trasladoDR;
        return $this;
    }
}
