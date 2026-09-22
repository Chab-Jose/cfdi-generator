<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Contracts\DeduccionesCalculatorInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeduccion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeducciones;

class NominaDeduccionesCalculator implements DeduccionesCalculatorInterface
{
    private const ISR = '002';

    public function calcular(NominaDeducciones $deducciones): NominaDeducciones
    {
        $isr = array_filter($deducciones->Deduccion, fn(NominaDeduccion $d) => $d->TipoDeduccion === self::ISR);
        $otras = array_filter($deducciones->Deduccion, fn(NominaDeduccion $d) => $d->TipoDeduccion !== self::ISR);

        $deducciones->TotalImpuestosRetenidos = empty($isr)
            ? null
            : $this->redondear(array_sum(array_map(fn(NominaDeduccion $d) => $d->Importe, $isr)));

        $deducciones->TotalOtrasDeducciones = empty($otras)
            ? null
            : $this->redondear(array_sum(array_map(fn(NominaDeduccion $d) => $d->Importe, $otras)));

        return $deducciones;
    }

    private function redondear(float $valor): float
    {
        return round($valor, 2, PHP_ROUND_HALF_UP);
    }
}