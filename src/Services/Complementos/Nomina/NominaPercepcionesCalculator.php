<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Contracts\PercepcionesCalculatorInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepciones;

class NominaPercepcionesCalculator implements PercepcionesCalculatorInterface
{
    private const SUELDOS = '001';
    private const JUBILACION_UNA_EXHIBICION = '039';
    private const JUBILACION_PARCIALIDADES = '044';

    public function calcular(NominaPercepciones $percepciones): NominaPercepciones
    {
        $percepciones->TotalGravado = $this->redondear(
            array_sum(array_map(fn(NominaPercepcion $p) => $p->ImporteGravado, $percepciones->Percepcion))
        );

        $percepciones->TotalExento = $this->redondear(
            array_sum(array_map(fn(NominaPercepcion $p) => $p->ImporteExento, $percepciones->Percepcion))
        );

        $percepciones->TotalSueldos = $this->sumarPorClave($percepciones->Percepcion, [self::SUELDOS]);

        $percepciones->TotalJubilacionPensionRetiro = $this->sumarPorClave(
            $percepciones->Percepcion,
            [self::JUBILACION_UNA_EXHIBICION, self::JUBILACION_PARCIALIDADES],
        );

        if ($percepciones->SeparacionIndemnizacion !== null) {
            $percepciones->TotalSeparacionIndemnizacion = $this->redondear(
                $percepciones->SeparacionIndemnizacion->TotalPagado
            );
        }

        return $percepciones;
    }

    /**
     * Suma ImporteGravado + ImporteExento de las percepciones cuyo TipoPercepcion
     * coincida con alguna de las claves dadas. Por diseño, cada percepción tiene
     * una sola clave, así que las claves de Sueldos (001) y Jubilación (039/044)
     * nunca se solapan entre sí — la exclusión mutua exigida por el SAT entre
     * TotalSueldos y TotalJubilacionPensionRetiro se cumple naturalmente.
     */
    private function sumarPorClave(array $percepcionesLista, array $claves): ?float
    {
        $filtradas = array_filter(
            $percepcionesLista,
            fn(NominaPercepcion $p) => in_array($p->TipoPercepcion, $claves, true)
        );

        if (empty($filtradas)) {
            return null;
        }

        $total = array_sum(array_map(fn(NominaPercepcion $p) => $p->ImporteGravado + $p->ImporteExento, $filtradas));

        return $this->redondear($total);
    }

    private function redondear(float $valor): float
    {
        return round($valor, 2, PHP_ROUND_HALF_UP);
    }
}