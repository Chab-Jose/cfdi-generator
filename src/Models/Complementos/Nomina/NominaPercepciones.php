<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaPercepciones
{
    public ?float $TotalSueldos = null;
    public ?float $TotalSeparacionIndemnizacion = null;
    public ?float $TotalJubilacionPensionRetiro = null;
    public float $TotalGravado = 0.0;
    public float $TotalExento = 0.0;

    /** @var NominaPercepcion[] */
    public array $Percepcion = [];

    public ?NominaJubilacionPensionRetiro $JubilacionPensionRetiro = null;
    public ?NominaSeparacionIndemnizacion $SeparacionIndemnizacion = null;

    public function addPercepcion(NominaPercepcion $percepcion): self
    {
        $this->Percepcion[] = $percepcion;
        return $this;
    }
}