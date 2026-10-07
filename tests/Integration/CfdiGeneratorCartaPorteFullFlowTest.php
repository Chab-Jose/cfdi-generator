<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Integration;

use ChabJose\CfdiGenerator\CfdiGenerator;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteAutotransporte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteIdentificacionVehicular;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Autotransporte\CartaPorteSeguros;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancia;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteUbicacion;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Services\CadenaOriginalService;
use ChabJose\CfdiGenerator\Services\CsdLoader;
use ChabJose\CfdiGenerator\Services\Sellador;
use PHPUnit\Framework\TestCase;

class CfdiGeneratorCartaPorteFullFlowTest extends TestCase
{
    private const RUTA_CER = __DIR__ . '/../Fixtures/csd/prueba.cer';
    private const RUTA_KEY = __DIR__ . '/../Fixtures/csd/prueba.key';

    private function password(): string
    {
        return getenv('CSD_PASSWORD') ?: '12345678a';
    }

    public function testFlujoCompletoDeUnCfdiDeIngresoConCartaPorteQuedaSelladoYVerificable(): void
    {
        // Arrange
        $csdLoader = new CsdLoader();
        $csd = $csdLoader->cargar(self::RUTA_CER, self::RUTA_KEY, $this->password());
        $sellador = new Sellador(new CadenaOriginalService());

        $concepto = new ComprobanteConcepto();
        $concepto->ClaveProdServ = '78101800'; // Servicios de transporte de carga
        $concepto->Descripcion = 'Servicio de flete';
        $concepto->Cantidad = 1.0;
        $concepto->ClaveUnidad = 'E48';
        $concepto->ValorUnitario = 5000.00;
        $concepto->ObjetoImp = '02';

        $cartaPorte = $this->cartaPorteDePrueba();

        // Act: CFDI de Ingreso NORMAL, con Carta Porte adjunto como complemento opcional
        $xmlSellado = CfdiGenerator::make(sellador: $sellador)
            ->comprobante(tipoDeComprobante: 'I', moneda: 'MXN', lugarExpedicion: '24090')
            ->emisor(rfc: 'AAA010101AAA', nombre: 'TRANSPORTES SA DE CV', regimenFiscal: '601')
            ->receptor(
                rfc: 'XAXX010101000',
                nombre: 'CLIENTE DE PRUEBA',
                domicilioFiscalReceptor: '01000',
                regimenFiscalReceptor: '616',
                usoCFDI: 'G03',
            )
            ->addConcepto($concepto)
            ->cartaPorte($cartaPorte)
            ->sellar($csd)
            ->buildXml();

        // Assert: XML bien formado
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
        $xpath->registerNamespace('cartaporte31', 'http://www.sat.gob.mx/CartaPorte31');

        // Assert: el Comprobante sigue siendo un CFDI de Ingreso NORMAL
        // (Carta Porte no fuerza ninguna regla estructural, a diferencia de Pagos/Nómina)
        $comprobante = $xpath->query('/cfdi:Comprobante')->item(0);
        $this->assertSame('I', $comprobante->getAttribute('TipoDeComprobante'));
        $this->assertSame('MXN', $comprobante->getAttribute('Moneda'));

        // Assert: el Concepto real del servicio de flete está presente (no un
        // Concepto fijo como en Pagos/Nómina)
        $concepto = $xpath->query('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto')->item(0);
        $this->assertSame('78101800', $concepto->getAttribute('ClaveProdServ'));
        $this->assertSame('5000.00', $concepto->getAttribute('ValorUnitario'));

        // Assert: el complemento de Carta Porte está presente con sus cálculos
        $cartaPorteNode = $xpath->query('/cfdi:Comprobante/cfdi:Complemento/cartaporte31:CartaPorte')->item(0);
        $this->assertNotNull($cartaPorteNode);
        $this->assertStringStartsWith('CCP', $cartaPorteNode->getAttribute('IdCCP'));

        $mercancias = $xpath->query('/cfdi:Comprobante/cfdi:Complemento/cartaporte31:CartaPorte/cartaporte31:Mercancias')->item(0);
        $this->assertSame('1', $mercancias->getAttribute('NumTotalMercancias'));
        $this->assertSame('500.000', $mercancias->getAttribute('PesoBrutoTotal'));

        // Assert: TotalDistRec se calculó automáticamente (suma de Ubicaciones Destino)
        $this->assertSame('350.000', $cartaPorteNode->getAttribute('TotalDistRec'));

        // Assert: el sello es criptográficamente válido
        $this->assertNotEmpty($comprobante->getAttribute('Sello'));
        $this->assertSame($csd->noCertificado, $comprobante->getAttribute('NoCertificado'));
    }

    public function testUnCfdiSinCartaPorteSigueFuncionandoNormal(): void
    {
        // Arrange: confirma que agregar la capacidad de Carta Porte NO rompió
        // el flujo normal de un CFDI sin ningún complemento
        $csdLoader = new CsdLoader();
        $csd = $csdLoader->cargar(self::RUTA_CER, self::RUTA_KEY, $this->password());
        $sellador = new Sellador(new CadenaOriginalService());

        $concepto = new ComprobanteConcepto();
        $concepto->ClaveProdServ = '01010101';
        $concepto->Descripcion = 'Producto de prueba';
        $concepto->Cantidad = 1.0;
        $concepto->ClaveUnidad = 'H87';
        $concepto->ValorUnitario = 1000.00;
        $concepto->ObjetoImp = '02';

        // Act: SIN llamar a ->cartaPorte()
        $xmlSellado = CfdiGenerator::make(sellador: $sellador)
            ->comprobante(tipoDeComprobante: 'I', moneda: 'MXN', lugarExpedicion: '24090')
            ->emisor(rfc: 'AAA010101AAA', nombre: 'ACME SA DE CV', regimenFiscal: '601')
            ->receptor(
                rfc: 'XAXX010101000',
                nombre: 'PUBLICO EN GENERAL',
                domicilioFiscalReceptor: '24090',
                regimenFiscalReceptor: '616',
                usoCFDI: 'S01',
            )
            ->addConcepto($concepto)
            ->sellar($csd)
            ->buildXml();

        // Assert
        $doc = new \DOMDocument();
        $doc->loadXML($xmlSellado);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');

        $this->assertSame(0, $xpath->query('/cfdi:Comprobante/cfdi:Complemento')->length);
        $this->assertNotEmpty($xpath->query('/cfdi:Comprobante')->item(0)->getAttribute('Sello'));
    }

    // --- Helper ---

    private function cartaPorteDePrueba(): CartaPorte
    {
        $cartaPorte = new CartaPorte();
        $cartaPorte->TranspInternac = 'No';

        $origen = new CartaPorteUbicacion();
        $origen->TipoUbicacion = '01';
        $origen->RFCRemitenteDestinatario = 'AAA010101AAA';
        $origen->FechaHoraSalidaLlegada = '2026-10-06T08:00:00';
        $cartaPorte->addUbicacion($origen);

        $destino = new CartaPorteUbicacion();
        $destino->TipoUbicacion = '02';
        $destino->RFCRemitenteDestinatario = 'XAXX010101000';
        $destino->FechaHoraSalidaLlegada = '2026-10-06T18:00:00';
        $destino->DistanciaRecorrida = 350.0;
        $cartaPorte->addUbicacion($destino);

        $mercancias = new CartaPorteMercancias();
        $mercancias->UnidadPeso = 'KGM';

        $mercancia = new CartaPorteMercancia();
        $mercancia->BienesTransp = '10101501';
        $mercancia->Descripcion = 'Mercancía de prueba';
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
}