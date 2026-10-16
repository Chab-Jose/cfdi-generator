<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\HidroYPetro;

use ChabJose\CfdiGenerator\Contracts\ComplementoConceptoBuilderInterface;
use ChabJose\CfdiGenerator\Models\Complementos\HidroYPetro\HidroYPetro;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

class HidroYPetroComplementoBuilderAdapter implements ComplementoConceptoBuilderInterface
{
    public function __construct(private HidroYPetroValidator $validator = new HidroYPetroValidator())
    {
    }

    public function soporta(object $complemento): bool
    {
        return $complemento instanceof HidroYPetro;
    }

    public function build(object $complemento, ComprobanteConcepto $concepto, Comprobante $comprobante): object
    {
        $this->validator->validar($complemento, $concepto, $comprobante);

        return $complemento;
    }
}