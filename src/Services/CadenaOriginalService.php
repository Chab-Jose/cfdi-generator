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
        $this->xsltPath = $xsltPath ?? dirname(__DIR__, 2) . '/resources/xslt/4.0/cadenaoriginal_4_0.xslt';

        if (!is_file($this->xsltPath)) {
            throw new CadenaOriginalException("No se encontró el XSLT en: {$this->xsltPath}");
        }
    }

    public function generar(string $xmlSinSello): string
    {
        $xsltDoc = new \DOMDocument();
        $xsltDoc->load($this->xsltPath);

        $xmlDoc = new \DOMDocument();
        $xmlDoc->loadXML($xmlSinSello);

        $processor = new \XSLTProcessor();
        $processor->importStylesheet($xsltDoc);

        $cadena = $processor->transformToXml($xmlDoc);

        if ($cadena === false) {
            throw new CadenaOriginalException('Falló la transformación XSLT al generar la cadena original.');
        }

        return $cadena;
    }
}