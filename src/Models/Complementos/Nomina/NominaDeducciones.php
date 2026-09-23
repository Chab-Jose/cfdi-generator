<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaDeducciones
{
    public ?float $TotalOtrasDeducciones = null;
    public ?float $TotalImpuestosRetenidos = null;

    /** @var NominaDeduccion[] */
    public array $Deduccion = [];

    public function addDeduccion(NominaDeduccion $deduccion): self
    {
        $this->Deduccion[] = $deduccion;
        return $this;
    }
}