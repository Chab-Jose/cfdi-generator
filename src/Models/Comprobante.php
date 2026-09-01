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
    public ?string $Serie = null;
    public ?string $Folio = null;
    public ?string $Fecha = null;
    public ?string $Sello = null;
    public ?string $FormaPago = null;
    public ?string $NoCertificado = null;
    public ?string $Certificado = null;
    public ?string $CondicionesDePago = null;
    public float $SubTotal = 0.0;
    public ?float $Descuento;
    public ?string $Moneda = null;
    public ?float $TipoCambio;
    public float $Total = 0.0;
    public ?string $TipoDeComprobante = null;
    public ?string $Exportacion = null;
    public ?string $MetodoPago = null;
    public ?string $LugarExpedicion = null;
    public ?string $Confirmacion = null;
}
