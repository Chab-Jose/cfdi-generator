<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteImpuestos;
use ChabJose\CfdiGenerator\Services\ComprobanteTotalesCalculator;
use PHPUnit\Framework\TestCase;

class ComprobanteTotalesCalculatorTest extends TestCase
{
    private ComprobanteTotalesCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ComprobanteTotalesCalculator();
    }

    public function testCalculaSubtotalComoSumaDeImportes(): void
    {
        // Arrange
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoConImporte(1000.00),
            $this->conceptoConImporte(500.00),
        ];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert
        $this->assertSame(1500.0, $comprobante->SubTotal);
    }

    public function testCalculaDescuentoComoSumaDeDescuentosDeConceptos(): void
    {
        // Arrange
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoConImporte(1000.00, descuento: 100.00),
            $this->conceptoConImporte(500.00, descuento: 50.00),
        ];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert
        $this->assertSame(150.0, $comprobante->Descuento);
    }

    public function testDescuentoEsNullCuandoNingunConceptoTieneDescuento(): void
    {
        // Arrange
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [$this->conceptoConImporte(1000.00)];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert
        $this->assertNull($comprobante->Descuento);
    }

    public function testCalculaTotalConSubtotalDescuentoEImpuestos(): void
    {
        // Arrange: SubTotal 1500 - Descuento 100 + Trasladados 224 - Retenidos 100
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoConImporte(1000.00, descuento: 100.00),
            $this->conceptoConImporte(500.00),
        ];

        $comprobante->Impuestos = new ComprobanteImpuestos();
        $comprobante->Impuestos->TotalImpuestosTrasladados = 224.00;
        $comprobante->Impuestos->TotalImpuestosRetenidos = 100.00;

        // Act
        $this->calculator->calcular($comprobante);

        // Assert: 1500 - 100 + 224 - 100 = 1524
        $this->assertSame(1500.0, $comprobante->SubTotal);
        $this->assertSame(100.0, $comprobante->Descuento);
        $this->assertSame(1524.0, $comprobante->Total);
    }

    public function testCalculaTotalSinImpuestos(): void
    {
        // Arrange: Comprobante->Impuestos es null (sin IVA, ISR, etc.)
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [$this->conceptoConImporte(1000.00)];
        $comprobante->Impuestos = null;

        // Act
        $this->calculator->calcular($comprobante);

        // Assert: Total = SubTotal, sin más operaciones
        $this->assertSame(1000.0, $comprobante->Total);
    }

    // --- Helper ---

    private function conceptoConImporte(float $importe, ?float $descuento = null): ComprobanteConcepto
    {
        $concepto = new ComprobanteConcepto();
        $concepto->Importe = $importe;
        $concepto->Descuento = $descuento;

        return $concepto;
    }
}