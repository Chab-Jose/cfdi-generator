<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Exceptions\PagosCalculoException;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosImpuestosP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoImpuestosP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoImpuestosPRetencionP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoImpuestosPTrasladoP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosRetencionP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosTrasladoP;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagosTotalesCalculator;
use PHPUnit\Framework\TestCase;

class PagosTotalesCalculatorTest extends TestCase
{
    private PagosTotalesCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PagosTotalesCalculator();
    }

    public function testCalculaMontoTotalPagosConUnSoloPagoEnMxn(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pagos->addPago($this->pagoSimple(monto: 1000.00, moneda: 'MXN'));

        // Act
        $this->calculator->calcular($pagos);

        // Assert
        $this->assertSame(1000.0, $pagos->Totales->MontoTotalPagos);
    }

    public function testSumaMontoTotalPagosDeVariosPagosEnMxn(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pagos->addPago($this->pagoSimple(monto: 1000.00, moneda: 'MXN'));
        $pagos->addPago($this->pagoSimple(monto: 500.00, moneda: 'MXN'));

        // Act
        $this->calculator->calcular($pagos);

        // Assert
        $this->assertSame(1500.0, $pagos->Totales->MontoTotalPagos);
    }

    public function testConvierteUnPagoEnUsdAMxnUsandoTipoCambioP(): void
    {
        // Arrange: $100 USD a tipo de cambio 18.50 = $1850.00 MXN
        $pagos = new Pagos();
        $pagos->addPago($this->pagoSimple(monto: 100.00, moneda: 'USD', tipoCambio: 18.50));

        // Act
        $this->calculator->calcular($pagos);

        // Assert
        $this->assertSame(1850.0, $pagos->Totales->MontoTotalPagos);
    }

    public function testSumaMontosDeMxnYUsdConvirtiendoCorrectamente(): void
    {
        // Arrange: 1000 MXN + (100 USD * 18.50) = 1000 + 1850 = 2850
        $pagos = new Pagos();
        $pagos->addPago($this->pagoSimple(monto: 1000.00, moneda: 'MXN'));
        $pagos->addPago($this->pagoSimple(monto: 100.00, moneda: 'USD', tipoCambio: 18.50));

        // Act
        $this->calculator->calcular($pagos);

        // Assert
        $this->assertSame(2850.0, $pagos->Totales->MontoTotalPagos);
    }

    public function testLanzaExcepcionSiPagoEnMonedaExtranjeraSinTipoCambioP(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pagos->addPago($this->pagoSimple(monto: 100.00, moneda: 'USD', tipoCambio: null));

        // Assert
        $this->expectException(PagosCalculoException::class);
        $this->expectExceptionMessageMatches('/TipoCambioP/');

        // Act
        $this->calculator->calcular($pagos);
    }

    public function testAgrupaTrasladosDeIva16DeVariosPagosEnTotales(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pagos->addPago($this->pagoConTrasladoIva(monto: 1160.00, base: 1000.00, importe: 160.00, tasa: 0.16));
        $pagos->addPago($this->pagoConTrasladoIva(monto: 580.00, base: 500.00, importe: 80.00, tasa: 0.16));

        // Act
        $this->calculator->calcular($pagos);

        // Assert
        $this->assertSame(1500.0, $pagos->Totales->TotalTrasladosBaseIVA16);
        $this->assertSame(240.0, $pagos->Totales->TotalTrasladosImpuestoIVA16);
    }

    public function testSeparaCorrectamenteTrasladosDeIva16YIva8(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pagos->addPago($this->pagoConTrasladoIva(monto: 1160.00, base: 1000.00, importe: 160.00, tasa: 0.16));
        $pagos->addPago($this->pagoConTrasladoIva(monto: 540.00, base: 500.00, importe: 40.00, tasa: 0.08));

        // Act
        $this->calculator->calcular($pagos);

        // Assert: cada tasa va a su campo fijo correspondiente, sin mezclarse
        $this->assertSame(1000.0, $pagos->Totales->TotalTrasladosBaseIVA16);
        $this->assertSame(160.0, $pagos->Totales->TotalTrasladosImpuestoIVA16);
        $this->assertSame(500.0, $pagos->Totales->TotalTrasladosBaseIVA8);
        $this->assertSame(40.0, $pagos->Totales->TotalTrasladosImpuestoIVA8);
    }

    public function testTrasladoExentoSoloAcumulaBaseSinImporte(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pago = $this->pagoSimple(monto: 1000.00, moneda: 'MXN');

        $traslado = new PagosPagoImpuestosPTrasladoP();
        $traslado->BaseP = 1000.00;
        $traslado->ImpuestoP = '002';
        $traslado->TipoFactorP = 'Exento';
        $traslado->TasaOCuotaP = null;
        $traslado->ImporteP = null;

        $pago->ImpuestosP = new PagosPagoImpuestosP();
        $pago->ImpuestosP->addTrasladoP($traslado);
        $pagos->addPago($pago);

        // Act
        $this->calculator->calcular($pagos);

        // Assert
        $this->assertSame(1000.0, $pagos->Totales->TotalTrasladosBaseIVAExento);
        $this->assertNull($pagos->Totales->TotalTrasladosImpuestoIVA16);
    }

    public function testAgrupaRetencionesPorTipoDeImpuesto(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pago = $this->pagoSimple(monto: 1000.00, moneda: 'MXN');

        $retencionIsr = new PagosPagoImpuestosPRetencionP();
        $retencionIsr->ImpuestoP = '001';
        $retencionIsr->ImporteP = 100.00;

        $retencionIva = new PagosPagoImpuestosPRetencionP();
        $retencionIva->ImpuestoP = '002';
        $retencionIva->ImporteP = 50.00;

        $pago->ImpuestosP = new PagosPagoImpuestosP();
        $pago->ImpuestosP->addRetencionP($retencionIsr);
        $pago->ImpuestosP->addRetencionP($retencionIva);
        $pagos->addPago($pago);

        // Act
        $this->calculator->calcular($pagos);

        // Assert
        $this->assertSame(100.0, $pagos->Totales->TotalRetencionesISR);
        $this->assertSame(50.0, $pagos->Totales->TotalRetencionesIVA);
    }

    public function testConvierteImpuestosAMxnCuandoElPagoEsEnMonedaExtranjera(): void
    {
        // Arrange: traslado de IVA calculado en USD, debe convertirse a MXN en Totales
        $pagos = new Pagos();
        $pago = $this->pagoSimple(monto: 116.00, moneda: 'USD', tipoCambio: 18.50);

        $traslado = new PagosPagoImpuestosPTrasladoP();
        $traslado->BaseP = 100.00; // USD
        $traslado->ImpuestoP = '002';
        $traslado->TipoFactorP = 'Tasa';
        $traslado->TasaOCuotaP = 0.16;
        $traslado->ImporteP = 16.00; // USD

        $pago->ImpuestosP = new PagosPagoImpuestosP();
        $pago->ImpuestosP->addTrasladoP($traslado);
        $pagos->addPago($pago);

        // Act
        $this->calculator->calcular($pagos);

        // Assert: 100 USD * 18.50 = 1850 MXN de base; 16 USD * 18.50 = 296 MXN de impuesto
        $this->assertSame(1850.0, $pagos->Totales->TotalTrasladosBaseIVA16);
        $this->assertSame(296.0, $pagos->Totales->TotalTrasladosImpuestoIVA16);
    }

    public function testCamposSinMovimientoQuedanNullNoCero(): void
    {
        // Arrange: un pago sin ningún impuesto
        $pagos = new Pagos();
        $pagos->addPago($this->pagoSimple(monto: 1000.00, moneda: 'MXN'));

        // Act
        $this->calculator->calcular($pagos);

        // Assert: el SAT espera ausencia del atributo, no un "0.00" explícito
        $this->assertNull($pagos->Totales->TotalRetencionesIVA);
        $this->assertNull($pagos->Totales->TotalTrasladosBaseIVA16);
        $this->assertNull($pagos->Totales->TotalTrasladosBaseIVAExento);
    }

    // --- Helpers ---

    private function pagoSimple(float $monto, string $moneda, ?float $tipoCambio = null): PagosPago
    {
        $pago = new PagosPago();
        $pago->Monto = $monto;
        $pago->MonedaP = $moneda;
        $pago->TipoCambioP = $tipoCambio;

        return $pago;
    }

    private function pagoConTrasladoIva(float $monto, float $base, float $importe, float $tasa): PagosPago
    {
        $pago = $this->pagoSimple($monto, 'MXN');

        $traslado = new PagosPagoImpuestosPTrasladoP();
        $traslado->BaseP = $base;
        $traslado->ImpuestoP = '002';
        $traslado->TipoFactorP = 'Tasa';
        $traslado->TasaOCuotaP = $tasa;
        $traslado->ImporteP = $importe;

        $pago->ImpuestosP = new PagosPagoImpuestosP();
        $pago->ImpuestosP->addTrasladoP($traslado);

        return $pago;
    }
}