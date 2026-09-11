<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteEmisor;
use ChabJose\CfdiGenerator\Models\ComprobanteReceptor;
use ChabJose\CfdiGenerator\Services\XmlMapper;
use PHPUnit\Framework\TestCase;

class XmlMapperTest extends TestCase
{
    private XmlMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new XmlMapper();
    }

    public function testGeneraNodoRaizConNamespaceCorrecto(): void
    {
        // Arrange
        $comprobante = $this->comprobanteMinimo();

        // Act
        $xml = $this->mapper->toXml($comprobante);
        $xpath = $this->xpathDe($xml);

        // Assert: el namespace es la parte más crítica para que el SAT acepte el XML
        $nodos = $xpath->query('/cfdi:Comprobante');
        $this->assertSame(1, $nodos->length);
        $this->assertSame('http://www.sat.gob.mx/cfd/4', $nodos->item(0)->namespaceURI);
    }

    public function testMapeaAtributosBasicosDelComprobante(): void
    {
        // Arrange
        $comprobante = $this->comprobanteMinimo();
        $comprobante->Serie = 'A';
        $comprobante->Folio = '123';
        $comprobante->SubTotal = 1000.00;
        $comprobante->Total = 1160.00;

        // Act
        $xml = $this->mapper->toXml($comprobante);
        $xpath = $this->xpathDe($xml);

        // Assert
        $this->assertSame('A', $this->atributo($xpath, '/cfdi:Comprobante', 'Serie'));
        $this->assertSame('123', $this->atributo($xpath, '/cfdi:Comprobante', 'Folio'));
        $this->assertSame('1000.00', $this->atributo($xpath, '/cfdi:Comprobante', 'SubTotal'));
        $this->assertSame('1160.00', $this->atributo($xpath, '/cfdi:Comprobante', 'Total'));
    }

    public function testOmiteAtributosOpcionalesCuandoSonNull(): void
    {
        // Arrange: Serie, Folio, Descuento NO se definen (quedan null)
        $comprobante = $this->comprobanteMinimo();

        // Act
        $xml = $this->mapper->toXml($comprobante);
        $xpath = $this->xpathDe($xml);
        $nodo = $xpath->query('/cfdi:Comprobante')->item(0);

        // Assert: el atributo NO debe existir en el XML, ni siquiera como cadena vacía.
        // El SAT rechaza atributos opcionales presentes pero vacíos.
        $this->assertFalse($nodo->hasAttribute('Serie'));
        $this->assertFalse($nodo->hasAttribute('Folio'));
        $this->assertFalse($nodo->hasAttribute('Descuento'));
    }

    public function testMapeaNodoEmisorConSusAtributos(): void
    {
        // Arrange
        $comprobante = $this->comprobanteMinimo();
        $comprobante->Emisor = new ComprobanteEmisor();
        $comprobante->Emisor->Rfc = 'XAXX010101000';
        $comprobante->Emisor->Nombre = 'ACME SA DE CV';
        $comprobante->Emisor->RegimenFiscal = '601';

        // Act
        $xml = $this->mapper->toXml($comprobante);
        $xpath = $this->xpathDe($xml);

        // Assert
        $this->assertSame('XAXX010101000', $this->atributo($xpath, '/cfdi:Comprobante/cfdi:Emisor', 'Rfc'));
        $this->assertSame('601', $this->atributo($xpath, '/cfdi:Comprobante/cfdi:Emisor', 'RegimenFiscal'));
    }

    public function testMapeaNodoReceptorConCamposObligatoriosDe40(): void
    {
        // Arrange: verificamos específicamente los campos NUEVOS de 4.0
        $comprobante = $this->comprobanteMinimo();
        $comprobante->Receptor = new ComprobanteReceptor();
        $comprobante->Receptor->Rfc = 'XEXX010101000';
        $comprobante->Receptor->Nombre = 'PUBLICO EN GENERAL';
        $comprobante->Receptor->DomicilioFiscalReceptor = '24090';
        $comprobante->Receptor->RegimenFiscalReceptor = '616';
        $comprobante->Receptor->UsoCFDI = 'S01';

        // Act
        $xml = $this->mapper->toXml($comprobante);
        $xpath = $this->xpathDe($xml);

        // Assert
        $this->assertSame('24090', $this->atributo($xpath, '/cfdi:Comprobante/cfdi:Receptor', 'DomicilioFiscalReceptor'));
        $this->assertSame('616', $this->atributo($xpath, '/cfdi:Comprobante/cfdi:Receptor', 'RegimenFiscalReceptor'));
        $this->assertSame('S01', $this->atributo($xpath, '/cfdi:Comprobante/cfdi:Receptor', 'UsoCFDI'));
    }

    public function testMapeaMultiplesConceptosEnOrden(): void
    {
        // Arrange
        $comprobante = $this->comprobanteMinimo();
        $comprobante->Conceptos = [
            $this->conceptoSimple('01010101', 'Concepto uno'),
            $this->conceptoSimple('02020202', 'Concepto dos'),
        ];

        // Act
        $xml = $this->mapper->toXml($comprobante);
        $xpath = $this->xpathDe($xml);

        // Assert
        $nodos = $xpath->query('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto');
        $this->assertSame(2, $nodos->length);
        $this->assertSame('01010101', $nodos->item(0)->getAttribute('ClaveProdServ'));
        $this->assertSame('02020202', $nodos->item(1)->getAttribute('ClaveProdServ'));
    }

    public function testFormateaDecimalesConDosPosicionesSiempre(): void
    {
        // Arrange: un ValorUnitario "redondo" no debe perder los .00
        $comprobante = $this->comprobanteMinimo();
        $comprobante->Conceptos = [$this->conceptoSimple('01010101', 'Test', valorUnitario: 100.0)];

        // Act
        $xml = $this->mapper->toXml($comprobante);
        $xpath = $this->xpathDe($xml);

        // Assert: el SAT exige "100.00", no "100" ni "100.0"
        $this->assertSame('100.00', $this->atributo($xpath, '//cfdi:Concepto', 'ValorUnitario'));
    }

    public function testXmlGeneradoEsParseableSinErrores(): void
    {
        // Arrange: comprobante completo con TODOS los campos requeridos de Emisor/Receptor
        $comprobante = $this->comprobanteMinimo();

        $comprobante->Emisor = new ComprobanteEmisor();
        $comprobante->Emisor->Rfc = 'XAXX010101000';
        $comprobante->Emisor->Nombre = 'ACME SA DE CV';
        $comprobante->Emisor->RegimenFiscal = '601';

        $comprobante->Receptor = new ComprobanteReceptor();
        $comprobante->Receptor->Rfc = 'XEXX010101000';
        $comprobante->Receptor->Nombre = 'PUBLICO EN GENERAL';
        $comprobante->Receptor->DomicilioFiscalReceptor = '24090';
        $comprobante->Receptor->RegimenFiscalReceptor = '616';
        $comprobante->Receptor->UsoCFDI = 'S01';

        $comprobante->Conceptos = [$this->conceptoSimple('01010101', 'Test')];

        // Act
        $xml = $this->mapper->toXml($comprobante);

        // Assert: no debe lanzar warnings/errores de libxml al parsearlo
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $cargoCorrectamente = $doc->loadXML($xml);
        $errores = libxml_get_errors();
        libxml_clear_errors();

        $this->assertTrue($cargoCorrectamente);
        $this->assertEmpty($errores);
    }

    public function testLanzaExcepcionSiFaltaUnCampoRequerido(): void
    {
        // Arrange
        $comprobante = $this->comprobanteSinNoCertificado();

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/NoCertificado/');

        // Act
        $this->mapper->toXml($comprobante);
    }

    public function testLanzaExcepcionSiFaltaTipoDeComprobante(): void
    {
        // Arrange: mismo patrón, otro campo requerido distinto
        $comprobante = $this->comprobanteMinimo();
        $comprobante->TipoDeComprobante = '';

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/TipoDeComprobante/');

        // Act
        $this->mapper->toXml($comprobante);
    }

    public function testLanzaExcepcionSiEmisorSinNombre(): void
    {
        // Arrange
        $comprobante = $this->comprobanteMinimo();
        $comprobante->Emisor = new ComprobanteEmisor();
        $comprobante->Emisor->Rfc = 'XAXX010101000';
        // Nombre y RegimenFiscal quedan vacíos intencionalmente

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Nombre/');

        // Act
        $this->mapper->toXml($comprobante);
    }

    // --- Helpers ---

    private function comprobanteMinimo(): Comprobante
    {
        $comprobante = new Comprobante();
        $comprobante->TipoDeComprobante = 'I';
        $comprobante->Moneda = 'MXN';
        $comprobante->LugarExpedicion = '24090';
        $comprobante->Exportacion = '01';
        $comprobante->Fecha = '2026-09-07T12:00:00';
        $comprobante->NoCertificado = '30001000000500003416'; // requerido, aunque sea un valor de prueba

        return $comprobante;
    }

    private function comprobanteSinNoCertificado(): Comprobante
    {
        $comprobante = $this->comprobanteMinimo();
        $comprobante->NoCertificado = ''; // explícitamente vacío, para probar la validación

        return $comprobante;
    }

    private function conceptoSimple(string $claveProdServ, string $descripcion, float $valorUnitario = 100.0): ComprobanteConcepto
    {
        $concepto = new ComprobanteConcepto();
        $concepto->ClaveProdServ = $claveProdServ;
        $concepto->Descripcion = $descripcion;
        $concepto->Cantidad = 1.0;
        $concepto->ClaveUnidad = 'H87';
        $concepto->ValorUnitario = $valorUnitario;
        $concepto->Importe = $valorUnitario;
        $concepto->ObjetoImp = '02';

        return $concepto;
    }

    private function xpathDe(string $xml): \DOMXPath
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');

        return $xpath;
    }

    private function atributo(\DOMXPath $xpath, string $query, string $atributo): ?string
    {
        $nodo = $xpath->query($query)->item(0);

        return $nodo?->getAttribute($atributo);
    }
}
