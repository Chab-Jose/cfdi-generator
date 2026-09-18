<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionado;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDRRetencionDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoImpuestosP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoImpuestosPTrasladoP;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosTotales;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagosXmlMapper;
use PHPUnit\Framework\TestCase;

class PagosXmlMapperTest extends TestCase
{
    private const NS_PAGO20 = 'http://www.sat.gob.mx/Pagos20';

    private PagosXmlMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PagosXmlMapper();
    }

    public function testSoportaSoloInstanciasDePagos(): void
    {
        $this->assertTrue($this->mapper->soporta(new Pagos()));
        $this->assertFalse($this->mapper->soporta(new \stdClass()));
    }

    public function testExponeElNamespaceYSchemaLocationCorrectos(): void
    {
        $this->assertSame('pago20', $this->mapper->namespacePrefix());
        $this->assertSame(self::NS_PAGO20, $this->mapper->namespaceUri());
        $this->assertStringContainsString('Pagos20.xsd', $this->mapper->schemaLocation());
    }

    public function testGeneraNodoRaizPagosConVersion(): void
    {
        // Arrange
        $pagos = $this->pagosMinimo();

        // Act
        $xpath = $this->xpathDe($pagos);

        // Assert
        $nodos = $xpath->query('/pago20:Pagos');
        $this->assertSame(1, $nodos->length);
        $this->assertSame('2.0', $nodos->item(0)->getAttribute('Version'));
    }

    public function testMapeaNodoTotalesConMontoTotalPagos(): void
    {
        // Arrange
        $pagos = $this->pagosMinimo();
        $pagos->Totales = new PagosTotales();
        $pagos->Totales->MontoTotalPagos = 1160.00;
        $pagos->Totales->TotalTrasladosBaseIVA16 = 1000.00;
        $pagos->Totales->TotalTrasladosImpuestoIVA16 = 160.00;

        // Act
        $xpath = $this->xpathDe($pagos);

        // Assert
        $this->assertSame('1160.00', $this->atributo($xpath, '/pago20:Pagos/pago20:Totales', 'MontoTotalPagos'));
        $this->assertSame('1000.00', $this->atributo($xpath, '/pago20:Pagos/pago20:Totales', 'TotalTrasladosBaseIVA16'));
    }

    public function testOmiteTotalesCuandoEsNull(): void
    {
        // Arrange
        $pagos = $this->pagosMinimo();
        $pagos->Totales = null;

        // Act
        $xpath = $this->xpathDe($pagos);

        // Assert
        $this->assertSame(0, $xpath->query('/pago20:Pagos/pago20:Totales')->length);
    }

    public function testMapeaMultiplesNodosPagoEnOrden(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pagos->addPago($this->pagoSimple(monto: 1000.00));
        $pagos->addPago($this->pagoSimple(monto: 500.00));

        // Act
        $xpath = $this->xpathDe($pagos);

        // Assert
        $nodos = $xpath->query('/pago20:Pagos/pago20:Pago');
        $this->assertSame(2, $nodos->length);
        $this->assertSame('1000.00', $nodos->item(0)->getAttribute('Monto'));
        $this->assertSame('500.00', $nodos->item(1)->getAttribute('Monto'));
    }

    public function testOmiteTipoCambioPCuandoEsNull(): void
    {
        // Arrange: pago en MXN, sin TipoCambioP
        $pagos = $this->pagosMinimo();

        // Act
        $xpath = $this->xpathDe($pagos);
        $nodoPago = $xpath->query('/pago20:Pagos/pago20:Pago')->item(0);

        // Assert
        $this->assertFalse($nodoPago->hasAttribute('TipoCambioP'));
    }

    public function testMapeaDoctoRelacionadoConCamposRequeridos(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pago = $this->pagoSimple(monto: 1160.00);
        $pago->addDoctoRelacionado($this->doctoRelacionadoSimple());
        $pagos->addPago($pago);

        // Act
        $xpath = $this->xpathDe($pagos);

        // Assert
        $query = '/pago20:Pagos/pago20:Pago/pago20:DoctoRelacionado';
        $this->assertSame('00000000-0000-0000-0000-000000000000', $this->atributo($xpath, $query, 'IdDocumento'));
        $this->assertSame('1', $this->atributo($xpath, $query, 'NumParcialidad'));
        $this->assertSame('0.00', $this->atributo($xpath, $query, 'ImpSaldoInsoluto'));
    }

    public function testMapeaImpuestosDRConTrasladoYRetencion(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pago = $this->pagoSimple(monto: 1160.00);
        $docto = $this->doctoRelacionadoSimple();

        $impuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();

        $traslado = new PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR();
        $traslado->BaseDR = 1000.00;
        $traslado->ImpuestoDR = '002';
        $traslado->TipoFactorDR = 'Tasa';
        $traslado->TasaOCuotaDR = 0.16;
        $traslado->ImporteDR = 160.00;
        $impuestosDR->addTrasladoDR($traslado);

        $retencion = new PagosPagoDoctoRelacionadoImpuestosDRRetencionDR();
        $retencion->BaseDR = 1000.00;
        $retencion->ImpuestoDR = '001';
        $retencion->TipoFactorDR = 'Tasa';
        $retencion->TasaOCuotaDR = 0.10;
        $retencion->ImporteDR = 100.00;
        $impuestosDR->addRetencionDR($retencion);

        $docto->ImpuestosDR = $impuestosDR;
        $pago->addDoctoRelacionado($docto);
        $pagos->addPago($pago);

        // Act
        $xpath = $this->xpathDe($pagos);

        // Assert
        $baseQuery = '/pago20:Pagos/pago20:Pago/pago20:DoctoRelacionado/pago20:ImpuestosDR';
        $this->assertSame('160.00', $this->atributo($xpath, "{$baseQuery}/pago20:TrasladosDR/pago20:TrasladoDR", 'ImporteDR'));
        $this->assertSame('100.00', $this->atributo($xpath, "{$baseQuery}/pago20:RetencionesDR/pago20:RetencionDR", 'ImporteDR'));
    }

    public function testMapeaImpuestosPAgregadoDelPago(): void
    {
        // Arrange
        $pagos = new Pagos();
        $pago = $this->pagoSimple(monto: 1160.00);

        $trasladoP = new PagosPagoImpuestosPTrasladoP();
        $trasladoP->BaseP = 1000.00;
        $trasladoP->ImpuestoP = '002';
        $trasladoP->TipoFactorP = 'Tasa';
        $trasladoP->TasaOCuotaP = 0.16;
        $trasladoP->ImporteP = 160.00;

        $pago->ImpuestosP = new PagosPagoImpuestosP();
        $pago->ImpuestosP->addTrasladoP($trasladoP);
        $pagos->addPago($pago);

        // Act
        $xpath = $this->xpathDe($pagos);

        // Assert
        $query = '/pago20:Pagos/pago20:Pago/pago20:ImpuestosP/pago20:TrasladosP/pago20:TrasladoP';
        $this->assertSame('160.00', $this->atributo($xpath, $query, 'ImporteP'));
    }

    public function testXmlGeneradoEsParseableSinErrores(): void
    {
        // Arrange: un Pagos completo, con DoctoRelacionado, ImpuestosDR/P y Totales
        $pagos = new Pagos();
        $pago = $this->pagoSimple(monto: 1160.00);
        $pago->addDoctoRelacionado($this->doctoRelacionadoSimple());
        $pagos->addPago($pago);
        $pagos->Totales = new PagosTotales();
        $pagos->Totales->MontoTotalPagos = 1160.00;

        // Act
        $doc = new \DOMDocument();
        $doc->appendChild($doc->importNode($this->mapper->toXmlElement(new \DOMDocument(), $pagos), true));
        // Nota: usamos un DOMDocument temporal para toXmlElement y luego importamos
        // el nodo a otro documento, simulando cómo lo usaría XmlMapper real.

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

    private function pagosMinimo(): Pagos
    {
        $pagos = new Pagos();
        $pagos->addPago($this->pagoSimple(monto: 1000.00));

        return $pagos;
    }

    private function pagoSimple(float $monto): PagosPago
    {
        $pago = new PagosPago();
        $pago->FechaPago = '2026-09-11T12:00:00';
        $pago->FormaDePagoP = '03';
        $pago->MonedaP = 'MXN';
        $pago->Monto = $monto;

        return $pago;
    }

    private function doctoRelacionadoSimple(): PagosPagoDoctoRelacionado
    {
        $docto = new PagosPagoDoctoRelacionado();
        $docto->IdDocumento = '00000000-0000-0000-0000-000000000000';
        $docto->MonedaDR = 'MXN';
        $docto->NumParcialidad = 1;
        $docto->ImpSaldoAnt = 1160.00;
        $docto->ImpPagado = 1160.00;
        $docto->ImpSaldoInsoluto = 0.0;
        $docto->ObjetoImpDR = '02';

        return $docto;
    }

    private function xpathDe(Pagos $pagos): \DOMXPath
    {
        $doc = new \DOMDocument();
        $elemento = $this->mapper->toXmlElement($doc, $pagos);
        $doc->appendChild($elemento);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('pago20', self::NS_PAGO20);

        return $xpath;
    }

    private function atributo(\DOMXPath $xpath, string $query, string $atributo): ?string
    {
        $nodo = $xpath->query($query)->item(0);

        return $nodo?->getAttribute($atributo);
    }
}