<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Integration;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeduccion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeducciones;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepciones;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaReceptor;
use ChabJose\CfdiGenerator\NominaGenerator;
use ChabJose\CfdiGenerator\Services\CadenaOriginalService;
use ChabJose\CfdiGenerator\Services\CsdLoader;
use ChabJose\CfdiGenerator\Services\Sellador;
use PHPUnit\Framework\TestCase;

class NominaFullFlowTest extends TestCase
{
    private const RUTA_CER = __DIR__ . '/../Fixtures/csd/prueba.cer';
    private const RUTA_KEY = __DIR__ . '/../Fixtures/csd/prueba.key';

    private function password(): string
    {
        return getenv('CSD_PASSWORD') ?: '12345678a';
    }

    public function testFlujoCompletoDeUnCfdiDeNominaQuedaSelladoYVerificable(): void
    {
        // Arrange
        $csdLoader = new CsdLoader();
        $csd = $csdLoader->cargar(self::RUTA_CER, self::RUTA_KEY, $this->password());

        $sellador = new Sellador(new CadenaOriginalService());

        $nomina = $this->nominaDePrueba();

        // Act
        $xmlSellado = NominaGenerator::make(sellador: $sellador)
            ->comprobante(lugarExpedicion: '06600')
            ->emisor(rfc: 'AAA010101AAA', nombre: 'MI EMPRESA SA DE CV', regimenFiscal: '601')
            ->receptor(rfc: 'XAXX010101000', nombre: 'JUAN PEREZ', domicilioFiscalReceptor: '01000')
            ->nomina($nomina)
            ->sellar($csd)
            ->buildXml();

        // Assert: XML bien formado
        libxml_clear_errors(); // defensivo: descarta cualquier residuo previo
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $cargoBien = $doc->loadXML($xmlSellado);
        $errores = libxml_get_errors();
        libxml_clear_errors();

        $this->assertTrue($cargoBien);
        $this->assertEmpty($errores);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xpath->registerNamespace('nomina12', 'http://www.sat.gob.mx/nomina12');

        // Assert: reglas estructurales fijas del CFDI tipo Nómina
        $comprobante = $xpath->query('/cfdi:Comprobante')->item(0);
        $this->assertSame('N', $comprobante->getAttribute('TipoDeComprobante'));
        $this->assertSame('MXN', $comprobante->getAttribute('Moneda'));
        $this->assertSame('99', $comprobante->getAttribute('FormaPago'));
        $this->assertSame('PUE', $comprobante->getAttribute('MetodoPago'));

        $receptor = $xpath->query('/cfdi:Comprobante/cfdi:Receptor')->item(0);
        $this->assertSame('605', $receptor->getAttribute('RegimenFiscalReceptor'));
        $this->assertSame('CN01', $receptor->getAttribute('UsoCFDI'));

        // Assert: el Concepto fijo con montos derivados de la Nómina
        $concepto = $xpath->query('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto')->item(0);
        $this->assertSame('84111505', $concepto->getAttribute('ClaveProdServ'));
        $this->assertSame('ACT', $concepto->getAttribute('ClaveUnidad'));
        $this->assertSame('Pago de nómina', $concepto->getAttribute('Descripcion'));
        $this->assertFalse($concepto->hasAttribute('NoIdentificacion'));

        // ValorUnitario = TotalPercepciones (3180.51) + TotalOtrosPagos (0, sin otros pagos)
        $this->assertSame('3180.51', $concepto->getAttribute('ValorUnitario'));
        $this->assertSame('3180.51', $concepto->getAttribute('Importe'));
        // Descuento = TotalDeducciones
        $this->assertSame('400.00', $concepto->getAttribute('Descuento'));

        // Assert: aritmética estándar del Comprobante (SubTotal - Descuento = Total)
        $this->assertSame('3180.51', $comprobante->getAttribute('SubTotal'));
        $this->assertSame('400.00', $comprobante->getAttribute('Descuento'));
        $this->assertSame('2780.51', $comprobante->getAttribute('Total'));

        // Assert: el complemento de Nómina está presente con sus totales calculados
        $nominaNode = $xpath->query('/cfdi:Comprobante/cfdi:Complemento/nomina12:Nomina')->item(0);
        $this->assertSame('3180.51', $nominaNode->getAttribute('TotalPercepciones'));
        $this->assertSame('400.00', $nominaNode->getAttribute('TotalDeducciones'));

        // Assert: el sello es criptográficamente válido
        $this->assertNotEmpty($comprobante->getAttribute('Sello'));
        $this->assertSame($csd->noCertificado, $comprobante->getAttribute('NoCertificado'));
    }

