<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte;

use ChabJose\CfdiGenerator\Contracts\MedioTransporteXmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Ferroviario\CartaPorteCarro;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Ferroviario\CartaPorteContenedorFerroviario;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Ferroviario\CartaPorteDerechosDePaso;
use ChabJose\CfdiGenerator\Utils\Xml\XmlAttributeHelpersTrait;

class FerroviarioXmlMapper implements MedioTransporteXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS = 'http://www.sat.gob.mx/CartaPorte31';
    private const P = 'cartaporte31';

    public function soporta(CartaPorteMercancias $mercancias): bool
    {
        return $mercancias->TransporteFerroviario !== null;
    }

    public function mapear(\DOMDocument $doc, CartaPorteMercancias $mercancias): \DOMElement
    {
        $f = $mercancias->TransporteFerroviario;
        $node = $doc->createElementNS(self::NS, self::P . ':TransporteFerroviario');

        $this->setRequiredAttr($node, 'TipoDeServicio', $f->TipoDeServicio);
        $this->setRequiredAttr($node, 'TipoDeTrafico', $f->TipoDeTrafico);
        $this->setAttr($node, 'NombreAseg', $f->NombreAseg);
        $this->setAttr($node, 'NumPolizaSeguro', $f->NumPolizaSeguro);

        foreach ($f->DerechosDePaso as $derecho) {
            $node->appendChild($this->crearDerechosDePaso($doc, $derecho));
        }

        foreach ($f->Carro as $carro) {
            $node->appendChild($this->crearCarro($doc, $carro));
        }

        return $node;
    }

    private function crearDerechosDePaso(\DOMDocument $doc, CartaPorteDerechosDePaso $d): \DOMElement
    {
        $node = $doc->createElementNS(self::NS, self::P . ':DerechosDePaso');
        $this->setRequiredAttr($node, 'TipoDerechoDePaso', $d->TipoDerechoDePaso);
        $this->setRequiredAttr($node, 'KilometrajePagado', $this->formatDecimal($d->KilometrajePagado) ?? '');
        return $node;
    }

    private function crearCarro(\DOMDocument $doc, CartaPorteCarro $c): \DOMElement
    {
        $node = $doc->createElementNS(self::NS, self::P . ':Carro');
        $this->setRequiredAttr($node, 'TipoCarro', $c->TipoCarro);
        $this->setRequiredAttr($node, 'MatriculaCarro', $c->MatriculaCarro);
        $this->setRequiredAttr($node, 'GuiaCarro', $c->GuiaCarro);
        $this->setRequiredAttr($node, 'ToneladasNetasCarro', $this->formatDecimal($c->ToneladasNetasCarro) ?? '');

        foreach ($c->Contenedor as $contenedor) {
            $node->appendChild($this->crearContenedor($doc, $contenedor));
        }

        return $node;
    }

    private function crearContenedor(\DOMDocument $doc, CartaPorteContenedorFerroviario $c): \DOMElement
    {
        $node = $doc->createElementNS(self::NS, self::P . ':Contenedor');
        $this->setRequiredAttr($node, 'TipoContenedor', $c->TipoContenedor);
        $this->setRequiredAttr($node, 'PesoContenedorVacio', $this->formatDecimal($c->PesoContenedorVacio) ?? '');
        $this->setRequiredAttr($node, 'PesoNetoMercancia', $this->formatDecimal($c->PesoNetoMercancia) ?? '');
        return $node;
    }
}