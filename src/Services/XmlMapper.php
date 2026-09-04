<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\XmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteCfdiRelacionados;
use ChabJose\CfdiGenerator\Models\ComprobanteCfdiRelacionadosCfdiRelacionado;
use ChabJose\CfdiGenerator\Models\ComprobanteEmisor;
use ChabJose\CfdiGenerator\Models\ComprobanteReceptor;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoACuentaTerceros;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoCuentaPredial;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoInformacionAduanera;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoParte;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoParteInformacionAduanera;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestos;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosTraslado;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosRetencion;
use ChabJose\CfdiGenerator\Models\ComprobanteImpuestos;
use ChabJose\CfdiGenerator\Models\ComprobanteImpuestosTraslado;
use ChabJose\CfdiGenerator\Models\ComprobanteImpuestosRetencion;
use ChabJose\CfdiGenerator\Models\ComprobanteInformacionGlobal;

final class XmlMapper implements XmlMapperInterface
{
    private const NS_CFDI = 'http://www.sat.gob.mx/cfd/4';
    private const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';
    private const XSD_LOCATION = 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd';

    private \DOMDocument $dom;

    public function toXml(Comprobante $comprobante): string
    {
        return $this->map($comprobante)->saveXML();
    }

    public function map(Comprobante $comprobante): \DOMDocument
    {
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = false;

        $root = $this->crearNodoComprobante($comprobante);
        $this->dom->appendChild($root);

        // Orden exigido por el XSD 4.0:
        // InformacionGlobal? , CfdiRelacionados? , Emisor, Receptor, Conceptos, Impuestos?, Complemento?, Addenda?

        if ($comprobante->InformacionGlobal !== null) {
            $root->appendChild($this->crearNodoInformacionGlobal($comprobante->InformacionGlobal));
        }

        foreach ($comprobante->CfdiRelacionados as $cfdiRelacionados) {
            $root->appendChild($this->crearNodoCfdiRelacionados($cfdiRelacionados));
        }

        $root->appendChild($this->crearNodoEmisor($comprobante->Emisor));
        $root->appendChild($this->crearNodoReceptor($comprobante->Receptor));
        $root->appendChild($this->crearNodoConceptos($comprobante->Conceptos));

        if ($comprobante->Impuestos !== null) {
            $root->appendChild($this->crearNodoImpuestos($comprobante->Impuestos));
        }

        if ($comprobante->Complemento !== null) {
            $root->appendChild($this->crearNodoComplemento($comprobante->Complemento));
        }

        if ($comprobante->Addenda !== null) {
            $root->appendChild($this->crearNodoAddenda($comprobante->Addenda));
        }

        return $this->dom;
    }

    // ---------- Comprobante (raíz) ----------

    private function crearNodoComprobante(Comprobante $c): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Comprobante');

        $nodo->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', self::NS_XSI);
        $nodo->setAttributeNS(self::NS_XSI, 'xsi:schemaLocation', self::XSD_LOCATION);

        $this->setAttr($nodo, 'Version', $c->Version);
        $this->setAttr($nodo, 'Serie', $c->Serie);
        $this->setAttr($nodo, 'Folio', $c->Folio);
        $this->setAttr($nodo, 'Fecha', $c->Fecha);
        $this->setAttr($nodo, 'Sello', $c->Sello);
        $this->setAttr($nodo, 'FormaPago', $c->FormaPago);
        $this->setAttr($nodo, 'NoCertificado', $c->NoCertificado);
        $this->setAttr($nodo, 'Certificado', $c->Certificado);
        $this->setAttr($nodo, 'CondicionesDePago', $c->CondicionesDePago);
        $this->setAttr($nodo, 'SubTotal', $this->formatoMonto($c->SubTotal));
        $this->setAttr($nodo, 'Descuento', $c->Descuento !== null ? $this->formatoMonto($c->Descuento) : null);
        $this->setAttr($nodo, 'Moneda', $c->Moneda);
        $this->setAttr($nodo, 'TipoCambio', $c->TipoCambio !== null ? (string) $c->TipoCambio : null);
        $this->setAttr($nodo, 'Total', $this->formatoMonto($c->Total));
        $this->setAttr($nodo, 'TipoDeComprobante', $c->TipoDeComprobante);
        $this->setAttr($nodo, 'Exportacion', $c->Exportacion);
        $this->setAttr($nodo, 'MetodoPago', $c->MetodoPago);
        $this->setAttr($nodo, 'LugarExpedicion', $c->LugarExpedicion);
        $this->setAttr($nodo, 'Confirmacion', $c->Confirmacion);

