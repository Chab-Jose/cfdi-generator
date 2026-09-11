<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestos;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosRetencion;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosTraslado;
use ChabJose\CfdiGenerator\Services\ConceptoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use PHPUnit\Framework\TestCase;

class ConceptoImpuestosCalculatorTest extends TestCase
{
    private ConceptoImpuestosCalculator $calculator;

    protected function setUp(): void
    {
        // Usamos el FactorImpuestoResolver REAL (no un mock).
        // Es una decisión válida cuando la dependencia es simple, rápida,
        // y sin efectos externos (no llama APIs, no toca disco, no es random).
        $this->calculator = new ConceptoImpuestosCalculator(new FactorImpuestoResolver());
    }

    public function testCalculaImporteDeTrasladoConIva16(): void
    {
        // Arrange
        $concepto = new ComprobanteConcepto();
        $concepto->Cantidad = 1.0;
        $concepto->Impuestos = new ComprobanteConceptoImpuestos();

        $traslado = new ComprobanteConceptoImpuestosTraslado();
        $traslado->Base = 1000.00;
        $traslado->Impuesto = '002'; // IVA
        $traslado->TipoFactor = 'Tasa';
        $traslado->TasaOCuota = 0.16;
        $concepto->Impuestos->addTraslado($traslado);

        // Act
        $this->calculator->calcular($concepto);

        // Assert
        $this->assertSame(160.0, $concepto->Impuestos->Traslados[0]->Importe);
    }

    public function testNoSobreescribeImporteYaDefinidoSalvoQueSeForce(): void
    {
        // Arrange: el usuario ya trae un Importe manual desde su ERP
        $concepto = new ComprobanteConcepto();
        $concepto->Cantidad = 1.0;
        $concepto->Impuestos = new ComprobanteConceptoImpuestos();

        $traslado = new ComprobanteConceptoImpuestosTraslado();
        $traslado->Base = 1000.00;
        $traslado->Impuesto = '002';
        $traslado->TipoFactor = 'Tasa';
        $traslado->TasaOCuota = 0.16;
        $traslado->Importe = 159.99; // valor manual, distinto al que calcularía la fórmula
        $concepto->Impuestos->addTraslado($traslado);

        // Act: sin forzar
        $this->calculator->calcular($concepto, forzar: false);

        // Assert: se respeta el valor manual
        $this->assertSame(159.99, $concepto->Impuestos->Traslados[0]->Importe);
    }

    public function testSobreescribeImporteCuandoSeForzaElCalculo(): void
    {
        // Arrange
        $concepto = new ComprobanteConcepto();
        $concepto->Cantidad = 1.0;
        $concepto->Impuestos = new ComprobanteConceptoImpuestos();

        $traslado = new ComprobanteConceptoImpuestosTraslado();
        $traslado->Base = 1000.00;
        $traslado->Impuesto = '002';
        $traslado->TipoFactor = 'Tasa';
        $traslado->TasaOCuota = 0.16;
        $traslado->Importe = 159.99; // valor manual "incorrecto"
        $concepto->Impuestos->addTraslado($traslado);

        // Act: forzando el recálculo
        $this->calculator->calcular($concepto, forzar: true);

        // Assert: ahora sí se recalcula
        $this->assertSame(160.0, $concepto->Impuestos->Traslados[0]->Importe);
    }

    public function testCalculaRetencionIsr(): void
    {
        // Arrange
        $concepto = new ComprobanteConcepto();
        $concepto->Cantidad = 1.0;
        $concepto->Impuestos = new ComprobanteConceptoImpuestos();

        $retencion = new ComprobanteConceptoImpuestosRetencion();
        $retencion->Base = 1000.00;
        $retencion->Impuesto = '001'; // ISR
        $retencion->TipoFactor = 'Tasa';
        $retencion->TasaOCuota = 0.10;
        $concepto->Impuestos->addRetencion($retencion);

        // Act
        $this->calculator->calcular($concepto);

        // Assert
        $this->assertSame(100.0, $concepto->Impuestos->Retenciones[0]->Importe);
    }

    public function testConceptoSinImpuestosNoFalla(): void
    {
        // Arrange: Impuestos es null (concepto sin impuestos aplicables)
        $concepto = new ComprobanteConcepto();
        $concepto->Impuestos = null;

        // Act & Assert: no debe lanzar excepción
        $resultado = $this->calculator->calcular($concepto);

        $this->assertNull($resultado->Impuestos);
    }
}