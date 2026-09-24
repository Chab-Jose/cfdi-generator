<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorte
{
    public string $Version = '3.1';
    public string $IdCCP = '';
    public string $TranspInternac = '';

    public ?string $EntradaSalidaMerc = null;
    public ?string $PaisOrigenDestino = null;
    public ?string $ViaEntradaSalida = null;
    public ?float $TotalDistRec = null;
    public ?string $RegistroISTMO = null;
    public ?string $UbicacionPoloOrigen = null;
    public ?string $UbicacionPoloDestino = null;

    /** @var CartaPorteRegimenAduaneroCCP[] */
    public array $RegimenesAduaneros = [];

    /** @var CartaPorteUbicacion[] */
    public array $Ubicaciones = [];

    public ?CartaPorteMercancias $Mercancias = null;

    /** @var CartaPorteFiguraTransporte[] */
    public array $FiguraTransporte = [];

    public function addRegimenAduanero(CartaPorteRegimenAduaneroCCP $regimen): self
    {
        $this->RegimenesAduaneros[] = $regimen;
        return $this;
    }

    public function addUbicacion(CartaPorteUbicacion $ubicacion): self
    {
        $this->Ubicaciones[] = $ubicacion;
        return $this;
    }

    public function addFiguraTransporte(CartaPorteFiguraTransporte $figura): self
    {
        $this->FiguraTransporte[] = $figura;
        return $this;
    }
}