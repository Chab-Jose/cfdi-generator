<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Services\ConceptoCalculator;
use PHPUnit\Framework\TestCase;

class ConceptoCalculatorTest extends TestCase
{
    private ConceptoCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ConceptoCalculator();
    }

    public function testCalculaImportePorValorUnitarioPorCantidad(): void
    {
        // Arrange
        $concepto = new ComprobanteConcepto();
        $concepto->ValorUnitario = 150.50;
        $concepto->Cantidad = 3.0;

        // Act
        $importe = $this->calculator->calcularImporte($concepto);

        // Assert
        $this->assertSame(451.5, $importe);
        $this->assertSame(451.5, $concepto->Importe); // confirma que también mutó el modelo
    }

    public function testRespetaDecimalesPersonalizados(): void
    {
        // Arrange
        $concepto = new ComprobanteConcepto();
        $concepto->ValorUnitario = 10.0;
        $concepto->Cantidad = 3.0;

        // Act: 6 decimales, común en conceptos con precios muy fraccionados
        $importe = $this->calculator->calcularImporte($concepto, decimales: 6);

        // Assert
        $this->assertSame(30.0, $importe);
    }

    public function testNoSobreescribeImporteYaDefinidoSalvoQueSeForce(): void
    {
        // Arrange: Importe ya viene de un ERP externo
        $concepto = new ComprobanteConcepto();
        $concepto->ValorUnitario = 100.0;
        $concepto->Cantidad = 2.0;
        $concepto->Importe = 199.99; // valor manual, distinto a 200.0

        // Act
        $importe = $this->calculator->calcularImporte($concepto, forzar: false);

        // Assert
        $this->assertSame(199.99, $importe);
    }

    public function testForzarRecalculaAunConValorManualPrevio(): void
    {
        // Arrange
        $concepto = new ComprobanteConcepto();
        $concepto->ValorUnitario = 100.0;
        $concepto->Cantidad = 2.0;
        $concepto->Importe = 199.99;

        // Act
        $importe = $this->calculator->calcularImporte($concepto, forzar: true);

        // Assert
        $this->assertSame(200.0, $importe);
    }

    public function testRedondeaConMediaHaciaArriba(): void
    {
        // Arrange: caso de redondeo frontera (0.005 hacia arriba, no hacia par)
        $concepto = new ComprobanteConcepto();
        $concepto->ValorUnitario = 0.125;
        $concepto->Cantidad = 1.0;

        // Act
        $importe = $this->calculator->calcularImporte($concepto);

        // Assert: PHP_ROUND_HALF_UP debe redondear 0.125 → 0.13, no 0.12
        $this->assertSame(0.13, $importe);
    }
}