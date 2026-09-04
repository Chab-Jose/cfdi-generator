<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Domain\CsdCredential;

interface SelladorInterface
{
    public function generarCadenaOriginal(string $xmlSinSello): string;

    public function sellar(string $cadenaOriginal, CsdCredential $csd): string;
}