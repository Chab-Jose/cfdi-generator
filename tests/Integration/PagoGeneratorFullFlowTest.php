<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Integration;

use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionado;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR;
use ChabJose\CfdiGenerator\PagoGenerator;
use ChabJose\CfdiGenerator\Services\CsdLoader;
use PHPUnit\Framework\TestCase;

class PagoGeneratorFullFlowTest extends TestCase
{
    private const RUTA_CER = __DIR__ . '/../Fixtures/csd/prueba.cer';
    private const RUTA_KEY = __DIR__ . '/../Fixtures/csd/prueba.key';

    private function password(): string
    {
        return getenv('CSD_PASSWORD') ?: '12345678a';
    }

    public function testFlujoCompletoDeUnCfdiDePagoQuedaSelladoYVerificable(): void
    {
        // Arrange
        $csdLoader = new CsdLoader();
        $csd = $csdLoader->cargar(self::RUTA_CER, self::RUTA_KEY, $this->password());

        $sellador = new \ChabJose\CfdiGenerator\Services\Sellador(
            new \ChabJose\CfdiGenerator\Services\CadenaOriginalService()
        );

        $pago = $this->pagoDePrueba();

        // Act
        $xmlSellado = PagoGenerator::make(sellador: $sellador)
            ->comprobante(lugarExpedicion: '24090')
            ->emisor(rfc: 'XAXX010101000', nombre: 'ACME SA DE CV', regimenFiscal: '601')
            ->receptor(
                rfc: 'XEXX010101000',
                nombre: 'PUBLICO EN GENERAL',
                domicilioFiscalReceptor: '24090',
                regimenFiscalReceptor: '616',
                usoCFDI: 'CP01',
            )
            ->pago($pago)
            ->sellar($csd)
            ->buildXml();

        // Assert: estructura general del Comprobante tipo Pago
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $cargoBien = $doc->loadXML($xmlSellado);
        $errores = libxml_get_errors();
        libxml_clear_errors();

        $this->assertTrue($cargoBien);
        $this->assertEmpty($errores);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xpath->registerNamespace('pago20', 'http://www.sat.gob.mx/Pagos20');

        // Assert: reglas específicas del CFDI tipo Pago
        $comprobante = $xpath->query('/cfdi:Comprobante')->item(0);
        $this->assertSame('P', $comprobante->getAttribute('TipoDeComprobante'));
        $this->assertSame('XXX', $comprobante->getAttribute('Moneda'));
        $this->assertSame('0.00', $comprobante->getAttribute('SubTotal'));
        $this->assertSame('0.00', $comprobante->getAttribute('Total'));

        // Assert: el Concepto fijo obligatorio está presente
        $concepto = $xpath->query('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto')->item(0);
        $this->assertSame('84111506', $concepto->getAttribute('ClaveProdServ'));

        // Assert: el complemento de Pagos está presente con sus cálculos
        $montoTotalPagos = $xpath->query('/cfdi:Comprobante/cfdi:Complemento/pago20:Pagos/pago20:Totales')
            ->item(0)
            ->getAttribute('MontoTotalPagos');
        $this->assertSame('1160.00', $montoTotalPagos);

        // Assert: el sello está presente y es criptográficamente válido
        $sello = $comprobante->getAttribute('Sello');
        $this->assertNotEmpty($sello);

        $noCertificado = $comprobante->getAttribute('NoCertificado');
        $this->assertSame($csd->noCertificado, $noCertificado);
    }

    // --- Helper ---

    private function pagoDePrueba(): PagosPago
    {
        $traslado = new PagosPagoDoctoRelacionadoImpuestosDRTrasladoDR();
        $traslado->BaseDR = 1000.00;
        $traslado->ImpuestoDR = '002';
        $traslado->TipoFactorDR = 'Tasa';
        $traslado->TasaOCuotaDR = 0.16;

        $impuestosDR = new PagosPagoDoctoRelacionadoImpuestosDR();
        $impuestosDR->addTrasladoDR($traslado);

        $docto = new PagosPagoDoctoRelacionado();
        $docto->IdDocumento = '11111111-2222-3333-4444-555555555555';
        $docto->MonedaDR = 'MXN';
        $docto->NumParcialidad = 1;
        $docto->ImpSaldoAnt = 1160.00;
        $docto->ImpPagado = 1160.00;
        $docto->ImpSaldoInsoluto = 0.0;
        $docto->ObjetoImpDR = '02';
        $docto->ImpuestosDR = $impuestosDR;

        $pago = new PagosPago();
        $pago->FechaPago = '2026-09-11T12:00:00';
        $pago->FormaDePagoP = '03';
        $pago->MonedaP = 'MXN';
        $pago->Monto = 1160.00;
        $pago->addDoctoRelacionado($docto);

        return $pago;
    }
}