        return $nodo;
    }

    // ---------- InformacionGlobal ----------
    private function crearNodoInformacionGlobal(ComprobanteInformacionGlobal $info): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:InformacionGlobal');
        $this->setAttr($nodo, 'Periodicidad', $info->Periodicidad);
        $this->setAttr($nodo, 'Meses', $info->Meses);
        $this->setAttr($nodo, 'Año', (string) $info->Año);
        return $nodo;
    }

    // ---------- CfdiRelacionados ----------

    private function crearNodoCfdiRelacionados(ComprobanteCfdiRelacionados $rel): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:CfdiRelacionados');
        $this->setAttr($nodo, 'TipoRelacion', $rel->TipoRelacion);

        foreach ($rel->CfdiRelacionado as $cfdiRelacionado) {
            $nodo->appendChild($this->crearNodoCfdiRelacionado($cfdiRelacionado));
        }

        return $nodo;
    }

    private function crearNodoCfdiRelacionado(ComprobanteCfdiRelacionadosCfdiRelacionado $r): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:CfdiRelacionado');
        $this->setAttr($nodo, 'UUID', $r->UUID);
        return $nodo;
    }

    // ---------- Emisor / Receptor ----------

    private function crearNodoEmisor(ComprobanteEmisor $emisor): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Emisor');
        $this->setAttr($nodo, 'Rfc', $emisor->Rfc);
        $this->setAttr($nodo, 'Nombre', $emisor->Nombre);
        $this->setAttr($nodo, 'RegimenFiscal', $emisor->RegimenFiscal);
        $this->setAttr($nodo, 'FacAtrAdquirente', $emisor->FacAtrAdquirente);
        return $nodo;
    }

    private function crearNodoReceptor(ComprobanteReceptor $receptor): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Receptor');
        $this->setAttr($nodo, 'Rfc', $receptor->Rfc);
        $this->setAttr($nodo, 'Nombre', $receptor->Nombre);
        $this->setAttr($nodo, 'DomicilioFiscalReceptor', $receptor->DomicilioFiscalReceptor);
        $this->setAttr($nodo, 'ResidenciaFiscal', $receptor->ResidenciaFiscal);
        $this->setAttr($nodo, 'NumRegIdTrib', $receptor->NumRegIdTrib);
        $this->setAttr($nodo, 'RegimenFiscalReceptor', $receptor->RegimenFiscalReceptor);
        $this->setAttr($nodo, 'UsoCFDI', $receptor->UsoCFDI);
        return $nodo;
    }

    // ---------- Conceptos ----------

    private function crearNodoConceptos(array $conceptos): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Conceptos');

        foreach ($conceptos as $concepto) {
            $nodo->appendChild($this->crearNodoConcepto($concepto));
        }

        return $nodo;
    }

    private function crearNodoConcepto(ComprobanteConcepto $concepto): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Concepto');

        $this->setAttr($nodo, 'ClaveProdServ', $concepto->ClaveProdServ);
        $this->setAttr($nodo, 'NoIdentificacion', $concepto->NoIdentificacion);
        $this->setAttr($nodo, 'Cantidad', $this->formatoNumero($concepto->Cantidad));
        $this->setAttr($nodo, 'ClaveUnidad', $concepto->ClaveUnidad);
        $this->setAttr($nodo, 'Unidad', $concepto->Unidad);
        $this->setAttr($nodo, 'Descripcion', $concepto->Descripcion);
        $this->setAttr($nodo, 'ValorUnitario', $this->formatoMonto($concepto->ValorUnitario));
        $this->setAttr($nodo, 'Importe', $this->formatoMonto($concepto->Importe));
        $this->setAttr($nodo, 'Descuento', $concepto->Descuento !== null ? $this->formatoMonto($concepto->Descuento) : null);
        $this->setAttr($nodo, 'ObjetoImp', $concepto->ObjetoImp);

        if ($concepto->ACuentaTerceros !== null) {
            $nodo->appendChild($this->crearNodoACuentaTerceros($concepto->ACuentaTerceros));
        }

        foreach ($concepto->InformacionAduanera as $informacionAduanera) {
            $nodo->appendChild($this->crearNodoConceptoInformacionAduanera($informacionAduanera));
        }

        foreach ($concepto->CuentaPredial as $cuentaPredial) {
            $nodo->appendChild($this->crearNodoConceptoCuentaPredial($cuentaPredial));
        }

        if ($concepto->ComplementoConcepto !== null && !empty($concepto->ComplementoConcepto->Any)) {
            $nodo->appendChild($this->crearNodoComplementoConcepto($concepto->ComplementoConcepto));
        }

        if ($concepto->Impuestos !== null) {
            $nodo->appendChild($this->crearNodoConceptoImpuestos($concepto->Impuestos));
        }

        foreach ($concepto->Parte as $parte) {
            $nodo->appendChild($this->crearNodoConceptoParte($parte));
        }

        return $nodo;
    }

    private function crearNodoACuentaTerceros(ComprobanteConceptoACuentaTerceros $act): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:ACuentaTerceros');
        $this->setAttr($nodo, 'RfcACuentaTerceros', $act->RfcACuentaTerceros);
        $this->setAttr($nodo, 'NombreACuentaTerceros', $act->NombreACuentaTerceros);
        $this->setAttr($nodo, 'RegimenFiscalACuentaTerceros', $act->RegimenFiscalACuentaTerceros);
        $this->setAttr($nodo, 'DomicilioFiscalACuentaTerceros', $act->DomicilioFiscalACuentaTerceros);
        return $nodo;
    }

    private function crearNodoConceptoInformacionAduanera(ComprobanteConceptoInformacionAduanera $ia): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:InformacionAduanera');
        $this->setAttr($nodo, 'NumeroPedimento', $ia->NumeroPedimento);
        return $nodo;
    }

    private function crearNodoConceptoCuentaPredial(ComprobanteConceptoCuentaPredial $cp): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:CuentaPredial');
        $this->setAttr($nodo, 'Numero', $cp->Numero);
        return $nodo;
    }

    /** @param object{Any: array<string, object>} $complementoConcepto */
    private function crearNodoComplementoConcepto(object $complementoConcepto): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:ComplementoConcepto');
        // Cada entrada de Any ya debería ser resuelta por su propio handler de complemento
        // (ver ComplementoRegistry) y devolver un \DOMElement listo para anexar.
        foreach ($complementoConcepto->Any as $elemento) {
            if ($elemento instanceof \DOMElement) {
                $nodo->appendChild($this->dom->importNode($elemento, true));
            }
        }
        return $nodo;
    }

    // ---------- Impuestos por concepto ----------

    private function crearNodoConceptoImpuestos(ComprobanteConceptoImpuestos $impuestos): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Impuestos');

        if (!empty($impuestos->Traslados)) {
            $nodoTraslados = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Traslados');
            foreach ($impuestos->Traslados as $traslado) {
                $nodoTraslados->appendChild($this->crearNodoConceptoTraslado($traslado));
            }
            $nodo->appendChild($nodoTraslados);
        }

        if (!empty($impuestos->Retenciones)) {
            $nodoRetenciones = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Retenciones');
            foreach ($impuestos->Retenciones as $retencion) {
                $nodoRetenciones->appendChild($this->crearNodoConceptoRetencion($retencion));
            }
            $nodo->appendChild($nodoRetenciones);
        }

        return $nodo;
    }

    private function crearNodoConceptoTraslado(ComprobanteConceptoImpuestosTraslado $t): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Traslado');
        $this->setAttr($nodo, 'Base', $this->formatoMonto($t->Base));
        $this->setAttr($nodo, 'Impuesto', $t->Impuesto);
        $this->setAttr($nodo, 'TipoFactor', $t->TipoFactor);
        $this->setAttr($nodo, 'TasaOCuota', $t->TasaOCuota !== null ? $this->formatoTasa($t->TasaOCuota) : null);
        $this->setAttr($nodo, 'Importe', $t->Importe !== null ? $this->formatoMonto($t->Importe) : null);
        return $nodo;
    }

    private function crearNodoConceptoRetencion(ComprobanteConceptoImpuestosRetencion $r): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Retencion');
        $this->setAttr($nodo, 'Base', $this->formatoMonto($r->Base));
        $this->setAttr($nodo, 'Impuesto', $r->Impuesto);
        $this->setAttr($nodo, 'TipoFactor', $r->TipoFactor);
        $this->setAttr($nodo, 'TasaOCuota', $this->formatoTasa($r->TasaOCuota));
        $this->setAttr($nodo, 'Importe', $this->formatoMonto($r->Importe));
        return $nodo;
    }

    // ---------- Parte ----------

    private function crearNodoConceptoParte(ComprobanteConceptoParte $parte): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Parte');
        $this->setAttr($nodo, 'ClaveProdServ', $parte->ClaveProdServ);
        $this->setAttr($nodo, 'NoIdentificacion', $parte->NoIdentificacion);
        $this->setAttr($nodo, 'Cantidad', $this->formatoNumero($parte->Cantidad));
        $this->setAttr($nodo, 'Unidad', $parte->Unidad);
        $this->setAttr($nodo, 'Descripcion', $parte->Descripcion);
        $this->setAttr($nodo, 'ValorUnitario', $parte->ValorUnitario !== null ? $this->formatoMonto($parte->ValorUnitario) : null);
        $this->setAttr($nodo, 'Importe', $parte->Importe !== null ? $this->formatoMonto($parte->Importe) : null);

        foreach ($parte->InformacionAduanera as $ia) {
            $nodo->appendChild($this->crearNodoParteInformacionAduanera($ia));
        }

        return $nodo;
    }

    private function crearNodoParteInformacionAduanera(ComprobanteConceptoParteInformacionAduanera $ia): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:InformacionAduanera');
        $this->setAttr($nodo, 'NumeroPedimento', $ia->NumeroPedimento);
        return $nodo;
    }

    // ---------- Impuestos globales (nivel Comprobante) ----------

    private function crearNodoImpuestos(ComprobanteImpuestos $impuestos): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Impuestos');

        $this->setAttr(
            $nodo,
            'TotalImpuestosRetenidos',
            $impuestos->TotalImpuestosRetenidos !== null ? $this->formatoMonto($impuestos->TotalImpuestosRetenidos) : null
        );
        $this->setAttr(
            $nodo,
            'TotalImpuestosTrasladados',
            $impuestos->TotalImpuestosTrasladados !== null ? $this->formatoMonto($impuestos->TotalImpuestosTrasladados) : null
        );

        if (!empty($impuestos->Retenciones)) {
            $nodoRetenciones = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Retenciones');
            foreach ($impuestos->Retenciones as $retencion) {
                $nodoRetenciones->appendChild($this->crearNodoRetencionGlobal($retencion));
            }
            $nodo->appendChild($nodoRetenciones);
        }

        if (!empty($impuestos->Traslados)) {
            $nodoTraslados = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Traslados');
            foreach ($impuestos->Traslados as $traslado) {
                $nodoTraslados->appendChild($this->crearNodoTrasladoGlobal($traslado));
            }
            $nodo->appendChild($nodoTraslados);
        }

        return $nodo;
    }

    private function crearNodoRetencionGlobal(ComprobanteImpuestosRetencion $r): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Retencion');
        $this->setAttr($nodo, 'Impuesto', $r->Impuesto);
        $this->setAttr($nodo, 'Importe', $this->formatoMonto($r->Importe));
        return $nodo;
    }

    private function crearNodoTrasladoGlobal(ComprobanteImpuestosTraslado $t): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Traslado');
        $this->setAttr($nodo, 'Base', $this->formatoMonto($t->Base));
        $this->setAttr($nodo, 'Impuesto', $t->Impuesto);
        $this->setAttr($nodo, 'TipoFactor', $t->TipoFactor);
        $this->setAttr($nodo, 'TasaOCuota', $t->TasaOCuota !== null ? $this->formatoTasa($t->TasaOCuota) : null);
        $this->setAttr($nodo, 'Importe', $t->Importe !== null ? $this->formatoMonto($t->Importe) : null);
        return $nodo;
    }

    // ---------- Complemento / Addenda (nivel Comprobante) ----------

    /** @param object{Any: array<string, object>} $complemento */
    private function crearNodoComplemento(object $complemento): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Complemento');
        foreach ($complemento->Any as $elemento) {
            if ($elemento instanceof \DOMElement) {
                $nodo->appendChild($this->dom->importNode($elemento, true));
            }
        }
        return $nodo;
    }

    /** @param object{Any: array<string, mixed>} $addenda */
    private function crearNodoAddenda(object $addenda): \DOMElement
    {
        $nodo = $this->dom->createElementNS(self::NS_CFDI, 'cfdi:Addenda');
        foreach ($addenda->Any as $elemento) {
            if ($elemento instanceof \DOMElement) {
                $nodo->appendChild($this->dom->importNode($elemento, true));
            }
        }
        return $nodo;
    }

    // ---------- Helpers ----------

    private function setAttr(\DOMElement $nodo, string $nombre, ?string $valor): void
    {
        if ($valor !== null && $valor !== '') {
            $nodo->setAttribute($nombre, $valor);
        }
    }

    /** Montos: el SAT acepta hasta 6 decimales; 2 es seguro para la mayoría de los casos */
    private function formatoMonto(float $monto): string
    {
        return number_format($monto, 2, '.', '');
    }

    /** Cantidad/Cantidad de Parte: sin forzar decimales fijos, pero sin notación científica */
    private function formatoNumero(float $numero): string
    {
        return rtrim(rtrim(number_format($numero, 6, '.', ''), '0'), '.') ?: '0';
    }

    /** TasaOCuota típicamente requiere 6 decimales (ej. IVA 16% = 0.160000) */
    private function formatoTasa(float $tasa): string
    {
        return number_format($tasa, 6, '.', '');
    }
}