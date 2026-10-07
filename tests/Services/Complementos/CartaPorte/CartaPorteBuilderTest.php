<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Contracts\IdCcpGeneratorInterface;
use ChabJose\CfdiGenerator\Exceptions\CartaPorteValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteAutotransporte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancia;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteUbicacion;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteBuilder;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteMercanciasCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MedioTransporteValidator;
use PHPUnit\Framework\TestCase;

class CartaPorteBuilderTest extends TestCase
{
    private const ID_CCP_FIJO = 'CCP00000000-0000-0000-0000-000000000000';

    private CartaPorteBuilder $builder;

    protected function setUp(): void
    {
        // El generador de IdCCP se mockea para que los tests sean deterministas
        // (no dependan de un valor aleatorio distinto en cada corrida).
        $generadorFalso = $this->createMock(IdCcpGeneratorInterface::class);
        $generadorFalso->method('generar')->willReturn(self::ID_CCP_FIJO);

        $this->builder = new CartaPorteBuilder(
            new CartaPorteMercanciasCalculator(),
            new CartaPorteTotalesCalculator(),
            MedioTransporteValidator::conMediosEstandar(),
            $generadorFalso,
        );
    }

    public function testBuildGeneraIdCcpAutomaticamenteCuandoEstaVacio(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteCruda();
        $cartaPorte->IdCCP = ''; // explícitamente vacío

        // Act
        $resultado = $this->builder->build($cartaPorte);

        // Assert
        $this->assertSame(self::ID_CCP_FIJO, $resultado->IdCCP);
    }

    public function testBuildRespetaUnIdCcpYaAsignadoManualmente(): void
    {
        // Arrange: el desarrollador ya trae su propio IdCCP (por ejemplo, para
        // mantener trazabilidad con un sistema externo)
        $cartaPorte = $this->cartaPorteCruda();
        $cartaPorte->IdCCP = 'CCPAAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

        // Act
        $resultado = $this->builder->build($cartaPorte);

        // Assert: no se sobreescribe
        $this->assertSame('CCPAAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE', $resultado->IdCCP);
    }

    public function testBuildCalculaPesoBrutoTotalYNumTotalMercancias(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteCruda();
        $cartaPorte->Mercancias->addMercancia($this->mercancia(pesoEnKg: 300.0));

        // Act
        $resultado = $this->builder->build($cartaPorte);

        // Assert: 500 (de la mercancía del helper base) + 300 = 800
        $this->assertSame(800.0, $resultado->Mercancias->PesoBrutoTotal);
        $this->assertSame(2, $resultado->Mercancias->NumTotalMercancias);
    }

    public function testBuildCalculaTotalDistRecSumandoSoloUbicacionesDestino(): void
    {
        // Arrange: Origen sin distancia, Destino con 350, y una parada
        // intermedia marcada también como Destino con 100 adicionales
        $cartaPorte = $this->cartaPorteCruda();

        $paradaIntermedia = new CartaPorteUbicacion();
        $paradaIntermedia->TipoUbicacion = '02'; // Destino
        $paradaIntermedia->RFCRemitenteDestinatario = 'CCC010101CCC';
        $paradaIntermedia->FechaHoraSalidaLlegada = '2026-10-05T12:00:00';
        $paradaIntermedia->DistanciaRecorrida = 100.0;
        $cartaPorte->addUbicacion($paradaIntermedia);

        // Act
        $resultado = $this->builder->build($cartaPorte);

        // Assert: 350 (destino original) + 100 (parada intermedia tipo Destino) = 450
        $this->assertSame(450.0, $resultado->TotalDistRec);
    }

    public function testBuildLanzaExcepcionSiNingunMedioDeTransporteEstaPresente(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteCruda();
        $cartaPorte->Mercancias->Autotransporte = null;

        // Assert
        $this->expectException(CartaPorteValidationException::class);

        // Act
        $this->builder->build($cartaPorte);
    }

    public function testBuildLanzaExcepcionSiDosMediosDeTransporteEstanPresentes(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteCruda();
        $cartaPorte->Mercancias->TransporteMaritimo = new \ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Maritimo\CartaPorteTransporteMaritimo();

        // Assert
        $this->expectException(CartaPorteValidationException::class);

        // Act
        $this->builder->build($cartaPorte);
    }

    public function testLaValidacionDeMedioDeTransporteCorreAntesDelCalculoDePesos(): void
    {
        // Arrange: Mercancias inválida (sin medio de transporte) Y con una
        // mercancía pendiente de calcular. Si el orden estuviera invertido,
        // el peso se calcularía antes de fallar - este test confirma que no.
        $cartaPorte = $this->cartaPorteCruda();
        $cartaPorte->Mercancias->Autotransporte = null;
        $cartaPorte->Mercancias->PesoBrutoTotal = 0.0; // estado "sin calcular"

        // Act & Assert
        try {
            $this->builder->build($cartaPorte);
            $this->fail('Se esperaba CartaPorteValidationException');
        } catch (CartaPorteValidationException) {
            // El peso NO debió haberse calculado, ya que la validación
            // detiene el flujo antes de llegar al cálculo.
            $this->assertSame(0.0, $cartaPorte->Mercancias->PesoBrutoTotal);
        }
    }

    // --- Helpers ---

    private function cartaPorteCruda(): CartaPorte
    {
        $cartaPorte = new CartaPorte();
        $cartaPorte->TranspInternac = 'No';

        $origen = new CartaPorteUbicacion();
        $origen->TipoUbicacion = '01';
        $origen->RFCRemitenteDestinatario = 'AAA010101AAA';
        $origen->FechaHoraSalidaLlegada = '2026-10-05T08:00:00';
        $cartaPorte->addUbicacion($origen);

        $destino = new CartaPorteUbicacion();
        $destino->TipoUbicacion = '02';
        $destino->RFCRemitenteDestinatario = 'BBB010101BBB';
        $destino->FechaHoraSalidaLlegada = '2026-10-05T18:00:00';
        $destino->DistanciaRecorrida = 350.0;
        $cartaPorte->addUbicacion($destino);

        $mercancias = new CartaPorteMercancias();
        $mercancias->UnidadPeso = 'KGM';
        $mercancias->addMercancia($this->mercancia(pesoEnKg: 500.0));
        $mercancias->Autotransporte = new CartaPorteAutotransporte();
        $mercancias->Autotransporte->PermSCT = 'TPAF01';
        $mercancias->Autotransporte->NumPermisoSCT = 'PERM123456';

        $cartaPorte->Mercancias = $mercancias;

        return $cartaPorte;
    }

    private function mercancia(float $pesoEnKg): CartaPorteMercancia
    {
        $mercancia = new CartaPorteMercancia();
        $mercancia->BienesTransp = '10101501';
        $mercancia->Descripcion = 'Producto de prueba';
        $mercancia->Cantidad = 1.0;
        $mercancia->ClaveUnidad = 'XBX';
        $mercancia->PesoEnKg = $pesoEnKg;

        return $mercancia;
    }
}