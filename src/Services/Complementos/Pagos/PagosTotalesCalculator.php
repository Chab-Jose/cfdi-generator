<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Contracts\PagosTotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Exceptions\PagosCalculoException;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosTotales;

class PagosTotalesCalculator implements PagosTotalesCalculatorInterface
{
    private const IVA = '002';
    private const ISR = '001';
    private const IEPS = '003';
    private const MXN = 'MXN';

    public function calcular(Pagos $pagos): Pagos
    {
        $totales = new PagosTotales();
        $montoTotalMxn = 0.0;

        $acumuladores = [
            'retIVA' => 0.0, 'retISR' => 0.0, 'retIEPS' => 0.0,
            'base16' => 0.0, 'imp16' => 0.0,
            'base8' => 0.0, 'imp8' => 0.0,
            'base0' => 0.0, 'imp0' => 0.0,
            'baseExento' => 0.0,
        ];

        foreach ($pagos->Pago as $pago) {
            $factorConversion = $this->factorConversionAMxn($pago);
            $montoTotalMxn += $this->redondear($pago->Monto * $factorConversion);

            if ($pago->ImpuestosP === null) {
                continue;
            }

            foreach ($pago->ImpuestosP->RetencionesP as $retencion) {
                $importeMxn = $retencion->ImporteP * $factorConversion;
                match ($retencion->ImpuestoP) {
                    self::IVA => $acumuladores['retIVA'] += $importeMxn,
                    self::ISR => $acumuladores['retISR'] += $importeMxn,
                    self::IEPS => $acumuladores['retIEPS'] += $importeMxn,
                    default => null, // otros impuestos no tienen campo dedicado en Totales
                };
            }

            foreach ($pago->ImpuestosP->TrasladosP as $traslado) {
                if ($traslado->ImpuestoP !== self::IVA) {
                    continue; // Totales solo desglosa IVA por tasa
                }

                $baseMxn = $traslado->BaseP * $factorConversion;
                $importeMxn = ($traslado->ImporteP ?? 0.0) * $factorConversion;

                if ($traslado->TipoFactorP === 'Exento') {
                    $acumuladores['baseExento'] += $baseMxn;
                    continue;
                }

                $this->acumularPorTasa($acumuladores, (float) $traslado->TasaOCuotaP, $baseMxn, $importeMxn);
            }
        }

        $totales->TotalRetencionesIVA = $this->nuloSiCero($acumuladores['retIVA']);
        $totales->TotalRetencionesISR = $this->nuloSiCero($acumuladores['retISR']);
        $totales->TotalRetencionesIEPS = $this->nuloSiCero($acumuladores['retIEPS']);
        $totales->TotalTrasladosBaseIVA16 = $this->nuloSiCero($acumuladores['base16']);
        $totales->TotalTrasladosImpuestoIVA16 = $this->nuloSiCero($acumuladores['imp16']);
        $totales->TotalTrasladosBaseIVA8 = $this->nuloSiCero($acumuladores['base8']);
        $totales->TotalTrasladosImpuestoIVA8 = $this->nuloSiCero($acumuladores['imp8']);
        $totales->TotalTrasladosBaseIVA0 = $this->nuloSiCero($acumuladores['base0']);
        $totales->TotalTrasladosImpuestoIVA0 = $this->nuloSiCero($acumuladores['imp0']);
        $totales->TotalTrasladosBaseIVAExento = $this->nuloSiCero($acumuladores['baseExento']);
        $totales->MontoTotalPagos = $this->redondear($montoTotalMxn);

        $pagos->Totales = $totales;

        return $pagos;
    }

    private function factorConversionAMxn(PagosPago $pago): float
    {
        if ($pago->MonedaP === self::MXN) {
            return 1.0;
        }

        if ($pago->TipoCambioP === null) {
            throw new PagosCalculoException(
                "El Pago en moneda {$pago->MonedaP} requiere TipoCambioP para poder convertirse a MXN en el nodo Totales."
            );
        }

        return $pago->TipoCambioP;
    }

    private function acumularPorTasa(array &$acumuladores, float $tasa, float $base, float $importe): void
    {
        match (true) {
            abs($tasa - 0.16) < 0.0001 => [
                $acumuladores['base16'] += $base,
                $acumuladores['imp16'] += $importe,
            ],
            abs($tasa - 0.08) < 0.0001 => [
                $acumuladores['base8'] += $base,
                $acumuladores['imp8'] += $importe,
            ],
            abs($tasa) < 0.0001 => [
                $acumuladores['base0'] += $base,
                $acumuladores['imp0'] += $importe,
            ],
            default => throw new PagosCalculoException("Tasa de IVA no reconocida para el nodo Totales: {$tasa}"),
        };
    }

    private function nuloSiCero(float $valor): ?float
    {
        $redondeado = $this->redondear($valor);
        return $redondeado === 0.0 ? null : $redondeado;
    }

    private function redondear(float $valor): float
    {
        return round($valor, 2, PHP_ROUND_HALF_UP);
    }
}