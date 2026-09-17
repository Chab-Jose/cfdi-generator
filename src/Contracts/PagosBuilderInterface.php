<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;

interface PagosBuilderInterface
{
    public function build(Pagos $pagos): Pagos;
}