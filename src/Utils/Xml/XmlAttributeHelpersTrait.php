<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Utils\Xml;

trait XmlAttributeHelpersTrait
{
    use NormalizaValorCatalogoTrait;

    private function setAttr(\DOMElement $nodo, string $atributo, mixed $valor): void
    {
        $valor = $this->valorEscalar($valor);

        if ($valor === null || $valor === '') {
            return;
        }

        $nodo->setAttribute($atributo, $valor);
    }

    private function setRequiredAttr(\DOMElement $nodo, string $atributo, mixed $valor): void
    {
        $valor = $this->valorEscalar($valor);

        if ($valor === null || $valor === '') {
            throw new \RuntimeException(
                "El atributo requerido '{$atributo}' no puede estar vacío."
            );
        }

        $nodo->setAttribute($atributo, $valor);
    }
    protected function formatDecimal(?float $value, int $decimales = 2): ?string
    {
        return $value === null ? null : number_format($value, $decimales, '.', '');
    }
}