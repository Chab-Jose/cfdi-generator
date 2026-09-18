<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Contracts\ComplementoXmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionado;
use ChabJose\CfdiGenerator\Services\Xml\XmlAttributeHelpersTrait;

class PagosXmlMapper implements ComplementoXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS_URI = 'http://www.sat.gob.mx/Pagos20';
    private const NS_PREFIX = 'pago20';

    public function soporta(object $complemento): bool
    {
        return $complemento instanceof Pagos;
    }

    public function namespacePrefix(): string
    {
        return self::NS_PREFIX;
    }

    public function namespaceUri(): string
    {
        return self::NS_URI;
    }

    public function schemaLocation(): string
    {
        return self::NS_URI . ' http://www.sat.gob.mx/sitio_internet/cfd/Pagos/Pagos20.xsd';
    }

    public function toXmlElement(\DOMDocument $doc, object $complemento): \DOMElement
    {
        if (!$complemento instanceof Pagos) {
            throw new \InvalidArgumentException('PagosXmlMapper solo procesa instancias de Pagos.');
        }

        $root = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Pagos');
        $this->setRequiredAttr($root, 'Version', '2.0');

        if ($complemento->Totales !== null) {
            $root->appendChild($this->crearTotales($doc, $complemento->Totales));
        }

        foreach ($complemento->Pago as $pago) {
            $root->appendChild($this->crearPago($doc, $pago));
        }

        return $root;
    }

    private function crearTotales(\DOMDocument $doc, $totales): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Totales');

        $this->setAttr($node, 'TotalRetencionesIVA', $this->formatDecimal($totales->TotalRetencionesIVA));
        $this->setAttr($node, 'TotalRetencionesISR', $this->formatDecimal($totales->TotalRetencionesISR));
        $this->setAttr($node, 'TotalRetencionesIEPS', $this->formatDecimal($totales->TotalRetencionesIEPS));
        $this->setAttr($node, 'TotalTrasladosBaseIVA16', $this->formatDecimal($totales->TotalTrasladosBaseIVA16));
        $this->setAttr($node, 'TotalTrasladosImpuestoIVA16', $this->formatDecimal($totales->TotalTrasladosImpuestoIVA16));
        $this->setAttr($node, 'TotalTrasladosBaseIVA8', $this->formatDecimal($totales->TotalTrasladosBaseIVA8));
        $this->setAttr($node, 'TotalTrasladosImpuestoIVA8', $this->formatDecimal($totales->TotalTrasladosImpuestoIVA8));
        $this->setAttr($node, 'TotalTrasladosBaseIVA0', $this->formatDecimal($totales->TotalTrasladosBaseIVA0));
        $this->setAttr($node, 'TotalTrasladosImpuestoIVA0', $this->formatDecimal($totales->TotalTrasladosImpuestoIVA0));
        $this->setAttr($node, 'TotalTrasladosBaseIVAExento', $this->formatDecimal($totales->TotalTrasladosBaseIVAExento));
        $this->setRequiredAttr($node, 'MontoTotalPagos', $this->formatDecimal($totales->MontoTotalPagos) ?? '');

        return $node;
    }

    private function crearPago(\DOMDocument $doc, PagosPago $pago): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Pago');

        $this->setRequiredAttr($node, 'FechaPago', $pago->FechaPago);
        $this->setRequiredAttr($node, 'FormaDePagoP', $pago->FormaDePagoP);
        $this->setRequiredAttr($node, 'MonedaP', $pago->MonedaP);
        $this->setAttr($node, 'TipoCambioP', $this->formatDecimal($pago->TipoCambioP, 6));
        $this->setRequiredAttr($node, 'Monto', $this->formatDecimal($pago->Monto) ?? '');
        $this->setAttr($node, 'NumOperacion', $pago->NumOperacion);
        $this->setAttr($node, 'RfcEmisorCtaOrd', $pago->RfcEmisorCtaOrd);
        $this->setAttr($node, 'NomBancoOrdExt', $pago->NomBancoOrdExt);
        $this->setAttr($node, 'CtaOrdenante', $pago->CtaOrdenante);
        $this->setAttr($node, 'RfcEmisorCtaBen', $pago->RfcEmisorCtaBen);
        $this->setAttr($node, 'CtaBeneficiario', $pago->CtaBeneficiario);
        $this->setAttr($node, 'TipoCadPago', $pago->TipoCadPago);
        $this->setAttr($node, 'CertPago', $pago->CertPago);
        $this->setAttr($node, 'CadPago', $pago->CadPago);
        $this->setAttr($node, 'SelloPago', $pago->SelloPago);

        foreach ($pago->DoctoRelacionado as $docto) {
            $node->appendChild($this->crearDoctoRelacionado($doc, $docto));
        }

        if ($pago->ImpuestosP !== null) {
            $node->appendChild($this->crearImpuestosP($doc, $pago->ImpuestosP));
        }

        return $node;
    }

    private function crearDoctoRelacionado(\DOMDocument $doc, PagosPagoDoctoRelacionado $docto): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':DoctoRelacionado');

        $this->setRequiredAttr($node, 'IdDocumento', $docto->IdDocumento);
        $this->setAttr($node, 'Serie', $docto->Serie);
        $this->setAttr($node, 'Folio', $docto->Folio);
        $this->setRequiredAttr($node, 'MonedaDR', $docto->MonedaDR);
        $this->setAttr($node, 'EquivalenciaDR', $this->formatDecimal($docto->EquivalenciaDR, 6));
        $this->setRequiredAttr($node, 'NumParcialidad', (string) $docto->NumParcialidad);
        $this->setRequiredAttr($node, 'ImpSaldoAnt', $this->formatDecimal($docto->ImpSaldoAnt) ?? '');
        $this->setRequiredAttr($node, 'ImpPagado', $this->formatDecimal($docto->ImpPagado) ?? '');
        $this->setRequiredAttr($node, 'ImpSaldoInsoluto', $this->formatDecimal($docto->ImpSaldoInsoluto) ?? '');
        $this->setRequiredAttr($node, 'ObjetoImpDR', $docto->ObjetoImpDR);

        if ($docto->ImpuestosDR !== null) {
            $node->appendChild($this->crearImpuestosDR($doc, $docto->ImpuestosDR));
        }

        return $node;
    }

    private function crearImpuestosDR(\DOMDocument $doc, $impuestosDR): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':ImpuestosDR');

        if (!empty($impuestosDR->RetencionesDR)) {
            $retencionesNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':RetencionesDR');
            foreach ($impuestosDR->RetencionesDR as $r) {
                $rNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':RetencionDR');
                $this->setRequiredAttr($rNode, 'BaseDR', $this->formatDecimal($r->BaseDR) ?? '');
                $this->setRequiredAttr($rNode, 'ImpuestoDR', $r->ImpuestoDR);
                $this->setRequiredAttr($rNode, 'TipoFactorDR', $r->TipoFactorDR);
                $this->setRequiredAttr($rNode, 'TasaOCuotaDR', $this->formatDecimal($r->TasaOCuotaDR, 6) ?? '');
                $this->setRequiredAttr($rNode, 'ImporteDR', $this->formatDecimal($r->ImporteDR) ?? '');
                $retencionesNode->appendChild($rNode);
            }
            $node->appendChild($retencionesNode);
        }

        if (!empty($impuestosDR->TrasladosDR)) {
            $trasladosNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':TrasladosDR');
            foreach ($impuestosDR->TrasladosDR as $t) {
                $tNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':TrasladoDR');
                $this->setRequiredAttr($tNode, 'BaseDR', $this->formatDecimal($t->BaseDR) ?? '');
                $this->setRequiredAttr($tNode, 'ImpuestoDR', $t->ImpuestoDR);
                $this->setRequiredAttr($tNode, 'TipoFactorDR', $t->TipoFactorDR);
                $this->setAttr($tNode, 'TasaOCuotaDR', $this->formatDecimal($t->TasaOCuotaDR, 6));
                $this->setAttr($tNode, 'ImporteDR', $this->formatDecimal($t->ImporteDR));
                $trasladosNode->appendChild($tNode);
            }
            $node->appendChild($trasladosNode);
        }

        return $node;
    }

    private function crearImpuestosP(\DOMDocument $doc, $impuestosP): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':ImpuestosP');

        if (!empty($impuestosP->RetencionesP)) {
            $retencionesNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':RetencionesP');
            foreach ($impuestosP->RetencionesP as $r) {
                $rNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':RetencionP');
                $this->setRequiredAttr($rNode, 'ImpuestoP', $r->ImpuestoP);
                $this->setRequiredAttr($rNode, 'ImporteP', $this->formatDecimal($r->ImporteP) ?? '');
                $retencionesNode->appendChild($rNode);
            }
            $node->appendChild($retencionesNode);
        }

        if (!empty($impuestosP->TrasladosP)) {
            $trasladosNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':TrasladosP');
            foreach ($impuestosP->TrasladosP as $t) {
                $tNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':TrasladoP');
                $this->setRequiredAttr($tNode, 'BaseP', $this->formatDecimal($t->BaseP) ?? '');
                $this->setRequiredAttr($tNode, 'ImpuestoP', $t->ImpuestoP);
                $this->setRequiredAttr($tNode, 'TipoFactorP', $t->TipoFactorP);
                $this->setAttr($tNode, 'TasaOCuotaP', $this->formatDecimal($t->TasaOCuotaP, 6));
                $this->setAttr($tNode, 'ImporteP', $this->formatDecimal($t->ImporteP));
                $trasladosNode->appendChild($tNode);
            }
            $node->appendChild($trasladosNode);
        }

        return $node;
    }
}