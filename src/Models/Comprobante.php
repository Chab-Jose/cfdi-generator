<?php

namespace ChabJose\CfdiGenerator\Models;

class Comprobante
{    
    /** @var ComprobanteCfdiRelacionados[] */
    public array $CfdiRelacionados = [];
    
    /** @var ComprobanteConcepto[] */
    public array $Conceptos = [];
    
    public ?ComprobanteInformacionGlobal $InformacionGlobal = null;
    public ?ComprobanteEmisor $Emisor = null;
    public ?ComprobanteReceptor $Receptor = null;
    public ?ComprobanteImpuestos $Impuestos = null;
    public ?ComprobanteComplemento $Complemento = null;
    public ?ComprobanteAddenda $Addenda = null;

    public string $Version = '4.0';
    public float $SubTotal = 0.0;
    public float $Total = 0.0;
    public string $LugarExpedicion;
    public string $Fecha;
    public string $NoCertificado;
    public string $Moneda;
    public string $TipoDeComprobante;
    
    public ?float $Descuento;
    public ?float $TipoCambio;
    public string $Exportacion;
    public ?string $Serie = null;
    public ?string $Folio = null;
    public ?string $Sello = null;
    public ?string $FormaPago = null;
    public ?string $Certificado = null;
    public ?string $CondicionesDePago = null;
    public ?string $MetodoPago = null;
    public ?string $Confirmacion = null;
}
