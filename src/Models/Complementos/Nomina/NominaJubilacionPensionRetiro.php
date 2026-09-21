<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaJubilacionPensionRetiro
{
    public ?float $TotalUnaExhibicion = null;
    public ?float $TotalParcialidad = null;
    public ?float $MontoDiario = null;
    public float $IngresoAcumulable = 0.0;
    public float $IngresoNoAcumulable = 0.0;
}