<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Integration;

use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestos;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosTraslado;
use ChabJose\CfdiGenerator\Models\ComprobanteEmisor;
use ChabJose\CfdiGenerator\Models\ComprobanteReceptor;
use ChabJose\CfdiGenerator\Services\CadenaOriginalService;
use ChabJose\CfdiGenerator\Services\ComprobanteBuilder;
use ChabJose\CfdiGenerator\Services\ComprobanteImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\ComprobanteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\CsdLoader;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use ChabJose\CfdiGenerator\Services\Sellador;
use ChabJose\CfdiGenerator\Services\XmlMapper;
use PHPUnit\Framework\TestCase;

/**
 * Test de integración de punta a punta: usa TODAS las implementaciones
 * reales (sin mocks) para validar que el flujo completo produzca un
 * CFDI sellado y criptográficamente verificable.
 */
class ComprobanteFullFlowTest extends TestCase
{
    private const RUTA_CER = __DIR__ . '/../Fixtures/csd/prueba.cer';
    private const RUTA_KEY = __DIR__ . '/../Fixtures/csd/prueba.key';
    private const PASSWORD = '12345678a';

    private ComprobanteBuilder $builder;
    private XmlMapper $xmlMapper;
    private CadenaOriginalService $cadenaOriginalService;
    private Sellador $sellador;
    private CsdLoader $csdLoader;

    protected function setUp(): void
    {
        $factorResolver = new FactorImpuestoResolver();

        $this->builder = new ComprobanteBuilder(
            new ConceptoImpuestosCalculator($factorResolver),
            new ConceptoCalculator(),
            new ComprobanteImpuestosCalculator(),
            new ComprobanteTotalesCalculator(),
        );

        $this->xmlMapper = new XmlMapper();
        $this->cadenaOriginalService = new CadenaOriginalService();
        $this->sellador = new Sellador($this->cadenaOriginalService);
        $this->csdLoader = new CsdLoader();
    }

    public function testFlujoCompletoProduceUnCfdiSelladoYVerificable(): void
    {
        // ── Arrange: comprobante crudo con datos reales de negocio ──
        $comprobante = $this->comprobanteDePrueba();
        $csd = $this->csdLoader->cargar(self::RUTA_CER, self::RUTA_KEY, self::PASSWORD);

        // El NoCertificado/Certificado se conocen ANTES de calcular, porque
        // vienen del CSD, no de la lógica de negocio del comprobante.
        $comprobante->NoCertificado = $csd->noCertificado;
        $comprobante->Certificado = $csd->certificadoBase64;

        // ── Act, paso 1: calcular Importes, Impuestos y Totales ──
        $comprobante = $this->builder->build($comprobante);

        // ── Act, paso 2: mapear a XML (sin Sello todavía) ──
        $xmlSinSello = $this->xmlMapper->toXml($comprobante);

        // ── Act, paso 3: generar cadena original con el XSLT real del SAT ──
        $cadenaOriginal = $this->cadenaOriginalService->generar($xmlSinSello);

        // ── Act, paso 4: sellar con el CSD real ──
        $sello = $this->sellador->sellar($cadenaOriginal, $csd);
        $comprobante->Sello = $sello;

        // ── Act, paso 5: generar el XML final ya con el Sello incluido ──
        $xmlFinal = $this->xmlMapper->toXml($comprobante);

        // ── Assert: cálculos correctos ──
        $this->assertSame(1000.0, $comprobante->SubTotal);
        $this->assertSame(160.0, $comprobante->Impuestos->TotalImpuestosTrasladados);
        $this->assertSame(1160.0, $comprobante->Total);

        // ── Assert: cadena original tiene forma válida y contiene los datos ──
        $this->assertStringStartsWith('||', $cadenaOriginal);
        $this->assertStringContainsString('1000.00', $cadenaOriginal);

        // ── Assert: el sello es un base64 válido y no vacío ──
        $this->assertNotEmpty($sello);
        $this->assertNotFalse(base64_decode($sello, true));

        // ── Assert: EL SELLO ES CRIPTOGRÁFICAMENTE VÁLIDO contra el certificado ──
        $this->assertTrue(
            $this->verificarSelloConCertificado($cadenaOriginal, $sello, $csd->certificadoBase64),
            'El sello debe ser verificable con la llave pública del certificado usado para firmar.'
        );

        // ── Assert: el XML final es válido y contiene el Sello ──
        libxml_clear_errors(); // defensivo: descarta cualquier residuo previo
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $cargoBien = $doc->loadXML($xmlFinal);
        $erroresXml = libxml_get_errors();
        libxml_clear_errors();

        $this->assertTrue($cargoBien);
        $this->assertEmpty($erroresXml);
        $this->assertStringContainsString('Sello="', $xmlFinal);
    }

