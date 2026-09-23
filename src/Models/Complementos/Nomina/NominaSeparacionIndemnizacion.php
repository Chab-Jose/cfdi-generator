<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaSeparacionIndemnizacion
{
    public float $TotalPagado = 0.0;
    public int $NumAñosServicio = 0;
    public float $UltimoSueldoMensOrd = 0.0;
    public float $IngresoAcumulable = 0.0;
    public float $IngresoNoAcumulable = 0.0;
}