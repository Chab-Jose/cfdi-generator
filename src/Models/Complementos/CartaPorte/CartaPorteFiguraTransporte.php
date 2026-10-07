<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorteFiguraTransporte
{
    /** @var CartaPorteTipoFigura[] */
    public array $TiposFigura = [];

    public function addTipoFigura(CartaPorteTipoFigura $figura): self
    {
        $this->TiposFigura[] = $figura;
        return $this;
    }
}