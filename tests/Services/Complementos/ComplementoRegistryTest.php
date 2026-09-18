<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos;

use ChabJose\CfdiGenerator\Contracts\ComplementoXmlMapperInterface;
use ChabJose\CfdiGenerator\Exceptions\ComplementoNoRegistradoException;
use ChabJose\CfdiGenerator\Services\Complementos\ComplementoRegistry;
use PHPUnit\Framework\TestCase;

class ComplementoRegistryTest extends TestCase
{
    public function testEncuentraElMapperCorrectoParaUnComplementoSoportado(): void
    {
        // Arrange
        $mapperFalso = $this->crearMapperFalso(soporta: true);
        $registry = new ComplementoRegistry();
        $registry->registrar($mapperFalso);

        // Act
        $resultado = $registry->encontrarPara(new \stdClass());

        // Assert
        $this->assertSame($mapperFalso, $resultado);
    }

    public function testPruebaLosMappersEnOrdenHastaEncontrarUnoQueSoporte(): void
    {
        // Arrange
        $mapperQueNoSoporta = $this->crearMapperFalso(soporta: false);
        $mapperQueSiSoporta = $this->crearMapperFalso(soporta: true);

        $registry = new ComplementoRegistry();
        $registry->registrar($mapperQueNoSoporta);
        $registry->registrar($mapperQueSiSoporta);

        // Act
        $resultado = $registry->encontrarPara(new \stdClass());

        // Assert
        $this->assertSame($mapperQueSiSoporta, $resultado);
    }

    public function testLanzaExcepcionCuandoNingunMapperSoportaElComplemento(): void
    {
        // Arrange
        $registry = new ComplementoRegistry();
        $registry->registrar($this->crearMapperFalso(soporta: false));

        // Assert
        $this->expectException(ComplementoNoRegistradoException::class);
        $this->expectExceptionMessageMatches('/stdClass/');

        // Act
        $registry->encontrarPara(new \stdClass());
    }

    public function testLanzaExcepcionCuandoNoHayNingunMapperRegistrado(): void
    {
        // Arrange
        $registry = new ComplementoRegistry();

        // Assert
        $this->expectException(ComplementoNoRegistradoException::class);

        // Act
        $registry->encontrarPara(new \stdClass());
    }

    // --- Helper ---

    private function crearMapperFalso(bool $soporta): ComplementoXmlMapperInterface
    {
        $mock = $this->createMock(ComplementoXmlMapperInterface::class);
        $mock->method('soporta')->willReturn($soporta);

        return $mock;
    }
}