<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Utils\Xml;

trait XmlAttributeHelpersTrait
{
    protected function setAttr(\DOMElement $node, string $name, ?string $value): void
    {
        if ($value !== null && $value !== '') {
            $node->setAttribute($name, $value);
        }
    }

    protected function setRequiredAttr(\DOMElement $node, string $name, string $value): void
    {
        if ($value === '') {
            throw new \RuntimeException("El atributo requerido '{$name}' no puede estar vacío.");
        }

        $node->setAttribute($name, $value);
    }

    protected function formatDecimal(?float $value, int $decimales = 2): ?string
    {
        return $value === null ? null : number_format($value, $decimales, '.', '');
    }
}