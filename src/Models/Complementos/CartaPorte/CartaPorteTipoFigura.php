<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorteTipoFigura
{
    public string $TipoFigura = '';
    public ?string $RFCFigura = null;
    public ?string $NumLicencia = null;
    public string $NombreFigura = '';
    public ?string $NumRegIdTribFigura = null;
    public ?string $ResidenciaFiscalFigura = null;

    /** @var CartaPorteParteTransporte[] */
    public array $PartesTransporte = [];

    public ?CartaPorteDomicilio $Domicilio = null;

    public function addParteTransporte(CartaPorteParteTransporte $parte): self
    {
        $this->PartesTransporte[] = $parte;
        return $this;
    }
}