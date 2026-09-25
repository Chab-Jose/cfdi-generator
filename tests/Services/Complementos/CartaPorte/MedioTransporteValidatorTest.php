<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Aereo\CartaPorteTransporteAereo;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteAutotransporte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Ferroviario\CartaPorteTransporteFerroviario;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Maritimo\CartaPorteTransporteMaritimo;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MedioTransporteValidator;
use PHPUnit\Framework\TestCase;

class MedioTransporteValidatorTest extends TestCase
{
    private MedioTransporteValidator $validator;

    protected function setUp(): void
    {
        $this->validator = MedioTransporteValidator::conMediosEstandar();
    }

    public function testNoRegresaErroresConSoloAutotransportePresente(): void
    {
        // Arrange
        $mercancias = new CartaPorteMercancias();
        $mercancias->Autotransporte = new CartaPorteAutotransporte();

        // Act
        $errores = $this->validator->validar($mercancias);

        // Assert
        $this->assertEmpty($errores);
    }

    public function testNoRegresaErroresConSoloMaritimoPresente(): void
    {
        // Arrange
        $mercancias = new CartaPorteMercancias();
        $mercancias->TransporteMaritimo = new CartaPorteTransporteMaritimo();

        // Act
        $errores = $this->validator->validar($mercancias);

        // Assert
        $this->assertEmpty($errores);
    }

    public function testRegresaErrorSiNingunMedioEstaPresente(): void
    {
        // Arrange
        $mercancias = new CartaPorteMercancias();

        // Act
        $errores = $this->validator->validar($mercancias);

        // Assert
        $this->assertCount(1, $errores);
        $this->assertStringContainsString('no se encontró ninguno', $errores[0]);
    }

    public function testRegresaErrorSiDosMediosEstanPresentesSimultaneamente(): void
    {
        // Arrange: caso inválido - Autotransporte Y Marítimo a la vez
        $mercancias = new CartaPorteMercancias();
        $mercancias->Autotransporte = new CartaPorteAutotransporte();
        $mercancias->TransporteMaritimo = new CartaPorteTransporteMaritimo();

        // Act
        $errores = $this->validator->validar($mercancias);

        // Assert
        $this->assertCount(1, $errores);
        $this->assertStringContainsString('Autotransporte', $errores[0]);
        $this->assertStringContainsString('Marítimo', $errores[0]);
    }

    public function testRegresaErrorSiLosCuatroMediosEstanPresentesALaVez(): void
    {
        // Arrange: caso extremo, todos presentes
        $mercancias = new CartaPorteMercancias();
        $mercancias->Autotransporte = new CartaPorteAutotransporte();
        $mercancias->TransporteMaritimo = new CartaPorteTransporteMaritimo();
        $mercancias->TransporteAereo = new CartaPorteTransporteAereo();
        $mercancias->TransporteFerroviario = new CartaPorteTransporteFerroviario();

        // Act
        $errores = $this->validator->validar($mercancias);

        // Assert
        $this->assertCount(1, $errores);
    }
}