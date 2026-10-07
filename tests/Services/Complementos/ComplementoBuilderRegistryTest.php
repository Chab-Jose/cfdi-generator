<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos;

use ChabJose\CfdiGenerator\Contracts\ComplementoBuilderInterface;
use ChabJose\CfdiGenerator\Services\Complementos\ComplementoBuilderRegistry;
use PHPUnit\Framework\TestCase;

class ComplementoBuilderRegistryTest extends TestCase
{
    public function testEncuentraElBuilderCorrectoParaUnComplementoSoportado(): void
    {
        // Arrange
        $builderFalso = $this->crearBuilderFalso(soporta: true);
        $registry = new ComplementoBuilderRegistry();
        $registry->registrar($builderFalso);

        // Act
        $resultado = $registry->encontrarPara(new \stdClass());

        // Assert
        $this->assertSame($builderFalso, $resultado);
    }

    public function testPruebaLosBuildersEnOrdenHastaEncontrarUnoQueSoporte(): void
    {
        // Arrange
        $builderQueNoSoporta = $this->crearBuilderFalso(soporta: false);
        $builderQueSiSoporta = $this->crearBuilderFalso(soporta: true);

        $registry = new ComplementoBuilderRegistry();
        $registry->registrar($builderQueNoSoporta);
        $registry->registrar($builderQueSiSoporta);

        // Act
        $resultado = $registry->encontrarPara(new \stdClass());

        // Assert
        $this->assertSame($builderQueSiSoporta, $resultado);
    }

    public function testRegresaNullCuandoNingunBuilderSoportaElComplemento(): void
    {
        // Arrange
        $registry = new ComplementoBuilderRegistry();
        $registry->registrar($this->crearBuilderFalso(soporta: false));

        // Act
        $resultado = $registry->encontrarPara(new \stdClass());

        // Assert: a diferencia de ComplementoRegistry (el de XML), aquí
        // null es una respuesta válida - no todo complemento necesita cálculo
        $this->assertNull($resultado);
    }

    public function testRegresaNullCuandoNoHayNingunBuilderRegistrado(): void
    {
        // Arrange
        $registry = new ComplementoBuilderRegistry();

        // Act
        $resultado = $registry->encontrarPara(new \stdClass());

        // Assert
        $this->assertNull($resultado);
    }

    // --- Helper ---

    private function crearBuilderFalso(bool $soporta): ComplementoBuilderInterface
    {
        $mock = $this->createMock(ComplementoBuilderInterface::class);
        $mock->method('soporta')->willReturn($soporta);

        return $mock;
    }
}