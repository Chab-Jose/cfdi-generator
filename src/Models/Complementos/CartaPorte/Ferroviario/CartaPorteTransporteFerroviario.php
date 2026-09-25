<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Ferroviario;

class CartaPorteTransporteFerroviario
{
    public string $TipoDeServicio = '';
    public string $TipoDeTrafico = '';
    public ?string $NombreAseg = null;
    public ?string $NumPolizaSeguro = null;

    /** @var CartaPorteDerechosDePaso[] */
    public array $DerechosDePaso = [];

    /** @var CartaPorteCarro[] */
    public array $Carro = [];

    public function addDerechosDePaso(CartaPorteDerechosDePaso $derecho): self
    {
        $this->DerechosDePaso[] = $derecho;
        return $this;
    }

    public function addCarro(CartaPorteCarro $carro): self
    {
        $this->Carro[] = $carro;
        return $this;
    }
}