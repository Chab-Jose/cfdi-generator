<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Iedu;

use ChabJose\CfdiGenerator\Contracts\ComplementoXmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Iedu\InstEducativas;
use ChabJose\CfdiGenerator\Utils\Xml\XmlAttributeHelpersTrait;

class InstEducativasXmlMapper implements ComplementoXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS_URI = 'http://www.sat.gob.mx/iedu';
    private const NS_PREFIX = 'iedu';
    private const XSD_LOCATION = 'http://www.sat.gob.mx/sitio_internet/cfd/iedu/iedu.xsd';

    public function soporta(object $complemento): bool
    {
        return $complemento instanceof InstEducativas;
    }

    public function toXmlElement(\DOMDocument $doc, object $complemento): \DOMElement
    {
        /** @var InstEducativas $complemento */
        $nodo = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':instEducativas');

        $this->setRequiredAttr($nodo, 'version', $complemento->version);
        $this->setRequiredAttr($nodo, 'nombreAlumno', $complemento->nombreAlumno);
        $this->setRequiredAttr($nodo, 'CURP', $complemento->CURP);
        $this->setRequiredAttr($nodo, 'nivelEducativo', $complemento->nivelEducativo);
        $this->setRequiredAttr($nodo, 'autRVOE', $complemento->autRVOE);
        $this->setAttr($nodo, 'rfcPago', $complemento->rfcPago);

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