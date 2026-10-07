<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte;

use ChabJose\CfdiGenerator\Contracts\MedioTransporteXmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteRemolque;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Utils\Xml\XmlAttributeHelpersTrait;

class AutotransporteXmlMapper implements MedioTransporteXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS = 'http://www.sat.gob.mx/CartaPorte31';
    private const P = 'cartaporte31';

    public function soporta(CartaPorteMercancias $mercancias): bool
    {
        return $mercancias->Autotransporte !== null;
    }

    public function mapear(\DOMDocument $doc, CartaPorteMercancias $mercancias): \DOMElement
    {
        $auto = $mercancias->Autotransporte;
        $node = $doc->createElementNS(self::NS, self::P . ':Autotransporte');

        $this->setRequiredAttr($node, 'PermSCT', $auto->PermSCT);
        $this->setRequiredAttr($node, 'NumPermisoSCT', $auto->NumPermisoSCT);

        if ($auto->IdentificacionVehicular !== null) {
            $iv = $auto->IdentificacionVehicular;
            $ivNode = $doc->createElementNS(self::NS, self::P . ':IdentificacionVehicular');
            $this->setRequiredAttr($ivNode, 'ConfigVehicular', $iv->ConfigVehicular);
            $this->setRequiredAttr($ivNode, 'PesoBrutoVehicular', $this->formatDecimal($iv->PesoBrutoVehicular) ?? '');
            $this->setRequiredAttr($ivNode, 'PlacaVM', $iv->PlacaVM);
            $this->setRequiredAttr($ivNode, 'AnioModeloVM', $iv->AnioModeloVM);
            $node->appendChild($ivNode);
        }

        if ($auto->Seguros !== null) {
            $s = $auto->Seguros;
            $sNode = $doc->createElementNS(self::NS, self::P . ':Seguros');
            $this->setRequiredAttr($sNode, 'AseguraRespCivil', $s->AseguraRespCivil);
            $this->setRequiredAttr($sNode, 'PolizaRespCivil', $s->PolizaRespCivil);
            $this->setAttr($sNode, 'AseguraMedAmbiente', $s->AseguraMedAmbiente);
            $this->setAttr($sNode, 'PolizaMedAmbiente', $s->PolizaMedAmbiente);
            $this->setAttr($sNode, 'AseguraCarga', $s->AseguraCarga);
            $this->setAttr($sNode, 'PolizaCarga', $s->PolizaCarga);
            $this->setAttr($sNode, 'PrimaSeguro', $this->formatDecimal($s->PrimaSeguro));
            $node->appendChild($sNode);
        }

        if (!empty($auto->Remolques)) {
            $remolquesNode = $doc->createElementNS(self::NS, self::P . ':Remolques');
            foreach ($auto->Remolques as $remolque) {
                $remolquesNode->appendChild($this->crearRemolque($doc, $remolque));
            }
            $node->appendChild($remolquesNode);
        }

        return $node;
    }

    private function crearRemolque(\DOMDocument $doc, CartaPorteRemolque $remolque): \DOMElement
    {
        $node = $doc->createElementNS(self::NS, self::P . ':Remolque');
        $this->setRequiredAttr($node, 'SubTipoRem', $remolque->SubTipoRem);
        $this->setRequiredAttr($node, 'Placa', $remolque->Placa);
        return $node;
    }
}