    public function testConOtrosPagosSumaCorrectamenteAlValorUnitarioDelConcepto(): void
    {
        // Arrange
        $csdLoader = new CsdLoader();
        $csd = $csdLoader->cargar(self::RUTA_CER, self::RUTA_KEY, $this->password());
        $sellador = new Sellador(new CadenaOriginalService());

        $nomina = $this->nominaDePrueba();
        $nomina->addOtroPago($this->otroPagoSimple(importe: 500.00));

        // Act
        $xmlSellado = NominaGenerator::make(sellador: $sellador)
            ->comprobante(lugarExpedicion: '06600')
            ->emisor(rfc: 'AAA010101AAA', nombre: 'MI EMPRESA SA DE CV', regimenFiscal: '601')
            ->receptor(rfc: 'XAXX010101000', nombre: 'JUAN PEREZ', domicilioFiscalReceptor: '01000')
            ->nomina($nomina)
            ->sellar($csd)
            ->buildXml();

        // Assert
        $doc = new \DOMDocument();
        $doc->loadXML($xmlSellado);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');

        $concepto = $xpath->query('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto')->item(0);

        // ValorUnitario = TotalPercepciones (3180.51) + TotalOtrosPagos (500.00) = 3680.51
        $this->assertSame('3680.51', $concepto->getAttribute('ValorUnitario'));
    }

    // --- Helper ---

    private function nominaDePrueba(): Nomina
    {
        $nomina = new Nomina();
        $nomina->TipoNomina = 'O';
        $nomina->FechaPago = '2026-09-15';
        $nomina->FechaInicialPago = '2026-09-01';
        $nomina->FechaFinalPago = '2026-09-15';
        $nomina->NumDiasPagados = 15.0;

        $receptor = new NominaReceptor();
        $receptor->Curp = 'PEJJ800101HDFRRN01';
        $receptor->TipoContrato = '01';
        $receptor->TipoRegimen = '02';
        $receptor->NumEmpleado = '001';
        $receptor->PeriodicidadPago = '04';
        $receptor->ClaveEntFed = 'CMX';
        $nomina->Receptor = $receptor;

        $percepciones = new NominaPercepciones();

        $sueldo = new NominaPercepcion();
        $sueldo->TipoPercepcion = '001';
        $sueldo->Clave = 'P001';
        $sueldo->Concepto = 'Sueldos y salarios';
        $sueldo->ImporteGravado = 3030.51;
        $sueldo->ImporteExento = 0.0;
        $percepciones->addPercepcion($sueldo);

        $primaVacacional = new NominaPercepcion();
        $primaVacacional->TipoPercepcion = '021';
        $primaVacacional->Clave = 'P021';
        $primaVacacional->Concepto = 'Prima vacacional';
        $primaVacacional->ImporteGravado = 0.0;
        $primaVacacional->ImporteExento = 150.00;
        $percepciones->addPercepcion($primaVacacional);

        $nomina->Percepciones = $percepciones;

        $deducciones = new NominaDeducciones();

        $isr = new NominaDeduccion();
        $isr->TipoDeduccion = '002';
        $isr->Clave = 'D002';
        $isr->Concepto = 'ISR';
        $isr->Importe = 400.00;
        $deducciones->addDeduccion($isr);

        $nomina->Deducciones = $deducciones;

        return $nomina;
    }

    private function otroPagoSimple(float $importe): \ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaOtroPago
    {
        $otroPago = new \ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaOtroPago();
        $otroPago->TipoOtroPago = '004';
        $otroPago->Clave = 'OP004';
        $otroPago->Concepto = 'Compensación';
        $otroPago->Importe = $importe;

        return $otroPago;
    }
}