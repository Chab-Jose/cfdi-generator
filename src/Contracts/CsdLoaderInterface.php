<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Domain\CsdCredential;

interface CsdLoaderInterface
{
    public function cargar(string $rutaCer, string $rutaKey, string $password): CsdCredential;
}