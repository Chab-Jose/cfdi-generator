<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos;

use ChabJose\CfdiGenerator\Contracts\ComplementoXmlMapperInterface;
use ChabJose\CfdiGenerator\Exceptions\ComplementoNoRegistradoException;

class ComplementoRegistry
{
    /** @var ComplementoXmlMapperInterface[] */
    private array $mappers = [];

    public function registrar(ComplementoXmlMapperInterface $mapper): self
    {
        $this->mappers[] = $mapper;
        return $this;
    }

    public function encontrarPara(object $complemento): ComplementoXmlMapperInterface
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->soporta($complemento)) {
                return $mapper;
            }
        }

        throw new ComplementoNoRegistradoException(
            'No hay un XmlMapper registrado para el complemento: ' . get_class($complemento)
        );
    }
}