<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Nomina;

class NominaPercepcion
{
    public string $TipoPercepcion = '';
    public string $Clave = '';
    public string $Concepto = '';
    public float $ImporteGravado = 0.0;
    public float $ImporteExento = 0.0;

    public ?NominaPercepcionAccionesOTitulos $AccionesOTitulos = null;

    /** @var NominaPercepcionHorasExtra[] */
    public array $HorasExtra = [];

    public function addHorasExtra(NominaPercepcionHorasExtra $horasExtra): self
    {
        $this->HorasExtra[] = $horasExtra;
        return $this;
    }
}