<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Exceptions\CartaPorteValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteAutotransporte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteIdentificacionVehicular;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteRemolque;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteSeguros;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteFiguraTransporte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancia;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteTipoFigura;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteUbicacion;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Ferroviario\CartaPorteTransporteFerroviario;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Maritimo\CartaPorteTransporteMaritimo;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\AereoXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\AutotransporteXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\FerroviarioXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\MaritimoXmlMapper;
use PHPUnit\Framework\TestCase;

class CartaPorteXmlMapperTest extends TestCase
{
    private const NS_CP = 'http://www.sat.gob.mx/CartaPorte31';

    private CartaPorteXmlMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new CartaPorteXmlMapper([
            new AutotransporteXmlMapper(),
            new MaritimoXmlMapper(),
            new AereoXmlMapper(),
            new FerroviarioXmlMapper(),
        ]);
    }

    public function testSoportaSoloInstanciasDeCartaPorte(): void
    {
        $this->assertTrue($this->mapper->soporta(new CartaPorte()));
        $this->assertFalse($this->mapper->soporta(new \stdClass()));
    }

    public function testGeneraNodoRaizConAtributosRequeridos(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteMinima();

        // Act
        $xpath = $this->xpathDe($cartaPorte);

        // Assert
        $nodos = $xpath->query('/cartaporte31:CartaPorte');
        $this->assertSame(1, $nodos->length);
        $this->assertSame('3.1', $nodos->item(0)->getAttribute('Version'));
        $this->assertSame('No', $nodos->item(0)->getAttribute('TranspInternac'));
    }

    public function testMapeaDosUbicacionesOrigenYDestino(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteMinima();

        // Act
        $xpath = $this->xpathDe($cartaPorte);

        // Assert
        $nodos = $xpath->query('/cartaporte31:CartaPorte/cartaporte31:Ubicaciones/cartaporte31:Ubicacion');
        $this->assertSame(2, $nodos->length);
        $this->assertSame('01', $nodos->item(0)->getAttribute('TipoUbicacion')); // Origen
        $this->assertSame('02', $nodos->item(1)->getAttribute('TipoUbicacion')); // Destino
    }

    public function testMapeaMercanciasConAutotransporte(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteMinima();

        // Act
        $xpath = $this->xpathDe($cartaPorte);

        // Assert
        $mercancias = $xpath->query('/cartaporte31:CartaPorte/cartaporte31:Mercancias')->item(0);
        $this->assertSame('1', $mercancias->getAttribute('NumTotalMercancias'));

        $autotransporte = $xpath->query('/cartaporte31:CartaPorte/cartaporte31:Mercancias/cartaporte31:Autotransporte');
        $this->assertSame(1, $autotransporte->length);
    }

    public function testMapeaIdentificacionVehicularYSegurosDentroDeAutotransporte(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteMinima();

        // Act
        $xpath = $this->xpathDe($cartaPorte);

        // Assert
        $ivQuery = '/cartaporte31:CartaPorte/cartaporte31:Mercancias/cartaporte31:Autotransporte/cartaporte31:IdentificacionVehicular';
        $this->assertSame('VTP02', $this->atributo($xpath, $ivQuery, 'ConfigVehicular'));

        $segurosQuery = '/cartaporte31:CartaPorte/cartaporte31:Mercancias/cartaporte31:Autotransporte/cartaporte31:Seguros';
        $this->assertSame('AseguradoraTest', $this->atributo($xpath, $segurosQuery, 'AseguraRespCivil'));
    }

    public function testMapeaRemolquesDentroDeAutotransporte(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteMinima();
        $cartaPorte->Mercancias->Autotransporte->addRemolque($this->remolque());

        // Act
        $xpath = $this->xpathDe($cartaPorte);

        // Assert
        $query = '/cartaporte31:CartaPorte/cartaporte31:Mercancias/cartaporte31:Autotransporte/cartaporte31:Remolques/cartaporte31:Remolque';
        $this->assertSame('CTR001', $this->atributo($xpath, $query, 'SubTipoRem'));
    }

    public function testUsaElMapperDeMaritimoCuandoCorresponde(): void
    {
        // Arrange: Mercancias con Marítimo en vez de Autotransporte
        $cartaPorte = $this->cartaPorteMinima();
        $cartaPorte->Mercancias->Autotransporte = null;

        $maritimo = new CartaPorteTransporteMaritimo();
        $maritimo->TipoEmbarcacion = '01';
        $maritimo->Matricula = 'ABC123';
        $maritimo->NumeroOMI = 'OMI1234567';
        $maritimo->NacionalidadEmbarc = 'MEX';
        $maritimo->UnidadesDeArqBruto = 500.0;
        $maritimo->TipoCarga = '01';
        $maritimo->NombreAgenteNaviero = 'Agente Naviero SA';
        $maritimo->NumAutorizacionNaviero = 'AUT001';
        $cartaPorte->Mercancias->TransporteMaritimo = $maritimo;

        // Act
        $xpath = $this->xpathDe($cartaPorte);

        // Assert: se usó el mapper correcto, no el de Autotransporte
        $this->assertSame(0, $xpath->query('//cartaporte31:Autotransporte')->length);
        $maritimoNode = $xpath->query('/cartaporte31:CartaPorte/cartaporte31:Mercancias/cartaporte31:TransporteMaritimo')->item(0);
        $this->assertSame('ABC123', $maritimoNode->getAttribute('Matricula'));
    }

    public function testUsaElMapperDeFerroviarioCuandoCorresponde(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteMinima();
        $cartaPorte->Mercancias->Autotransporte = null;

        $ferroviario = new CartaPorteTransporteFerroviario();
        $ferroviario->TipoDeServicio = '01';
        $ferroviario->TipoDeTrafico = '01';
        $cartaPorte->Mercancias->TransporteFerroviario = $ferroviario;

        // Act
        $xpath = $this->xpathDe($cartaPorte);

        // Assert
        $nodo = $xpath->query('/cartaporte31:CartaPorte/cartaporte31:Mercancias/cartaporte31:TransporteFerroviario')->item(0);
        $this->assertSame('01', $nodo->getAttribute('TipoDeServicio'));
    }

    public function testLanzaExcepcionSiNingunMedioDeTransporteEstaPresente(): void
    {
        // Arrange: Mercancias sin NINGÚN medio de transporte
        $cartaPorte = $this->cartaPorteMinima();
        $cartaPorte->Mercancias->Autotransporte = null;

        // Assert
        $this->expectException(CartaPorteValidationException::class);
        $this->expectExceptionMessageMatches('/No se encontró un medio de transporte válido/');

        // Act
        $doc = new \DOMDocument();
        $this->mapper->toXmlElement($doc, $cartaPorte);
    }

    public function testMapeaFiguraTransporteConPartesTransporte(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteMinima();

        $tipoFigura = new CartaPorteTipoFigura();
        $tipoFigura->TipoFigura = '01';
        $tipoFigura->NombreFigura = 'Juan Pérez';

        $parte = new \ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteParteTransporte();
        $parte->ParteTransporte = 'ParteX';
        $tipoFigura->addParteTransporte($parte);

        $figuraTransporte = new CartaPorteFiguraTransporte();
        $figuraTransporte->addTipoFigura($tipoFigura);
        $cartaPorte->addFiguraTransporte($figuraTransporte);

        // Act
        $xpath = $this->xpathDe($cartaPorte);

        // Assert
        $figuraQuery = '/cartaporte31:CartaPorte/cartaporte31:FiguraTransporte/cartaporte31:TiposFigura';
        $this->assertSame('Juan Pérez', $this->atributo($xpath, $figuraQuery, 'NombreFigura'));

        $parteQuery = "{$figuraQuery}/cartaporte31:PartesTransporte";
        $this->assertSame('ParteX', $this->atributo($xpath, $parteQuery, 'ParteTransporte'));
    }

    public function testXmlGeneradoEsParseableSinErrores(): void
    {
        // Arrange
        $cartaPorte = $this->cartaPorteMinima();

        // Act
        $doc = new \DOMDocument();
        $doc->appendChild($this->mapper->toXmlElement($doc, $cartaPorte));

        libxml_use_internal_errors(true);
        $xmlString = $doc->saveXML();
        $docVerificacion = new \DOMDocument();
        $cargoBien = $docVerificacion->loadXML($xmlString);
        $errores = libxml_get_errors();
        libxml_clear_errors();

        // Assert
        $this->assertTrue($cargoBien);
        $this->assertEmpty($errores);
    }

    // --- Helpers ---

    private function cartaPorteMinima(): CartaPorte
    {
        $cartaPorte = new CartaPorte();
        $cartaPorte->IdCCP = 'CCP5AA6B145-F17D-4C47-9A3C-8C8A6EAA2A5B';
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
        $mercancias->PesoBrutoTotal = 500.0;
        $mercancias->UnidadPeso = 'KGM';
        $mercancias->NumTotalMercancias = 1;

        $mercancia = new CartaPorteMercancia();
        $mercancia->BienesTransp = '10101501';
        $mercancia->Descripcion = 'Producto de prueba';
        $mercancia->Cantidad = 10.0;
        $mercancia->ClaveUnidad = 'XBX';
        $mercancia->PesoEnKg = 500.0;
        $mercancias->addMercancia($mercancia);

        $autotransporte = new CartaPorteAutotransporte();
        $autotransporte->PermSCT = 'TPAF01';
        $autotransporte->NumPermisoSCT = 'PERM123456';

        $iv = new CartaPorteIdentificacionVehicular();
        $iv->ConfigVehicular = 'VTP02';
        $iv->PesoBrutoVehicular = 3500.0;
        $iv->PlacaVM = 'ABC1234';
        $iv->AnioModeloVM = '2020';
        $autotransporte->IdentificacionVehicular = $iv;

        $seguros = new CartaPorteSeguros();
        $seguros->AseguraRespCivil = 'AseguradoraTest';
        $seguros->PolizaRespCivil = 'POL123456';
        $autotransporte->Seguros = $seguros;

        $mercancias->Autotransporte = $autotransporte;
        $cartaPorte->Mercancias = $mercancias;

        return $cartaPorte;
    }

    private function remolque(): CartaPorteRemolque
    {
        $remolque = new CartaPorteRemolque();
        $remolque->SubTipoRem = 'CTR001';
        $remolque->Placa = 'XYZ9876';

        return $remolque;
    }

    private function xpathDe(CartaPorte $cartaPorte): \DOMXPath
    {
        $doc = new \DOMDocument();
        $doc->appendChild($this->mapper->toXmlElement($doc, $cartaPorte));

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('cartaporte31', self::NS_CP);

        return $xpath;
    }

    private function atributo(\DOMXPath $xpath, string $query, string $atributo): ?string
    {
        $nodo = $xpath->query($query)->item(0);

        return $nodo?->getAttribute($atributo);
    }
}