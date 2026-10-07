<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Exceptions;

class CartaPorteValidationException extends \RuntimeException
{
    /** @var string[] */
    private array $errores;

    public function __construct(array $errores)
    {
        $this->errores = $errores;
        parent::__construct('CartaPorte inválido: ' . implode(' | ', $errores));
    }

    /** @return string[] */
    public function getErrores(): array
    {
        return $this->errores;
    }
}