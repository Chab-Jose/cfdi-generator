<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionado;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\DoctoRelacionadoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagosBuilder;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagosTotalesCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use PHPUnit\Framework\TestCase;

class PagosBuilderTest extends TestCase
{
    private PagosBuilder $builder;

    protected function setUp(): void
    {
        // Implementaciones reales de los 3 niveles - este test valida
        // que la orquesta completa funcione junta, igual que hicimos
        // con ComprobanteBuilderTest.
        $factorResolver = new FactorImpuestoResolver();

        $this->builder = new PagosBuilder(
            new DoctoRelacionadoImpuestosCalculator($factorResolver),
            new PagoImpuestosCalculator(),
            new PagosTotalesCalculator(),
        );
    }

    public function testBuildCalculaLosTresNivelesDePuntaAPuntaConUnSoloPago(): void
    {
        // Arrange: solo Base/TasaOCuotaDR crudos, nada calculado
        $pagos = new Pagos();
        $pagos->addPago($this->pagoCrudo(monto: 1160.00, baseTraslado: 1000.00, tasaIva: 0.16));

        // Act
        $resultado = $this->builder->build($pagos);

        // Assert: verificamos CADA nivel de la cadena
        $this->assertSame(160.0, $resultado->Pago[0]->DoctoRelacionado[0]->ImpuestosDR->TrasladosDR[0]->ImporteDR); // nivel DR
        $this->assertSame(160.0, $resultado->Pago[0]->ImpuestosP->TrasladosP[0]->ImporteP); // nivel P
        $this->assertSame(160.0, $resultado->Totales->TotalTrasladosImpuestoIVA16); // nivel Totales
        $this->assertSame(1160.0, $resultado->Totales->MontoTotalPagos);
    }

    public function testBuildConMultiplesDoctoRelacionadoAgrupaImpuestosCorrectamente(): void
    {
        // Arrange: un Pago que cubre 2 facturas distintas, ambas con IVA 16%
        $pago = new PagosPago();
        $pago->Monto = 1740.00;
        $pago->MonedaP = 'MXN';
        $pago->FechaPago = '2026-09-11T12:00:00';
        $pago->FormaDePagoP = '03';

        $pago->addDoctoRelacionado($this->doctoRelacionadoCrudo(baseTraslado: 1000.00, tasaIva: 0.16));
        $pago->addDoctoRelacionado($this->doctoRelacionadoCrudo(baseTraslado: 500.00, tasaIva: 0.16));

        $pagos = new Pagos();
        $pagos->addPago($pago);

        // Act
        $resultado = $this->builder->build($pagos);

        // Assert: los dos DoctoRelacionado se agrupan en un solo TrasladoP
        $this->assertCount(1, $resultado->Pago[0]->ImpuestosP->TrasladosP);
        $this->assertSame(1500.0, $resultado->Pago[0]->ImpuestosP->TrasladosP[0]->BaseP);
        $this->assertSame(240.0, $resultado->Pago[0]->ImpuestosP->TrasladosP[0]->ImporteP);
        $this->assertSame(240.0, $resultado->Totales->TotalTrasladosImpuestoIVA16);
    }

    public function testBuildConMultiplesPagosEnMonedasDistintasConvierteYSumaEnTotales(): void
    {
        // Arrange: un pago en MXN, otro en USD
        $pagos = new Pagos();
        $pagos->addPago($this->pagoCrudo(monto: 1000.00, baseTraslado: 0.0, tasaIva: 0.16, incluirImpuesto: false));

        $pagoUsd = new PagosPago();
        $pagoUsd->Monto = 100.00;
        $pagoUsd->MonedaP = 'USD';
        $pagoUsd->TipoCambioP = 18.50;
        $pagoUsd->FechaPago = '2026-09-11T12:00:00';
        $pagoUsd->FormaDePagoP = '03';
        $pagos->addPago($pagoUsd);

        // Act
        $resultado = $this->builder->build($pagos);

        // Assert: 1000 MXN + (100 USD * 18.50) = 2850
        $this->assertSame(2850.0, $resultado->Totales->MontoTotalPagos);
    }

    public function testBuildSinImpuestosSoloCalculaMontoTotalPagos(): void
    {
        // Arrange: pago simple sin ningún DoctoRelacionado con impuestos
        $pagos = new Pagos();
        $pagos->addPago($this->pagoCrudo(monto: 1000.00, baseTraslado: 0.0, tasaIva: 0.16, incluirImpuesto: false));

        // Act
        $resultado = $this->builder->build($pagos);

        // Assert
        $this->assertSame(1000.0, $resultado->Totales->MontoTotalPagos);
        $this->assertNull($resultado->Totales->TotalTrasladosImpuestoIVA16);
    }

    // --- Helpers ---

    private function pagoCrudo(float $monto, float $baseTraslado, float $tasaIva, bool $incluirImpuesto = true): PagosPago
    {
        $pago = new PagosPago();
        $pago->Monto = $monto;
        $pago->MonedaP = 'MXN';
        $pago->FechaPago = '2026-09-11T12:00:00';
        $pago->FormaDePagoP = '03';

        if ($incluirImpuesto) {
            $pago->addDoctoRelacionado($this->doctoRelacionadoCrudo($baseTraslado, $tasaIva));
        }

        return $pago;
    }

    private function doctoRelacionadoCrudo(float $baseTraslado, float $tasaIva): PagosPagoDoctoRelacionado
    {
        $traslado = new PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR();
        $traslado->BaseDR = $baseTraslado;
        $traslado->ImpuestoDR = '002';
        $traslado->TipoFactorDR = 'Tasa';
        $traslado->TasaOCuotaDR = $tasaIva;

        $impuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();
        $impuestosDR->addTrasladoDR($traslado);

        $docto = new PagosPagoDoctoRelacionado();
        $docto->IdDocumento = '00000000-0000-0000-0000-000000000000';
        $docto->MonedaDR = 'MXN';
        $docto->NumParcialidad = 1;
        $docto->ImpSaldoAnt = $baseTraslado + ($baseTraslado * $tasaIva);
        $docto->ImpPagado = $baseTraslado + ($baseTraslado * $tasaIva);
        $docto->ImpSaldoInsoluto = 0.0;
        $docto->ObjetoImpDR = '02';
        $docto->ImpuestosDR = $impuestosDR;

        return $docto;
    }
}