<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteImpuestos
{
    /** @var ComprobanteImpuestosRetencion[] */
    public array $Retenciones = [];

    /** @var ComprobanteImpuestosTraslado[] */
    public array $Traslados = [];

    public ?float $TotalImpuestosRetenidos = null;
    public ?float $TotalImpuestosTrasladados = null;


    public function addTraslados(ComprobanteImpuestosTraslado $traslado):self
    {
        $this->Traslados[] = $traslado;
        return $this;
    }

    public function addRetenciones(ComprobanteImpuestosRetencion $retencion):self
    {
        $this->Retenciones[] = $retencion;
        return $this;
    }

}
