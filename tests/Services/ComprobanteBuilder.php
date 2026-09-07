<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestos;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosRetencion;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosTraslado;
use ChabJose\CfdiGenerator\Services\ComprobanteBuilder;
use ChabJose\CfdiGenerator\Services\ComprobanteImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\ComprobanteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use PHPUnit\Framework\TestCase;

class ComprobanteBuilderTest extends TestCase
{
    private ComprobanteBuilder $builder;

    protected function setUp(): void
    {
        // Aquí SÍ usamos las implementaciones reales de todos los servicios,
        // no mocks - este test valida que la orquesta completa funcione junta.
        $factorResolver = new FactorImpuestoResolver();

        $this->builder = new ComprobanteBuilder(
            new ConceptoImpuestosCalculator($factorResolver),
            new ConceptoCalculator(),
            new ComprobanteImpuestosCalculator(),
            new ComprobanteTotalesCalculator(),
        );
    }

    public function testBuildCalculaTodoDePuntaAPuntaConUnSoloConcepto(): void
    {
        // Arrange: comprobante crudo, nada pre-calculado
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoCrudo(valorUnitario: 1000.00, cantidad: 1.0, tasaIva: 0.16),
        ];

        // Act
        $resultado = $this->builder->build($comprobante);

        // Assert: verificamos CADA nivel de la cadena de cálculo
        $this->assertSame(1000.0, $resultado->Conceptos[0]->Importe); // ConceptoCalculator
        $this->assertSame(160.0, $resultado->Conceptos[0]->Impuestos->Traslados[0]->Importe); // ConceptoImpuestosCalculator
        $this->assertSame(160.0, $resultado->Impuestos->TotalImpuestosTrasladados); // ComprobanteImpuestosCalculator
        $this->assertSame(1000.0, $resultado->SubTotal); // ComprobanteTotalesCalculator
        $this->assertSame(1160.0, $resultado->Total); // 1000 + 160 de IVA
    }

    public function testBuildConMultiplesConceptosMismaTasaIvaAgrupaCorrectamente(): void
    {
        // Arrange: dos conceptos con IVA 16%, deben agruparse en un solo Traslado
        $comprobante = new Comprobante();
        $comprobante->Conceptos = [
            $this->conceptoCrudo(valorUnitario: 1000.00, cantidad: 1.0, tasaIva: 0.16),
            $this->conceptoCrudo(valorUnitario: 500.00, cantidad: 2.0, tasaIva: 0.16),
        ];

        // Act
        $resultado = $this->builder->build($comprobante);

        // Assert
        $this->assertSame(1000.0, $resultado->Conceptos[0]->Importe);
        $this->assertSame(1000.0, $resultado->Conceptos[1]->Importe); // 500 * 2

        $this->assertCount(1, $resultado->Impuestos->Traslados); // agrupados
        $this->assertSame(2000.0, $resultado->Impuestos->Traslados[0]->Base); // 1000 + 1000
        $this->assertSame(320.0, $resultado->Impuestos->Traslados[0]->Importe); // 160 + 160

        $this->assertSame(2000.0, $resultado->SubTotal);
        $this->assertSame(2320.0, $resultado->Total);
    }

    public function testBuildConTrasladoYRetencionCalculaTotalCorrecto(): void
    {
        // Arrange: IVA 16% (traslado) + ISR 10% (retención) - caso típico de honorarios
        $comprobante = new Comprobante();
        $concepto = $this->conceptoCrudo(valorUnitario: 1000.00, cantidad: 1.0, tasaIva: 0.16);

        $retencion = new ComprobanteConceptoImpuestosRetencion();
        $retencion->Base = 1000.00;
        $retencion->Impuesto = '001';
        $retencion->TipoFactor = 'Tasa';
        $retencion->TasaOCuota = 0.10;
        $concepto->Impuestos->addRetencion($retencion);

        $comprobante->Conceptos = [$concepto];

        // Act
        $resultado = $this->builder->build($comprobante);

        // Assert: Total = SubTotal + Trasladados - Retenidos = 1000 + 160 - 100 = 1060
        $this->assertSame(100.0, $resultado->Impuestos->Retenciones[0]->Importe);
        $this->assertSame(1060.0, $resultado->Total);
    }

    public function testBuildSinImpuestosCalculaSoloSubtotalYTotal(): void
    {
        // Arrange: concepto sin Impuestos en absoluto
        $comprobante = new Comprobante();
        $concepto = new ComprobanteConcepto();
        $concepto->ValorUnitario = 500.00;
        $concepto->Cantidad = 2.0;
        $concepto->Impuestos = null;
        $comprobante->Conceptos = [$concepto];

        // Act
        $resultado = $this->builder->build($comprobante);

        // Assert
        $this->assertSame(1000.0, $resultado->SubTotal);
        $this->assertSame(1000.0, $resultado->Total);
        $this->assertNull($resultado->Impuestos);
    }

    public function testBuildRespetaImporteManualDelConceptoSinRecalcular(): void
    {
        // Arrange: el usuario ya trae el Importe desde su ERP
        $comprobante = new Comprobante();
        $concepto = new ComprobanteConcepto();
        $concepto->ValorUnitario = 100.00;
        $concepto->Cantidad = 3.0; // calcularía 300.0
        $concepto->Importe = 299.97; // pero el usuario trae este valor con su propio redondeo
        $comprobante->Conceptos = [$concepto];

        // Act
        $resultado = $this->builder->build($comprobante);

        // Assert: se respeta el valor manual, no se sobreescribe
        $this->assertSame(299.97, $resultado->SubTotal);
    }

    // --- Helper ---

    private function conceptoCrudo(float $valorUnitario, float $cantidad, float $tasaIva): ComprobanteConcepto
    {
        $concepto = new ComprobanteConcepto();
        $concepto->ValorUnitario = $valorUnitario;
        $concepto->Cantidad = $cantidad;

        $traslado = new ComprobanteConceptoImpuestosTraslado();
        $traslado->Base = $valorUnitario * $cantidad;
        $traslado->Impuesto = '002';
        $traslado->TipoFactor = 'Tasa';
        $traslado->TasaOCuota = $tasaIva;

        $impuestos = new ComprobanteConceptoImpuestos();
        $impuestos->addTraslado($traslado);
        $concepto->Impuestos = $impuestos;

        return $concepto;
    }
}