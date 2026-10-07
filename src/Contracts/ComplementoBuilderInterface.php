<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

interface ComplementoBuilderInterface
{
    public function soporta(object $complemento): bool;

    public function build(object $complemento): object;
}