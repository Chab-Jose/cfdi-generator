<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\HidroYPetro;

use ChabJose\CfdiGenerator\Contracts\ComplementoXmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\HidroYPetro\HidroYPetro;
use ChabJose\CfdiGenerator\Utils\Xml\XmlAttributeHelpersTrait;

class HidroYPetroXmlMapper implements ComplementoXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS_URI = 'http://www.sat.gob.mx/hidrocarburospetroliferos';
    private const NS_PREFIX = 'hidrocarburospetroliferos';
    private const XSD_LOCATION = 'http://www.sat.gob.mx/sitio_internet/cfd/hidrocarburospetroliferos.xsd';

    public function soporta(object $complemento): bool
    {
        return $complemento instanceof HidroYPetro;
    }

    public function toXmlElement(\DOMDocument $doc, object $complemento): \DOMElement
    {
        /** @var HidroYPetro $complemento */
        $nodo = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':HidroYPetro');

        $this->setRequiredAttr($nodo, 'Version', $complemento->Version);
        $this->setRequiredAttr($nodo, 'TipoPermiso', $complemento->TipoPermiso);
        $this->setRequiredAttr($nodo, 'NumeroPermiso', $complemento->NumeroPermiso);
        $this->setRequiredAttr($nodo, 'ClaveHYP', $complemento->ClaveHYP);
        $this->setRequiredAttr($nodo, 'SubProductoHYP', $complemento->SubProductoHYP);

        return $nodo;
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
        return self::NS_URI . ' ' . self::XSD_LOCATION;
    }
}