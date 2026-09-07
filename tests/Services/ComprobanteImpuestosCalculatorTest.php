<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestos;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosRetencion;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosTraslado;
use ChabJose\CfdiGenerator\Services\ComprobanteImpuestosCalculator;
use PHPUnit\Framework\TestCase;

class ComprobanteImpuestosCalculatorTest extends TestCase
{
    private ComprobanteImpuestosCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ComprobanteImpuestosCalculator();
    }

    public function testAgrupaTrasladosDeDosConceptosConMismoImpuesto(): void
    {
        // Arrange: dos conceptos, ambos con IVA 16%, deben sumarse en UN solo renglón
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoConTraslado(base: 1000.00, importe: 160.00),
            $this->conceptoConTraslado(base: 500.00, importe: 80.00),
        ];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert
        $this->assertNotNull($comprobante->Impuestos);
        $this->assertCount(1, $comprobante->Impuestos->Traslados); // un solo renglón agrupado
        $this->assertSame(1500.0, $comprobante->Impuestos->Traslados[0]->Base); // 1000 + 500
        $this->assertSame(240.0, $comprobante->Impuestos->Traslados[0]->Importe); // 160 + 80
    }

    public function testNoAgrupaTrasladosConDistintaTasa(): void
    {
        // Arrange: IVA 16% y IVA 8% (frontera) deben quedar en renglones separados
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoConTraslado(base: 1000.00, importe: 160.00, tasaOCuota: 0.16),
            $this->conceptoConTraslado(base: 1000.00, importe: 80.00, tasaOCuota: 0.08),
        ];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert
        $this->assertCount(2, $comprobante->Impuestos->Traslados);
    }

    public function testAgrupaTasaOCuotaNullYCeroComoElMismoGrupo(): void
    {
        // Arrange: este es el edge case que discutimos - null y 0.0 deben ser el mismo grupo
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoConTraslado(base: 1000.00, importe: null, tasaOCuota: null),
            $this->conceptoConTraslado(base: 500.00, importe: null, tasaOCuota: 0.0),
        ];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert: deben quedar agrupados en un solo renglón, no dos
        $this->assertCount(1, $comprobante->Impuestos->Traslados);
        $this->assertSame(1500.0, $comprobante->Impuestos->Traslados[0]->Base);
    }

    public function testAgrupaRetencionesSoloPorImpuestoIgnorandoTasa(): void
    {
        // Arrange: dos conceptos con ISR pero distinta tasa - a nivel Comprobante
        // las retenciones solo agrupan por Impuesto (según el XSLT del SAT)
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoConRetencion(importe: 100.00, tasaOCuota: 0.10),
            $this->conceptoConRetencion(importe: 125.00, tasaOCuota: 0.125),
        ];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert
        $this->assertCount(1, $comprobante->Impuestos->Retenciones);
        $this->assertSame(225.0, $comprobante->Impuestos->Retenciones[0]->Importe);
    }

    public function testCalculaTotalImpuestosTrasladadosYRetenidos(): void
    {
        // Arrange
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoConTrasladoYRetencion(
                baseTraslado: 1000.00,
                importeTraslado: 160.00,
                importeRetencion: 100.00,
            ),
        ];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert
        $this->assertSame(160.0, $comprobante->Impuestos->TotalImpuestosTrasladados);
        $this->assertSame(100.0, $comprobante->Impuestos->TotalImpuestosRetenidos);
    }

    public function testComprobanteSinImpuestosEnNingunConceptoResultaEnImpuestosNull(): void
    {
        // Arrange: ningún concepto tiene Impuestos
        $comprobante = new Comprobante();
        $concepto = new ComprobanteConcepto();
        $concepto->Impuestos = null;
        $comprobante->Conceptos = [$concepto];

        // Act
        $this->calculator->calcular($comprobante);

        // Assert: el nodo Impuestos completo debe ser null, no un objeto vacío
        $this->assertNull($comprobante->Impuestos);
    }

    // --- Helpers para armar fixtures sin repetir código en cada test ---

    private function conceptoConTraslado(float $base, ?float $importe, ?float $tasaOCuota = 0.16): ComprobanteConcepto
    {
        $traslado = new ComprobanteConceptoImpuestosTraslado();
        $traslado->Base = $base;
        $traslado->Impuesto = '002';
        $traslado->TipoFactor = 'Tasa';
        $traslado->TasaOCuota = $tasaOCuota;
        $traslado->Importe = $importe;

        $impuestos = new ComprobanteConceptoImpuestos();
        $impuestos->addTraslado($traslado);

        $concepto = new ComprobanteConcepto();
        $concepto->Impuestos = $impuestos;

        return $concepto;
    }

    private function conceptoConRetencion(float $importe, ?float $tasaOCuota): ComprobanteConcepto
    {
        $retencion = new ComprobanteConceptoImpuestosRetencion();
        $retencion->Base = 1000.00;
        $retencion->Impuesto = '001';
        $retencion->TipoFactor = 'Tasa';
        $retencion->TasaOCuota = $tasaOCuota;
        $retencion->Importe = $importe;

        $impuestos = new ComprobanteConceptoImpuestos();
        $impuestos->addRetencion($retencion);

        $concepto = new ComprobanteConcepto();
        $concepto->Impuestos = $impuestos;

        return $concepto;
    }

    private function conceptoConTrasladoYRetencion(
        float $baseTraslado,
        float $importeTraslado,
        float $importeRetencion,
    ): ComprobanteConcepto {
        $concepto = $this->conceptoConTraslado($baseTraslado, $importeTraslado);
        $concepto->Impuestos->addRetencion($this->retencionSimple($importeRetencion));

        return $concepto;
    }

    private function retencionSimple(float $importe): ComprobanteConceptoImpuestosRetencion
    {
        $retencion = new ComprobanteConceptoImpuestosRetencion();
        $retencion->Base = 1000.00;
        $retencion->Impuesto = '001';
        $retencion->TipoFactor = 'Tasa';
        $retencion->TasaOCuota = 0.10;
        $retencion->Importe = $importe;

        return $retencion;
    }
}