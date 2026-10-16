<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos;

use ChabJose\CfdiGenerator\Contracts\ComplementoConceptoBuilderInterface;

class ComplementoConceptoBuilderRegistry
{
    /** @var ComplementoConceptoBuilderInterface[] */
    private array $builders = [];

    public function registrar(ComplementoConceptoBuilderInterface $builder): self
    {
        $this->builders[] = $builder;
        return $this;
    }

    public function encontrarPara(object $complemento): ?ComplementoConceptoBuilderInterface
    {
        foreach ($this->builders as $builder) {
            if ($builder->soporta($complemento)) {
                return $builder;
            }
        }

        return null;
    }
}