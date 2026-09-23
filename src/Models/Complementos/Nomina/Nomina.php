<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class Nomina
{
    public string $Version = '1.2';
    public string $TipoNomina = '';
    public string $FechaPago = '';
    public string $FechaInicialPago = '';
    public string $FechaFinalPago = '';
    public float $NumDiasPagados = 0.0;

    public ?float $TotalPercepciones = null;
    public ?float $TotalDeducciones = null;
    public ?float $TotalOtrosPagos = null;

    public ?NominaEmisor $Emisor = null;
    public ?NominaReceptor $Receptor = null;
    public ?NominaPercepciones $Percepciones = null;
    public ?NominaDeducciones $Deducciones = null;

    /** @var NominaOtroPago[] */
    public array $OtrosPagos = [];

    /** @var NominaIncapacidad[] */
    public array $Incapacidades = [];

    public function addOtroPago(NominaOtroPago $otroPago): self
    {
        $this->OtrosPagos[] = $otroPago;
        return $this;
    }

    public function addIncapacidad(NominaIncapacidad $incapacidad): self
    {
        $this->Incapacidades[] = $incapacidad;
        return $this;
    }
}