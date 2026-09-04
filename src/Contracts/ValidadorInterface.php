<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Comprobante;

interface ValidadorInterface
{
    /**
     * Valida los campos requeridos según el esquema/reglas del SAT.
     * No valida contra el XSD (eso es responsabilidad de otra capa),
     * solo reglas de negocio (nullability, formatos, catálogos).
     */
    public function validar(Comprobante $comprobante): bool;

    /** @return string[] */
    public function obtenerErrores(): array;
}