<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos;

use ChabJose\CfdiGenerator\Contracts\ComplementoBuilderInterface;

class ComplementoBuilderRegistry
{
    /** @var ComplementoBuilderInterface[] */
    private array $builders = [];

    public function registrar(ComplementoBuilderInterface $builder): self
    {
        $this->builders[] = $builder;
        return $this;
    }

    /** Busca un builder para el complemento; regresa null si no hay ninguno (no todos los complementos necesitan cálculo). */
    public function encontrarPara(object $complemento): ?ComplementoBuilderInterface
    {
        foreach ($this->builders as $builder) {
            if ($builder->soporta($complemento)) {
                return $builder;
            }
        }

        return null;
    }
}