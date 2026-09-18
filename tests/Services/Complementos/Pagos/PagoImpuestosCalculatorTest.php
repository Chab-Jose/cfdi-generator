<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosDoctoRelacionado;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosImpuestosDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionado;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDRRetencionDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosRetencionDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosTrasladoDR;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagoImpuestosCalculator;
use PHPUnit\Framework\TestCase;

class PagoImpuestosCalculatorTest extends TestCase
{
    private PagoImpuestosCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PagoImpuestosCalculator();
    }

    public function testAgrupaTrasladosDeDosDoctoRelacionadoConMismoImpuesto(): void
    {
        // Arrange: dos facturas pagadas en el mismo Pago, ambas con IVA 16%
        $pago = new PagosPago();
        $pago->DoctoRelacionado = [
            $this->doctoConTraslado(base: 1000.00, importe: 160.00),
            $this->doctoConTraslado(base: 500.00, importe: 80.00),
        ];

        // Act
        $this->calculator->calcular($pago);

        // Assert: un solo renglón agrupado en ImpuestosP
        $this->assertCount(1, $pago->ImpuestosP->TrasladosP);
        $this->assertSame(1500.0, $pago->ImpuestosP->TrasladosP[0]->BaseP);
        $this->assertSame(240.0, $pago->ImpuestosP->TrasladosP[0]->ImporteP);
    }

    public function testAgrupaRetencionesSoloPorImpuesto(): void
    {
        // Arrange
        $pago = new PagosPago();
        $pago->DoctoRelacionado = [
            $this->doctoConRetencion(importe: 100.00),
            $this->doctoConRetencion(importe: 50.00),
        ];

        // Act
        $this->calculator->calcular($pago);

        // Assert
        $this->assertCount(1, $pago->ImpuestosP->RetencionesP);
        $this->assertSame(150.0, $pago->ImpuestosP->RetencionesP[0]->ImporteP);
    }

    public function testPagoSinDoctoRelacionadoConImpuestosDejaImpuestosPNull(): void
    {
        // Arrange
        $pago = new PagosPago();
        $docto = new PagosPagoDoctoRelacionado();
        $docto->ImpuestosDR = null;
        $pago->DoctoRelacionado = [$docto];

        // Act
        $this->calculator->calcular($pago);

        // Assert
        $this->assertNull($pago->ImpuestosP);
    }

    // --- Helpers ---

    private function doctoConTraslado(float $base, float $importe): PagosPagoDoctoRelacionado
    {
        $traslado = new PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR();
        $traslado->BaseDR = $base;
        $traslado->ImpuestoDR = '002';
        $traslado->TipoFactorDR = 'Tasa';
        $traslado->TasaOCuotaDR = 0.16;
        $traslado->ImporteDR = $importe;

        $impuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();
        $impuestosDR->addTrasladoDR($traslado);

        $docto = new PagosPagoDoctoRelacionado();
        $docto->ImpuestosDR = $impuestosDR;

        return $docto;
    }

    private function doctoConRetencion(float $importe): PagosPagoDoctoRelacionado
    {
        $retencion = new PagosPagoDoctoRelacionadoImpuestosDRRetencionDR();
        $retencion->BaseDR = 1000.00;
        $retencion->ImpuestoDR = '001';
        $retencion->TipoFactorDR = 'Tasa';
        $retencion->TasaOCuotaDR = 0.10;
        $retencion->ImporteDR = $importe;

        $impuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();
        $impuestosDR->addRetencionDR($retencion);

        $docto = new PagosPagoDoctoRelacionado();
        $docto->ImpuestosDR = $impuestosDR;

        return $docto;
    }
}