<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Exceptions\CadenaOriginalException;
use ChabJose\CfdiGenerator\Services\CadenaOriginalService;
use PHPUnit\Framework\TestCase;

class CadenaOriginalServiceTest extends TestCase
{
    private CadenaOriginalService $service;

    protected function setUp(): void
    {
        // Sin argumento: usa el XSLT default empaquetado en resources/xslt/4.0/
        $this->service = new CadenaOriginalService();
    }

    public function testGeneraCadenaOriginalConXmlCompletoValido(): void
    {
        // Act
        $cadena = $this->service->generar($this->xmlDeComprobantePrueba());

        // Assert: la cadena original del SAT siempre empieza y termina con "||"
        $this->assertStringStartsWith('||', $cadena);
        $this->assertStringEndsWith('||', $cadena);
    }

    public function testCadenaOriginalContieneLosValoresDelComprobante(): void
    {
        // Act
        $cadena = $this->service->generar($this->xmlDeComprobantePrueba());

        // Assert: confirma que los valores reales del comprobante viajan a la cadena,
        // no solo que el formato "||...||" se cumpla vacío
        $this->assertStringContainsString('4.0', $cadena);
        $this->assertStringContainsString('XAXX010101000', $cadena); // RFC Emisor
        $this->assertStringContainsString('XEXX010101000', $cadena); // RFC Receptor
        $this->assertStringContainsString('1000.00', $cadena);       // SubTotal
    }

    public function testCadenasIdenticasParaElMismoXmlSonReproducibles(): void
    {
        // Arrange: la transformación NO debe tener ningún elemento aleatorio o dependiente del tiempo
        $xml = $this->xmlDeComprobantePrueba();

        // Act
        $cadenaUno = $this->service->generar($xml);
        $cadenaDos = $this->service->generar($xml);

        // Assert
        $this->assertSame($cadenaUno, $cadenaDos);
    }

    public function testCadenasDiferentesParaDatosDiferentes(): void
    {
        // Arrange
        $xmlUno = $this->xmlDeComprobantePrueba();
        $xmlDos = str_replace('SubTotal="1000.00"', 'SubTotal="2000.00"', $xmlUno);
        $xmlDos = str_replace('Total="1160.00"', 'Total="2320.00"', $xmlDos);

        // Act
        $cadenaUno = $this->service->generar($xmlUno);
        $cadenaDos = $this->service->generar($xmlDos);

        // Assert
        $this->assertNotSame($cadenaUno, $cadenaDos);
    }

    public function testConstructorLanzaExcepcionSiElXsltNoExiste(): void
    {
        // Assert
        $this->expectException(CadenaOriginalException::class);
        $this->expectExceptionMessageMatches('/No se encontró el XSLT/');

        // Act
        new CadenaOriginalService('/ruta/que/no/existe.xslt');
    }

    public function testGenerarConXmlMalFormadoLanzaExcepcion(): void
    {
        // Arrange: XML con una etiqueta mal cerrada
        $xmlInvalido = '<cfdi:Comprobante><cfdi:Emisor Rfc="XAXX010101000"';

        // Assert
        $this->expectException(CadenaOriginalException::class);

        // Act: suprimimos el warning de libxml para que no ensucie la salida del test,
        // pero la excepción SÍ debe lanzarse igual
        libxml_use_internal_errors(true);
        $this->service->generar($xmlInvalido);
        libxml_use_internal_errors(false);
    }

    // --- Helper ---

    private function xmlDeComprobantePrueba(): string
    {
        return <<<XML
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4"
    Version="4.0" Fecha="2026-09-07T12:00:00" NoCertificado="30001000000500003416"
    SubTotal="1000.00" Moneda="MXN" Total="1160.00" TipoDeComprobante="I"
    Exportacion="01" LugarExpedicion="24090">
    <cfdi:Emisor Rfc="XAXX010101000" Nombre="ACME SA DE CV" RegimenFiscal="601"/>
    <cfdi:Receptor Rfc="XEXX010101000" Nombre="PUBLICO EN GENERAL"
        DomicilioFiscalReceptor="24090" RegimenFiscalReceptor="616" UsoCFDI="S01"/>
    <cfdi:Conceptos>
        <cfdi:Concepto ClaveProdServ="01010101" Cantidad="1" ClaveUnidad="H87"
            Descripcion="Producto de prueba" ValorUnitario="1000.00" Importe="1000.00" ObjetoImp="02"/>
    </cfdi:Conceptos>
</cfdi:Comprobante>
XML;
    }
}