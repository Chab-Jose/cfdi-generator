<?php

namespace ChabJose\CfdiGenerator\Models;

use ChabJose\CfdiGenerator\Catalogs\Exportacion;
use ChabJose\CfdiGenerator\Catalogs\FormaPago;
use ChabJose\CfdiGenerator\Catalogs\MetodoPago;
use ChabJose\CfdiGenerator\Catalogs\TipoDeComprobante;

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
    public string $LugarExpedicion = '';
    public string $Fecha = '';
    public string $NoCertificado = '';
    public string $Moneda = '';
    public string|TipoDeComprobante $TipoDeComprobante = '';
    public string|Exportacion $Exportacion = '';
    
    public ?float $Descuento = null;
    public ?float $TipoCambio = null;
    public ?string $Serie = null;
    public ?string $Folio = null;
    public ?string $Sello = null;
    public string|FormaPago|null $FormaPago = null;
    public ?string $Certificado = null;
    public ?string $CondicionesDePago = null;
    public string|MetodoPago|null $MetodoPago = null;
    public ?string $Confirmacion = null;
}
