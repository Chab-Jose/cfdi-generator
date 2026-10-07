<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Maritimo;

class CartaPorteContenedorMaritimo
{
    public string $TipoContenedor = '';
    public ?string $MatriculaContenedor = null;
    public ?string $NumPrecinto = null;
    public ?string $IdCCPRelacionado = null;
    public ?string $PlacaVMCCP = null;
    public ?string $FechaCertificacionCCP = null;

    /** @var CartaPorteRemolqueCCP[] */
    public array $RemolquesCCP = [];

    public function addRemolqueCCP(CartaPorteRemolqueCCP $remolque): self
    {
        $this->RemolquesCCP[] = $remolque;
        return $this;
    }
}