    public function testDosComprobantesDiferentesProducenSellosDiferentes(): void
    {
        // Arrange
        $csd = $this->csdLoader->cargar(self::RUTA_CER, self::RUTA_KEY, self::PASSWORD);

        $comprobanteUno = $this->builder->build($this->comprobanteDePrueba());
        $comprobanteUno->NoCertificado = $csd->noCertificado;
        $comprobanteUno->Certificado = $csd->certificadoBase64;

        $comprobanteDos = $this->comprobanteDePrueba();
        $comprobanteDos->Conceptos[0]->ValorUnitario = 5000.00; // dato distinto
        $comprobanteDos = $this->builder->build($comprobanteDos);
        $comprobanteDos->NoCertificado = $csd->noCertificado;
        $comprobanteDos->Certificado = $csd->certificadoBase64;

        // Act
        $selloUno = $this->sellarComprobante($comprobanteUno, $csd);
        $selloDos = $this->sellarComprobante($comprobanteDos, $csd);

        // Assert
        $this->assertNotSame($selloUno, $selloDos);
    }

    // --- Helpers ---

    private function sellarComprobante(Comprobante $comprobante, $csd): string
    {
        $xml = $this->xmlMapper->toXml($comprobante);
        $cadenaOriginal = $this->cadenaOriginalService->generar($xml);

        return $this->sellador->sellar($cadenaOriginal, $csd);
    }

    private function verificarSelloConCertificado(string $cadenaOriginal, string $sello, string $certificadoBase64): bool
    {
        $certificadoPem = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split($certificadoBase64, 64, "\n")
            . "-----END CERTIFICATE-----\n";

        $llavePublica = openssl_pkey_get_public($certificadoPem);

        if ($llavePublica === false) {
            return false;
        }

        return openssl_verify($cadenaOriginal, base64_decode($sello), $llavePublica, OPENSSL_ALGO_SHA256) === 1;
    }

    private function comprobanteDePrueba(): Comprobante
    {
        $comprobante = new Comprobante();
        $comprobante->TipoDeComprobante = 'I';
        $comprobante->Moneda = 'MXN';
        $comprobante->LugarExpedicion = '24090';
        $comprobante->Exportacion = '01';
        $comprobante->Fecha = '2026-09-07T12:00:00';

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

        $concepto = new ComprobanteConcepto();
        $concepto->ClaveProdServ = '01010101';
        $concepto->Descripcion = 'Producto de prueba';
        $concepto->Cantidad = 1.0;
        $concepto->ClaveUnidad = 'H87';
        $concepto->ValorUnitario = 1000.00;
        $concepto->ObjetoImp = '02';

        $traslado = new ComprobanteConceptoImpuestosTraslado();
        $traslado->Base = 1000.00;
        $traslado->Impuesto = '002';
        $traslado->TipoFactor = 'Tasa';
        $traslado->TasaOCuota = 0.16;

        $impuestos = new ComprobanteConceptoImpuestos();
        $impuestos->addTraslado($traslado);
        $concepto->Impuestos = $impuestos;

        $comprobante->Conceptos = [$concepto];


        return $comprobante;
    }
}
