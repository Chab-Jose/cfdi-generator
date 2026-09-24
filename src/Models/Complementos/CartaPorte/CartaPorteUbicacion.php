<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorteUbicacion
{
    public string $TipoUbicacion = '';
    public ?string $IDUbicacion = null;
    public string $RFCRemitenteDestinatario = '';
    public ?string $NombreRemitenteDestinatario = null;
    public ?string $NumRegIdTrib = null;
    public ?string $ResidenciaFiscal = null;
    public ?string $NumEstacion = null;
    public ?string $NombreEstacion = null;
    public ?string $NavegacionTrafico = null;
    public string $FechaHoraSalidaLlegada = '';
    public ?string $TipoEstacion = null;
    public ?float $DistanciaRecorrida = null;

    public ?CartaPorteDomicilio $Domicilio = null;
}