<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteConceptoParte
{
    /** @var ComprobanteConceptoParteInformacionAduanera[] */
    public array $InformacionAduanera = [];

    public string $ClaveProdServ;
    public string $Descripcion;
    public float $Cantidad = 0.0;

    public ?string $NoIdentificacion = null;
    public ?string $Unidad = null;
    public ?float $ValorUnitario;
    public ?float $Importe;

    public function addInformacionAduanera(ComprobanteConceptoParteInformacionAduanera $informacionAduanera):self
    {
        $this->InformacionAduanera[] = $informacionAduanera;
        return $this;
    }
}
