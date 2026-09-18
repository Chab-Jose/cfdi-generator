<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionado;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDRRetencionDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\DoctoRelacionadoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use PHPUnit\Framework\TestCase;

class DoctoRelacionadoImpuestosCalculatorTest extends TestCase
{
    private DoctoRelacionadoImpuestosCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DoctoRelacionadoImpuestosCalculator(new FactorImpuestoResolver());
    }

    public function testCalculaImporteDRDeUnTrasladoConIva16(): void
    {
        // Arrange
        $docto = new PagosPagoDoctoRelacionado();
        $docto->ImpuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();

        $traslado = new PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR();
        $traslado->BaseDR = 1000.00;
        $traslado->ImpuestoDR = '002';
        $traslado->TipoFactorDR = 'Tasa';
        $traslado->TasaOCuotaDR = 0.16;
        $docto->ImpuestosDR->addTrasladoDR($traslado);

        // Act
        $this->calculator->calcular($docto);

        // Assert
        $this->assertSame(160.0, $docto->ImpuestosDR->TrasladosDR[0]->ImporteDR);
    }

    public function testNoSobreescribeImporteDRYaDefinidoSalvoQueSeForce(): void
    {
        // Arrange
        $docto = new PagosPagoDoctoRelacionado();
        $docto->ImpuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();

        $traslado = new PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR();
        $traslado->BaseDR = 1000.00;
        $traslado->ImpuestoDR = '002';
        $traslado->TipoFactorDR = 'Tasa';
        $traslado->TasaOCuotaDR = 0.16;
        $traslado->ImporteDR = 159.99; // valor manual
        $docto->ImpuestosDR->addTrasladoDR($traslado);

        // Act
        $this->calculator->calcular($docto, forzar: false);

        // Assert
        $this->assertSame(159.99, $docto->ImpuestosDR->TrasladosDR[0]->ImporteDR);
    }

    public function testCalculaImporteDRDeUnaRetencion(): void
    {
        // Arrange
        $docto = new PagosPagoDoctoRelacionado();
        $docto->ImpuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();

        $retencion = new PagosPagoDoctoRelacionadoImpuestosDRRetencionDR();
        $retencion->BaseDR = 1000.00;
        $retencion->ImpuestoDR = '001'; // ISR
        $retencion->TipoFactorDR = 'Tasa';
        $retencion->TasaOCuotaDR = 0.10;
        $docto->ImpuestosDR->addRetencionDR($retencion);

        // Act
        $this->calculator->calcular($docto);

        // Assert
        $this->assertSame(100.0, $docto->ImpuestosDR->RetencionesDR[0]->ImporteDR);
    }

    public function testDoctoRelacionadoSinImpuestosDRNoFalla(): void
    {
        // Arrange
        $docto = new PagosPagoDoctoRelacionado();
        $docto->ImpuestosDR = null;

        // Act
        $resultado = $this->calculator->calcular($docto);

        // Assert
        $this->assertNull($resultado->ImpuestosDR);
    }
}