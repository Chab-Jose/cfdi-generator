<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeduccion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeducciones;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaEmisor;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaIncapacidad;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaOtroPago;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaOtroPagoSubsidioAlEmpleo;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcionAccionesOTitulos;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcionHorasExtra;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepciones;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaReceptor;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaXmlMapper;
use PHPUnit\Framework\TestCase;

class NominaXmlMapperTest extends TestCase
{
    private const NS_NOMINA12 = 'http://www.sat.gob.mx/nomina12';

    private NominaXmlMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new NominaXmlMapper();
    }

    public function testSoportaSoloInstanciasDeNomina(): void
    {
        $this->assertTrue($this->mapper->soporta(new Nomina()));
        $this->assertFalse($this->mapper->soporta(new \stdClass()));
    }

    public function testGeneraNodoRaizConAtributosRequeridos(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $nodos = $xpath->query('/nomina12:Nomina');
        $this->assertSame(1, $nodos->length);
        $this->assertSame('1.2', $nodos->item(0)->getAttribute('Version'));
        $this->assertSame('O', $nodos->item(0)->getAttribute('TipoNomina'));
    }

    public function testFormateaNumDiasPagadosConTresDecimales(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();
        $nomina->NumDiasPagados = 15.0;

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert: el ejemplo real del SAT usa "1.000", no "1.00"
        $this->assertSame('15.000', $this->atributo($xpath, '/nomina12:Nomina', 'NumDiasPagados'));
    }

    public function testMapeaAtributosConAcentoCorrectamente(): void
    {
        // Arrange: verifica específicamente Antigüedad y Año, que usan caracteres UTF-8
        $nomina = $this->nominaMinima();
        $nomina->Receptor = new NominaReceptor();
        $nomina->Receptor->Curp = 'XAXX010101HDFXXX01';
        $nomina->Receptor->TipoContrato = '01';
        $nomina->Receptor->TipoRegimen = '02';
        $nomina->Receptor->NumEmpleado = '001';
        $nomina->Receptor->PeriodicidadPago = '04';
        $nomina->Receptor->ClaveEntFed = 'CMX';
        $nomina->Receptor->Antigüedad = 'P3Y2M';

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $this->assertSame('P3Y2M', $this->atributo($xpath, '/nomina12:Nomina/nomina12:Receptor', 'Antigüedad'));
    }

    public function testMapeaEmisorConEntidadSNCF(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();
        $nomina->Emisor = new NominaEmisor();
        $nomina->Emisor->RegistroPatronal = 'A1234567890';

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $this->assertSame(
            'A1234567890',
            $this->atributo($xpath, '/nomina12:Nomina/nomina12:Emisor', 'RegistroPatronal')
        );
    }

    public function testMapeaReceptorConSubContratacion(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();
        $receptor = $this->receptorSimple();
        $receptor->addSubContratacion($this->subContratacion());
        $nomina->Receptor = $receptor;

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $query = '/nomina12:Nomina/nomina12:Receptor/nomina12:SubContratacion';
        $this->assertSame('XAXX010101000', $this->atributo($xpath, $query, 'RfcLabora'));
    }

    public function testMapeaPercepcionConHorasExtraYAccionesOTitulos(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();
        $percepciones = new NominaPercepciones();
        $percepciones->TotalGravado = 3180.51;
        $percepciones->TotalExento = 0.0;

        $percepcion = $this->percepcion('001', gravado: 3030.51, exento: 0.0);

        $horasExtra = new NominaPercepcionHorasExtra();
        $horasExtra->Dias = 5;
        $horasExtra->TipoHoras = '01';
        $horasExtra->HorasExtra = 10;
        $horasExtra->ImportePagado = 500.00;
        $percepcion->addHorasExtra($horasExtra);

        $acciones = new NominaPercepcionAccionesOTitulos();
        $acciones->ValorMercado = 10000.00;
        $acciones->PrecioAlOtorgarse = 8000.00;
        $percepcion->AccionesOTitulos = $acciones;

        $percepciones->addPercepcion($percepcion);
        $nomina->Percepciones = $percepciones;

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $horasQuery = '/nomina12:Nomina/nomina12:Percepciones/nomina12:Percepcion/nomina12:HorasExtra';
        $this->assertSame('500.00', $this->atributo($xpath, $horasQuery, 'ImportePagado'));

        $accionesQuery = '/nomina12:Nomina/nomina12:Percepciones/nomina12:Percepcion/nomina12:AccionesOTitulos';
        $this->assertSame('10000.00', $this->atributo($xpath, $accionesQuery, 'ValorMercado'));
    }

    public function testMapeaDeduccionesConVariasDeduccion(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();
        $deducciones = new NominaDeducciones();
        $deducciones->TotalImpuestosRetenidos = 850.00;
        $deducciones->addDeduccion($this->deduccion('002', 850.00));
        $nomina->Deducciones = $deducciones;

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $nodos = $xpath->query('/nomina12:Nomina/nomina12:Deducciones/nomina12:Deduccion');
        $this->assertSame(1, $nodos->length);
        $this->assertSame('850.00', $nodos->item(0)->getAttribute('Importe'));
    }

    public function testMapeaOtroPagoConSubsidioAlEmpleo(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();

        $otroPago = new NominaOtroPago();
        $otroPago->TipoOtroPago = '002';
        $otroPago->Clave = 'OP002';
        $otroPago->Concepto = 'Subsidio al empleo';
        $otroPago->Importe = 200.00;

        $subsidio = new NominaOtroPagoSubsidioAlEmpleo();
        $subsidio->SubsidioCausado = 200.00;
        $otroPago->SubsidioAlEmpleo = $subsidio;

        $nomina->addOtroPago($otroPago);

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $query = '/nomina12:Nomina/nomina12:OtrosPagos/nomina12:OtroPago/nomina12:SubsidioAlEmpleo';
        $this->assertSame('200.00', $this->atributo($xpath, $query, 'SubsidioCausado'));
    }

    public function testOmiteNodoOtrosPagosCuandoNoHayNinguno(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $this->assertSame(0, $xpath->query('/nomina12:Nomina/nomina12:OtrosPagos')->length);
    }

    public function testMapeaIncapacidad(): void
    {
        // Arrange
        $nomina = $this->nominaMinima();
        $incapacidad = new NominaIncapacidad();
        $incapacidad->DiasIncapacidad = 3;
        $incapacidad->TipoIncapacidad = '01';
        $nomina->addIncapacidad($incapacidad);

        // Act
        $xpath = $this->xpathDe($nomina);

        // Assert
        $query = '/nomina12:Nomina/nomina12:Incapacidades/nomina12:Incapacidad';
        $this->assertSame('3', $this->atributo($xpath, $query, 'DiasIncapacidad'));
    }

    public function testXmlGeneradoEsParseableSinErrores(): void
    {
        // Arrange: una nómina razonablemente completa
        $nomina = $this->nominaMinima();
        $nomina->Receptor = $this->receptorSimple();

        $percepciones = new NominaPercepciones();
        $percepciones->TotalGravado = 3030.51;
        $percepciones->TotalExento = 0.0;
        $percepciones->addPercepcion($this->percepcion('001', 3030.51, 0.0));
        $nomina->Percepciones = $percepciones;

        $deducciones = new NominaDeducciones();
        $deducciones->TotalImpuestosRetenidos = 400.00;
        $deducciones->addDeduccion($this->deduccion('002', 400.00));
        $nomina->Deducciones = $deducciones;

        // Act
        $doc = new \DOMDocument();
        $doc->appendChild($this->mapper->toXmlElement($doc, $nomina));

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

    private function nominaMinima(): Nomina
    {
        $nomina = new Nomina();
        $nomina->TipoNomina = 'O';
        $nomina->FechaPago = '2026-09-15';
        $nomina->FechaInicialPago = '2026-09-01';
        $nomina->FechaFinalPago = '2026-09-15';
        $nomina->NumDiasPagados = 15.0;

        return $nomina;
    }

    private function receptorSimple(): NominaReceptor
    {
        $receptor = new NominaReceptor();
        $receptor->Curp = 'XAXX010101HDFXXX01';
        $receptor->TipoContrato = '01';
        $receptor->TipoRegimen = '02';
        $receptor->NumEmpleado = '001';
        $receptor->PeriodicidadPago = '04';
        $receptor->ClaveEntFed = 'CMX';

        return $receptor;
    }

    private function subContratacion()
    {
        $sub = new \ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaReceptorSubContratacion();
        $sub->RfcLabora = 'XAXX010101000';
        $sub->PorcentajeTiempo = 50.0;

        return $sub;
    }

    private function percepcion(string $tipoPercepcion, float $gravado, float $exento): NominaPercepcion
    {
        $percepcion = new NominaPercepcion();
        $percepcion->TipoPercepcion = $tipoPercepcion;
        $percepcion->Clave = 'P' . $tipoPercepcion;
        $percepcion->Concepto = 'Percepción de prueba';
        $percepcion->ImporteGravado = $gravado;
        $percepcion->ImporteExento = $exento;

        return $percepcion;
    }

    private function deduccion(string $tipoDeduccion, float $importe): NominaDeduccion
    {
        $deduccion = new NominaDeduccion();
        $deduccion->TipoDeduccion = $tipoDeduccion;
        $deduccion->Clave = 'D' . $tipoDeduccion;
        $deduccion->Concepto = 'Deducción de prueba';
        $deduccion->Importe = $importe;

        return $deduccion;
    }

    private function xpathDe(Nomina $nomina): \DOMXPath
    {
        $doc = new \DOMDocument();
        $doc->appendChild($this->mapper->toXmlElement($doc, $nomina));

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('nomina12', self::NS_NOMINA12);

        return $xpath;
    }

    private function atributo(\DOMXPath $xpath, string $query, string $atributo): ?string
    {
        $nodo = $xpath->query($query)->item(0);

        return $nodo?->getAttribute($atributo);
    }
}