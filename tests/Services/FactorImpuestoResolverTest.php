<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use PHPUnit\Framework\TestCase;

class FactorImpuestoResolverTest extends TestCase
{
    private FactorImpuestoResolver $resolver;

    protected function setUp(): void
    {
        // Esto corre ANTES de cada test individual — evita repetir "new FactorImpuestoResolver()" en cada método
        $this->resolver = new FactorImpuestoResolver();
    }

    public function testCalculaImporteConTipoFactorTasa(): void
    {
        // Arrange
        $base = 1000.00;
        $tasaOCuota = 0.16; // IVA 16%

        // Act
        $importe = $this->resolver->resolverImporte('Tasa', $base, $tasaOCuota, cantidadConcepto: 1.0);

        // Assert
        $this->assertSame(160.0, $importe);
    }

    public function testCalculaImporteConTipoFactorCuota(): void
    {
        // Arrange: Cuota usa Cantidad del concepto, no Base
        $cantidadConcepto = 5.0;
        $tasaOCuota = 2.50; // ej. IEPS por litro

        // Act
        $importe = $this->resolver->resolverImporte('Cuota', base: 0.0, tasaOCuota: $tasaOCuota, cantidadConcepto: $cantidadConcepto);

        // Assert
        $this->assertSame(12.5, $importe);
    }

    public function testTipoFactorExentoRegresaNull(): void
    {
        // Act
        $importe = $this->resolver->resolverImporte('Exento', base: 1000.0, tasaOCuota: null, cantidadConcepto: 1.0);

        // Assert
        $this->assertNull($importe);
    }

    public function testTipoFactorDesconocidoLanzaExcepcion(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->resolver->resolverImporte('NoExiste', base: 100.0, tasaOCuota: 0.1, cantidadConcepto: 1.0);
    }
}