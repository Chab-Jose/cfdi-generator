<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

interface IdCcpGeneratorInterface
{
    public function generar(): string;
}