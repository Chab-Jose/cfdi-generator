<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteConcepto
{
    
    /** @var ComprobanteConceptoInformacionAduanera[] */
    public array $InformacionAduanera = [];
    
    /** @var ComprobanteConceptoCuentaPredial[] */
    public array $CuentaPredial = [];
    
    /** @var ComprobanteConceptoParte[] */
    public array $Parte = [];
    
    public ?ComprobanteConceptoComplementoConcepto $ComplementoConcepto = null;
    public ?ComprobanteConceptoImpuestos $Impuestos = null;
    public ?ComprobanteConceptoACuentaTerceros $ACuentaTerceros = null;
    
    public string $ClaveProdServ;
    public float $Cantidad = 0.0;
    public string $ClaveUnidad;
    public string $Descripcion;
    public float $ValorUnitario = 0.0;
    public float $Importe = 0.0;
    public string $ObjetoImp;

    public ?string $Unidad = null;
    public ?string $NoIdentificacion = null;
    public ?float $Descuento;

    public function addConceptoCuentaPredial(ComprobanteConceptoCuentaPredial $cuentaPredial): self
    {
        $this->CuentaPredial[] = $cuentaPredial;
        return $this;
    }

    public function addConceptoParte(ComprobanteConceptoParte $parte): self
    {
        $this->Parte[] = $parte;
        return $this;
    }

    public function addConceptoInformacionAduanera(ComprobanteConceptoInformacionAduanera $informacionAduanera): self
    {
        $this->InformacionAduanera[] = $informacionAduanera;
        return $this;
    }
}
