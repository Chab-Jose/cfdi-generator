<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Contracts\NominaTotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaOtroPago;

class NominaTotalesCalculator implements NominaTotalesCalculatorInterface
{
    public function calcular(Nomina $nomina): Nomina
    {
        if ($nomina->Percepciones !== null) {
            $nomina->TotalPercepciones = $this->redondear(
                $nomina->Percepciones->TotalGravado + $nomina->Percepciones->TotalExento
            );
        }

        if ($nomina->Deducciones !== null) {
            $totalOtras = $nomina->Deducciones->TotalOtrasDeducciones ?? 0.0;
            $totalIsr = $nomina->Deducciones->TotalImpuestosRetenidos ?? 0.0;

            $nomina->TotalDeducciones = ($totalOtras === 0.0 && $totalIsr === 0.0)
                ? null
                : $this->redondear($totalOtras + $totalIsr);
        }

        if (!empty($nomina->OtrosPagos)) {
            $nomina->TotalOtrosPagos = $this->redondear(
                array_sum(array_map(fn(NominaOtroPago $op) => $op->Importe, $nomina->OtrosPagos))
            );
        }

        return $nomina;
    }

    private function redondear(float $valor): float
    {
        return round($valor, 2, PHP_ROUND_HALF_UP);
    }
}