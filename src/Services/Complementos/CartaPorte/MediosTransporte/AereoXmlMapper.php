<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte;

use ChabJose\CfdiGenerator\Contracts\MedioTransporteXmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Utils\Xml\XmlAttributeHelpersTrait;

class AereoXmlMapper implements MedioTransporteXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS = 'http://www.sat.gob.mx/CartaPorte31';
    private const P = 'cartaporte31';

    public function soporta(CartaPorteMercancias $mercancias): bool
    {
        return $mercancias->TransporteAereo !== null;
    }

    public function mapear(\DOMDocument $doc, CartaPorteMercancias $mercancias): \DOMElement
    {
        $a = $mercancias->TransporteAereo;
        $node = $doc->createElementNS(self::NS, self::P . ':TransporteAereo');

        $this->setRequiredAttr($node, 'PermSCT', $a->PermSCT);
        $this->setRequiredAttr($node, 'NumPermisoSCT', $a->NumPermisoSCT);
        $this->setAttr($node, 'MatriculaAeronave', $a->MatriculaAeronave);
        $this->setAttr($node, 'NombreAseg', $a->NombreAseg);
        $this->setAttr($node, 'NumPolizaSeguro', $a->NumPolizaSeguro);
        $this->setRequiredAttr($node, 'NumeroGuia', $a->NumeroGuia);
        $this->setAttr($node, 'LugarContrato', $a->LugarContrato);
        $this->setRequiredAttr($node, 'CodigoTransportista', $a->CodigoTransportista);
        $this->setAttr($node, 'RFCEmbarcador', $a->RFCEmbarcador);
        $this->setAttr($node, 'NumRegIdTribEmbarc', $a->NumRegIdTribEmbarc);
        $this->setAttr($node, 'ResidenciaFiscalEmbarc', $a->ResidenciaFiscalEmbarc);
        $this->setAttr($node, 'NombreEmbarcador', $a->NombreEmbarcador);

        return $node;
    }
}