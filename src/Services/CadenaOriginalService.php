<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\CadenaOriginalServiceInterface;
use ChabJose\CfdiGenerator\Exceptions\CadenaOriginalException;

final class CadenaOriginalService implements CadenaOriginalServiceInterface
{
    private readonly string $xsltPath;

    public function __construct(?string $xsltPath = null)
    {
        // Default: el XSLT que empaquetas dentro del propio paquete
        $this->xsltPath = $xsltPath ?? dirname(__DIR__, 2) . '/resources/4.0/cadenaoriginal_4_0.xslt';

        if (!is_file($this->xsltPath)) {
            throw new CadenaOriginalException("No se encontró el XSLT en: {$this->xsltPath}");
        }
    }

    public function generar(string $xmlSinSello): string
    {
        $xsltDoc = new \DOMDocument();
        $xsltDoc->load($this->xsltPath);

        $xmlDoc = new \DOMDocument();

        libxml_use_internal_errors(true);
        $cargoCorrectamente = $xmlDoc->loadXML($xmlSinSello);
        $erroresXml = libxml_get_errors();
        libxml_clear_errors();

        if (!$cargoCorrectamente) {
            $detalle = implode('; ', array_map(fn($e) => trim($e->message), $erroresXml));
            throw new CadenaOriginalException("El XML proporcionado no es válido: {$detalle}");
        }

        $processor = new \XSLTProcessor();

        $cadena = $this->ejecutarSilenciandoAvisoConocidoDelSat(
            fn() => $this->transformar($processor, $xsltDoc, $xmlDoc)
        );

        if ($cadena === false) {
            throw new CadenaOriginalException('Falló la transformación XSLT al generar la cadena original.');
        }

        return $cadena;
    }

    private function transformar(\XSLTProcessor $processor, \DOMDocument $xsltDoc, \DOMDocument $xmlDoc): string|false
    {
        $processor->importStylesheet($xsltDoc);

        return $processor->transformToXml($xmlDoc);
    }

    private function ejecutarSilenciandoAvisoConocidoDelSat(callable $operacion): mixed
    {
        set_error_handler(function (int $severity, string $mensaje): bool {
            $esAvisoConocido = str_contains($mensaje, 'only 1.1 features are supported')
                || str_contains($mensaje, 'element stylesheet');

            return $esAvisoConocido; // true = silenciado; false = comportamiento normal de PHP
        }, E_WARNING);

        try {
            return $operacion();
        } finally {
            restore_error_handler();
        }
    }
}
