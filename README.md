# CFDI Generator

[![Latest Version](https://img.shields.io/packagist/v/chabjose/cfdi-generator.svg)](https://packagist.org/packages/chabjose/cfdi-generator)
[![License](https://img.shields.io/packagist/l/chabjose/cfdi-generator.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/chabjose/cfdi-generator.svg)](composer.json)

Generador de CFDI 4.0 (Comprobante Fiscal Digital por Internet) para México,
independiente de framework, con una API fluida para construir, calcular y
sellar comprobantes fiscales conforme al esquema del SAT.

> **Nota sobre alcance**: este paquete genera y sella el XML del CFDI. El
> timbrado (envío a un PAC) **no está incluido** — cada proyecto conecta su
> propio Proveedor Autorizado de Certificación. Ver [Alcance](#alcance) más abajo.

## Características

- ✅ API fluida para construir comprobantes: `Comprobante → Emisor → Receptor → Conceptos`
- ✅ Cálculo automático de Importes, Impuestos (Traslados/Retenciones), SubTotal, Descuento y Total
- ✅ Mapeo a XML válido según el esquema CFDI 4.0 del SAT
- ✅ Sellado digital completo (cadena original + firma SHA256) usando el XSLT oficial del SAT
- ✅ Carga de CSD (.cer/.key) con conversión DER→PEM incluida
- ✅ Arquitectura extensible: cada pieza (cálculo, mapeo, sellado, validación) es sustituible vía contratos
- ✅ Cobertura de tests amplia, incluyendo verificación criptográfica real del sello

## Requisitos

- PHP >= 8.1
- Extensiones: `ext-dom`, `ext-openssl`, `ext-xsl`, `ext-libxml`

## Instalación

```bash
composer require chabjose/cfdi-generator
```

## Uso básico

```php
use ChabJose\CfdiGenerator\CfdiGenerator;
use ChabJose\CfdiGenerator\Services\CsdLoader;
use ChabJose\CfdiGenerator\Services\Sellador;
use ChabJose\CfdiGenerator\Services\CadenaOriginalService;

$csd = (new CsdLoader())->cargar(
    rutaCer: '/ruta/certificado.cer',
    rutaKey: '/ruta/llave.key',
    password: 'contraseña_csd',
);

$sellador = new Sellador(new CadenaOriginalService());

$xmlSellado = CfdiGenerator::make(sellador: $sellador)
    ->comprobante(
        tipoDeComprobante: 'I',
        moneda: 'MXN',
        lugarExpedicion: '24090',
    )
    ->emisor(
        rfc: 'XAXX010101000',
        nombre: 'ACME SA DE CV',
        regimenFiscal: '601',
    )
    ->receptor(
        rfc: 'XEXX010101000',
        nombre: 'PUBLICO EN GENERAL',
        domicilioFiscalReceptor: '24090',
        regimenFiscalReceptor: '616',
        usoCFDI: 'S01',
    )
    ->addConcepto($concepto) // ver ejemplo completo abajo
    ->sellar($csd)
    ->buildXml();
```

### Armando un Concepto con impuestos

```php
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestos;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoImpuestosTraslado;

$concepto = new ComprobanteConcepto();
$concepto->ClaveProdServ = '01010101';
$concepto->Descripcion = 'Producto de prueba';
$concepto->Cantidad = 1.0;
$concepto->ClaveUnidad = 'H87';
$concepto->ValorUnitario = 1000.00;
$concepto->ObjetoImp = '02';

$traslado = new ComprobanteConceptoImpuestosTraslado();
$traslado->Base = 1000.00;
$traslado->Impuesto = '002'; // IVA
$traslado->TipoFactor = 'Tasa';
$traslado->TasaOCuota = 0.16;

$impuestos = new ComprobanteConceptoImpuestos();
$impuestos->addTraslado($traslado);
$concepto->Impuestos = $impuestos;
```

El paquete calcula automáticamente `Importe` del concepto y de cada impuesto,
así como `SubTotal`, `Descuento` y `Total` del comprobante — no es necesario
calcularlos manualmente, aunque si ya traes un valor desde otro sistema
(ERP, POS), el paquete lo respeta y no lo sobreescribe.

## Alcance

Este paquete cubre la **generación y sellado** de CFDI 4.0. Explícitamente
**no incluye**:

| Fuera de alcance | Por qué |
|---|---|
| Timbrado (conexión a PAC) | Cada PAC tiene su propia API; conecta el tuyo implementando tu propio cliente sobre el XML que este paquete genera |
| Complementos (Nómina, Pagos, Carta Porte, INE, IEDU, Comercio Exterior) | Roadmap futuro — ver [Roadmap](#roadmap) |
| Representación impresa (PDF) | Fuera del alcance de esta versión |

## Arquitectura

```
CfdiGenerator (facade fluido)
    └── ComprobanteBuilder (orquesta el cálculo)
            ├── ConceptoImpuestosCalculator
            ├── ConceptoCalculator
            ├── ComprobanteImpuestosCalculator
            └── ComprobanteTotalesCalculator

XmlMapper          → Comprobante (modelo) → XML
CadenaOriginalService → XML → Cadena Original (XSLT oficial SAT)
Sellador           → Cadena Original + CSD → Sello digital
CsdLoader          → .cer/.key → CsdCredential
```

Cada pieza está definida por un contrato en `Contracts/` — puedes sustituir
cualquier implementación (por ejemplo, un `TotalesCalculatorInterface`
personalizado) sin tocar el resto del paquete.

## Testing

El paquete incluye tests unitarios y de integración. Los tests que requieren
un CSD real (sellado, cadena original) necesitan un CSD de **pruebas** del SAT:

```bash
composer install
vendor/bin/phpunit
```

Ver [`tests/Fixtures/csd/README.md`](tests/Fixtures/csd/README.md) para
instrucciones de cómo obtener un CSD de pruebas.

## Roadmap

- [ ] Complemento de Pagos 2.0
- [ ] Complemento de Nómina 1.2
- [ ] Complemento de Carta Porte
- [ ] Contrato `TimbradoInterface` (opcional, sin implementación propia)
- [ ] Representación impresa (PDF)

## Contribuir

Los pull requests son bienvenidos. Para cambios grandes, abre un issue
primero para discutir qué te gustaría cambiar.

## Licencia

[MIT](LICENSE)