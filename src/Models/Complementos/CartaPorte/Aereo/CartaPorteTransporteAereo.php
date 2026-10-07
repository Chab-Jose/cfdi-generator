<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Aereo;

class CartaPorteTransporteAereo
{
    public string $PermSCT = '';
    public string $NumPermisoSCT = '';
    public ?string $MatriculaAeronave = null;
    public ?string $NombreAseg = null;
    public ?string $NumPolizaSeguro = null;
    public string $NumeroGuia = '';
    public ?string $LugarContrato = null;
    public string $CodigoTransportista = '';
    public ?string $RFCEmbarcador = null;
    public ?string $NumRegIdTribEmbarc = null;
    public ?string $ResidenciaFiscalEmbarc = null;
    public ?string $NombreEmbarcador = null;
}