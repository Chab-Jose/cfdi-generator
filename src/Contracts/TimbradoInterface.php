<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

interface TimbradoInterface
{
    /**
     * Envía el XML sellado al PAC y regresa el XML ya timbrado
     * (con TimbreFiscalDigital incluido).
     *
     * @throws \RuntimeException si el PAC rechaza el timbrado
     */
    public function timbrar(string $xmlSellado): string;
}