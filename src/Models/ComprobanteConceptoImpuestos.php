<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteConceptoImpuestos
{
    /** @var ComprobanteConceptoImpuestosTraslado[] */
    public array $Traslados = [];

    /** @var ComprobanteConceptoImpuestosRetencion[] */
    public array $Retenciones = [];

    public function addTraslado(ComprobanteConceptoImpuestosTraslado $traslado): self
    {
        $this->Traslados[] = $traslado;
        return $this;
    }

    public function addRetencion(ComprobanteConceptoImpuestosRetencion $retencion): self
    {
        $this->Retenciones[] = $retencion;
        return $this;
    }
}
