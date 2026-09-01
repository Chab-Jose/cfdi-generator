<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteConceptoParte
{
    /** @var ComprobanteConceptoParteInformacionAduanera[] */
    public array $InformacionAduanera = [];

    public ?string $ClaveProdServ = null;
    public ?string $NoIdentificacion = null;
    public float $Cantidad = 0.0;
    public ?string $Unidad = null;
    public ?string $Descripcion = null;
    public float $ValorUnitario = 0.0;
    public float $Importe = 0.0;

    public function addInformacionAduanera(ComprobanteConceptoParteInformacionAduanera $informacionAduanera):self
    {
        $this->InformacionAduanera[] = $informacionAduanera;
        return $this;
    }
}
