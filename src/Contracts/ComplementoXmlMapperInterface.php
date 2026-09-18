<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

interface ComplementoXmlMapperInterface
{
    /** Indica si este mapper sabe procesar el objeto dado. */
    public function soporta(object $complemento): bool;

    /** Construye el nodo XML raíz de este complemento (ej. pago20:Pagos). */
    public function toXmlElement(\DOMDocument $doc, object $complemento): \DOMElement;

    public function namespacePrefix(): string;
    public function namespaceUri(): string;
    public function schemaLocation(): string;
}