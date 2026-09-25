<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Ferroviario;

class CartaPorteCarro
{
    public string $TipoCarro = '';
    public string $MatriculaCarro = '';
    public string $GuiaCarro = '';
    public float $ToneladasNetasCarro = 0.0;

    /** @var CartaPorteContenedorFerroviario[] */
    public array $Contenedor = [];

    public function addContenedor(CartaPorteContenedorFerroviario $contenedor): self
    {
        $this->Contenedor[] = $contenedor;
        return $this;
    }
}