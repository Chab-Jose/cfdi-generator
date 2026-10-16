<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

interface ComplementoConceptoBuilderInterface
{
    public function soporta(object $complemento): bool;

    public function build(object $complemento, ComprobanteConcepto $concepto, Comprobante $comprobante): object;
}