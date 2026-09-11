<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

interface CadenaOriginalServiceInterface
{
    public function generar(string $xmlSinSello): string;
}