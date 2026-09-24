<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteAutotransporte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Maritimo\CartaPorteTransporteMaritimo;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Aereo\CartaPorteTransporteAereo;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Ferroviario\CartaPorteTransporteFerroviario;

class CartaPorteMercancias
{
    public float $PesoBrutoTotal = 0.0;
    public string $UnidadPeso = '';
    public ?float $PesoNetoTotal = null;
    public int $NumTotalMercancias = 0;
    public ?float $CargoPorTasacion = null;
    public ?string $LogisticaInversaRecoleccionDevolucion = null;

    /** @var CartaPorteMercancia[] */
    public array $Mercancia = [];

    // Mutuamente excluyentes según el medio de transporte real
    public ?CartaPorteAutotransporte $Autotransporte = null;
    public ?CartaPorteTransporteMaritimo $TransporteMaritimo = null;
    public ?CartaPorteTransporteAereo $TransporteAereo = null;
    public ?CartaPorteTransporteFerroviario $TransporteFerroviario = null;

    public function addMercancia(CartaPorteMercancia $mercancia): self
    {
        $this->Mercancia[] = $mercancia;
        return $this;
    }
}