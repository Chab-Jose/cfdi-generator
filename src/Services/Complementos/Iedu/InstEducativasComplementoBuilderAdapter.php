<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Iedu;

use ChabJose\CfdiGenerator\Contracts\ComplementoConceptoBuilderInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Iedu\InstEducativas;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;

class InstEducativasComplementoBuilderAdapter implements ComplementoConceptoBuilderInterface
{
    public function __construct(private InstEducativasValidator $validator = new InstEducativasValidator())
    {
    }

    public function soporta(object $complemento): bool
    {
        return $complemento instanceof InstEducativas;
    }

    public function build(object $complemento, ComprobanteConcepto $concepto, Comprobante $comprobante): object
    {
        $this->validator->validar($complemento);

        return $complemento;
    }
}