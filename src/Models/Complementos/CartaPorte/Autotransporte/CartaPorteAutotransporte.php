<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte;

class CartaPorteAutotransporte
{
    public string $PermSCT = '';
    public string $NumPermisoSCT = '';

    public ?CartaPorteIdentificacionVehicular $IdentificacionVehicular = null;
    public ?CartaPorteSeguros $Seguros = null;

    /** @var CartaPorteRemolque[] */
    public array $Remolques = [];

    public function addRemolque(CartaPorteRemolque $remolque): self
    {
        $this->Remolques[] = $remolque;
        return $this;
    }
}