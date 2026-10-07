<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte;

use ChabJose\CfdiGenerator\Contracts\MedioTransporteXmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Maritimo\CartaPorteContenedorMaritimo;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Maritimo\CartaPorteRemolqueCCP;
use ChabJose\CfdiGenerator\Utils\Xml\XmlAttributeHelpersTrait;

class MaritimoXmlMapper implements MedioTransporteXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS = 'http://www.sat.gob.mx/CartaPorte31';
    private const P = 'cartaporte31';

    public function soporta(CartaPorteMercancias $mercancias): bool
    {
        return $mercancias->TransporteMaritimo !== null;
    }

    public function mapear(\DOMDocument $doc, CartaPorteMercancias $mercancias): \DOMElement
    {
        $m = $mercancias->TransporteMaritimo;
        $node = $doc->createElementNS(self::NS, self::P . ':TransporteMaritimo');

        $this->setAttr($node, 'PermSCT', $m->PermSCT);
        $this->setAttr($node, 'NumPermisoSCT', $m->NumPermisoSCT);
        $this->setAttr($node, 'NombreAseg', $m->NombreAseg);
        $this->setAttr($node, 'NumPolizaSeguro', $m->NumPolizaSeguro);
        $this->setRequiredAttr($node, 'TipoEmbarcacion', $m->TipoEmbarcacion);
        $this->setRequiredAttr($node, 'Matricula', $m->Matricula);
        $this->setRequiredAttr($node, 'NumeroOMI', $m->NumeroOMI);
        $this->setAttr($node, 'AnioEmbarcacion', $m->AnioEmbarcacion);
        $this->setAttr($node, 'NombreEmbarc', $m->NombreEmbarc);
        $this->setRequiredAttr($node, 'NacionalidadEmbarc', $m->NacionalidadEmbarc);
        $this->setRequiredAttr($node, 'UnidadesDeArqBruto', $this->formatDecimal($m->UnidadesDeArqBruto) ?? '');
        $this->setRequiredAttr($node, 'TipoCarga', $m->TipoCarga);
        $this->setAttr($node, 'Eslora', $this->formatDecimal($m->Eslora));
        $this->setAttr($node, 'Manga', $this->formatDecimal($m->Manga));
        $this->setAttr($node, 'Calado', $this->formatDecimal($m->Calado));
        $this->setAttr($node, 'Puntal', $this->formatDecimal($m->Puntal));
        $this->setAttr($node, 'LineaNaviera', $m->LineaNaviera);
        $this->setRequiredAttr($node, 'NombreAgenteNaviero', $m->NombreAgenteNaviero);
        $this->setRequiredAttr($node, 'NumAutorizacionNaviero', $m->NumAutorizacionNaviero);
        $this->setAttr($node, 'NumViaje', $m->NumViaje);
        $this->setAttr($node, 'NumConocEmbarc', $m->NumConocEmbarc);
        $this->setAttr($node, 'PermisoTempNavegacion', $m->PermisoTempNavegacion);

        foreach ($m->Contenedor as $contenedor) {
            $node->appendChild($this->crearContenedor($doc, $contenedor));
        }

        return $node;
    }

    private function crearContenedor(\DOMDocument $doc, CartaPorteContenedorMaritimo $c): \DOMElement
    {
        $node = $doc->createElementNS(self::NS, self::P . ':Contenedor');
        $this->setRequiredAttr($node, 'TipoContenedor', $c->TipoContenedor);
        $this->setAttr($node, 'MatriculaContenedor', $c->MatriculaContenedor);
        $this->setAttr($node, 'NumPrecinto', $c->NumPrecinto);
        $this->setAttr($node, 'IdCCPRelacionado', $c->IdCCPRelacionado);
        $this->setAttr($node, 'PlacaVMCCP', $c->PlacaVMCCP);
        $this->setAttr($node, 'FechaCertificacionCCP', $c->FechaCertificacionCCP);

        foreach ($c->RemolquesCCP as $remolque) {
            $node->appendChild($this->crearRemolqueCCP($doc, $remolque));
        }

        return $node;
    }

    private function crearRemolqueCCP(\DOMDocument $doc, CartaPorteRemolqueCCP $r): \DOMElement
    {
        $node = $doc->createElementNS(self::NS, self::P . ':RemolquesCCP');
        $this->setRequiredAttr($node, 'SubTipoRemCCP', $r->SubTipoRemCCP);
        $this->setRequiredAttr($node, 'PlacaCCP', $r->PlacaCCP);
        return $node;
    }
}