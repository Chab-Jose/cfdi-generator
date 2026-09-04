<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Exceptions;

class ComprobanteValidationException extends \RuntimeException
{
    /** @var string[] */
    private array $errores;

    public function __construct(array $errores)
    {
        $this->errores = $errores;
        parent::__construct('Comprobante inválido: ' . implode(' | ', $errores));
    }

    /** @return string[] */
    public function getErrores(): array
    {
        return $this->errores;
    }
}