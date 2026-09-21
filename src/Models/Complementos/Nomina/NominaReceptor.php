<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaReceptor
{
    public string $Curp = '';
    public ?string $NumSeguridadSocial = null;
    public ?string $FechaInicioRelLaboral = null;
    public ?string $Antigüedad = null;
    public string $TipoContrato = '';
    public ?string $Sindicalizado = null;
    public ?string $TipoJornada = null;
    public string $TipoRegimen = '';
    public string $NumEmpleado = '';
    public ?string $Departamento = null;
    public ?string $Puesto = null;
    public ?string $RiesgoPuesto = null;
    public string $PeriodicidadPago = '';
    public ?string $Banco = null;
    public ?string $CuentaBancaria = null;
    public ?float $SalarioBaseCotApor = null;
    public ?float $SalarioDiarioIntegrado = null;
    public string $ClaveEntFed = '';

    /** @var NominaReceptorSubContratacion[] */
    public array $SubContratacion = [];

    public function addSubContratacion(NominaReceptorSubContratacion $sub): self
    {
        $this->SubContratacion[] = $sub;
        return $this;
    }
}