# CFDI Generator

[![Tests](https://github.com/Chab-Jose/cfdi-generator/actions/workflows/tests.yml/badge.svg)](https://github.com/Chab-Jose/cfdi-generator/actions/workflows/tests.yml)
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
- ✅ **Complemento de Pagos (REP) 2.0**, con cálculo automático de impuestos agrupados y conversión de moneda a MXN en el nodo `Totales`
- ✅ **Complemento de Nómina 1.2**, con cálculo automático de Percepciones/Deducciones/Totales, incluyendo la regla oficial de exclusión mutua entre Sueldos y Jubilación/Pensión/Retiro
- ✅ Arquitectura extensible: cada pieza (cálculo, mapeo, sellado, validación) es sustituible vía contratos, y nuevos complementos se agregan sin modificar el core (`ComplementoRegistry`)
- ✅ Cobertura de tests amplia, incluyendo verificación criptográfica real del sello

## Requisitos

- PHP >= 8.1
- Extensiones: `ext-dom`, `ext-openssl`, `ext-xsl`, `ext-libxml`

## Instalación

```bash
composer require chabjose/cfdi-generator
```

## Uso básico: CFDI de Ingreso/Egreso

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

## Complemento de Pagos (REP) 2.0

Para CFDIs de tipo "P" (Pago), usa el facade dedicado `PagoGenerator` — no
`CfdiGenerator` — ya que un comprobante de Pago tiene reglas estructurales
propias (Concepto fijo, SubTotal/Total en cero, Moneda "XXX") que este
facade configura automáticamente.

```php
use ChabJose\CfdiGenerator\PagoGenerator;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosDoctoRelacionado;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosImpuestosDR;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosTrasladoDR;

$traslado = new PagosTrasladoDR();
$traslado->BaseDR = 1000.00;
$traslado->ImpuestoDR = '002';
$traslado->TipoFactorDR = 'Tasa';
$traslado->TasaOCuotaDR = 0.16;

$impuestosDR = new PagosImpuestosDR();
$impuestosDR->addTrasladoDR($traslado);

$docto = new PagosDoctoRelacionado();
$docto->IdDocumento = '11111111-2222-3333-4444-555555555555'; // UUID de la factura pagada
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
```

El paquete calcula automáticamente los tres niveles de agregación del
complemento (impuestos por documento relacionado → impuestos agregados por
pago → totales del complemento), incluyendo conversión automática a MXN
cuando un pago viene en moneda extranjera.

> ⚠️ **Reglas cruzadas del SAT pendientes**: el SAT publica un catálogo
> (`c_FormaPago`) con reglas de obligatoriedad de ciertos campos (cuentas
> bancarias, certificación SPEI) según la forma de pago. Este paquete valida
> las reglas cruzadas que están documentadas de forma clara y verificable
> (ver `PagoValidator`), pero **no valida aún la matriz completa por cada
> código de forma de pago** — esa validación queda pendiente hasta
> incorporar el catálogo oficial completo.

## Complemento de Nómina 1.2

Para CFDIs de tipo "N" (Nómina), usa el facade dedicado `NominaGenerator`.
Igual que con Pagos, este facade configura automáticamente las reglas fijas
del SAT para este tipo de comprobante (`TipoDeComprobante="N"`,
`Moneda="MXN"`, `FormaPago="99"`, `MetodoPago="PUE"`,
`Receptor.RegimenFiscalReceptor="605"`, `Receptor.UsoCFDI="CN01"`, y el
Concepto fijo `ClaveProdServ="84111505"`), por lo que `receptor()` en este
facade **no** pide `regimenFiscalReceptor` ni `usoCFDI` — ya están fijados
según la guía oficial del SAT.

```php
use ChabJose\CfdiGenerator\NominaGenerator;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaReceptor;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepciones;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeduccion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeducciones;

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
$sueldo->TipoPercepcion = '001'; // Sueldos, Salarios Rayas y Jornales
$sueldo->Clave = 'P001';
$sueldo->Concepto = 'Sueldos y salarios';
$sueldo->ImporteGravado = 3030.51;
$sueldo->ImporteExento = 0.0;
$percepciones->addPercepcion($sueldo);
$nomina->Percepciones = $percepciones;

$deducciones = new NominaDeducciones();
$isr = new NominaDeduccion();
$isr->TipoDeduccion = '002'; // ISR
$isr->Clave = 'D002';
$isr->Concepto = 'ISR';
$isr->Importe = 400.00;
$deducciones->addDeduccion($isr);
$nomina->Deducciones = $deducciones;

$xmlSellado = NominaGenerator::make(sellador: $sellador)
    ->comprobante(lugarExpedicion: '06600')
    ->emisor(rfc: 'AAA010101AAA', nombre: 'MI EMPRESA SA DE CV', regimenFiscal: '601')
    ->receptor(rfc: 'XAXX010101000', nombre: 'JUAN PEREZ', domicilioFiscalReceptor: '01000')
    ->nomina($nomina)
    ->sellar($csd)
    ->buildXml();
```

El paquete calcula automáticamente `TotalGravado`/`TotalExento`/`TotalSueldos`
por percepciones, `TotalImpuestosRetenidos`/`TotalOtrasDeducciones` por
deducciones, los totales a nivel `Nomina`, y propaga esos totales al Concepto
fijo del Comprobante (`ValorUnitario = TotalPercepciones + TotalOtrosPagos`,
`Descuento = TotalDeducciones`), de donde el resto del cálculo del CFDI
(`SubTotal`, `Total`) sigue la misma aritmética estándar del paquete.

> ℹ️ Soporta percepciones especiales como Horas Extra, Acciones o Títulos,
> Jubilación/Pensión/Retiro (con la regla oficial de exclusión mutua respecto
> a `TotalSueldos`), Separación e Indemnización, así como Subsidio al Empleo,
> Compensación de Saldos a Favor e Incapacidades.

## Alcance

Este paquete cubre la **generación y sellado** de CFDI 4.0. Explícitamente
**no incluye**:

| Fuera de alcance | Por qué |
|---|---|
| Timbrado (conexión a PAC) | Cada PAC tiene su propia API; conecta el tuyo implementando tu propio cliente sobre el XML que este paquete genera |
| Otros complementos (Carta Porte, INE, IEDU, Comercio Exterior) | Roadmap futuro — ver [Roadmap](#roadmap) |
| Representación impresa (PDF) | Fuera del alcance de esta versión |

## Arquitectura

```
CfdiGenerator / PagoGenerator / NominaGenerator
    (facades fluidos, extienden AbstractCfdiGenerator)
    └── ComprobanteBuilder (orquesta el cálculo del CFDI base)
            ├── ConceptoImpuestosCalculator
            ├── ConceptoCalculator
            ├── ComprobanteImpuestosCalculator
            └── ComprobanteTotalesCalculator

    └── PagosBuilder (orquesta el cálculo del complemento de Pagos)
            ├── DoctoRelacionadoImpuestosCalculator
            ├── PagoImpuestosCalculator
            └── PagosTotalesCalculator (con conversión de moneda a MXN)

    └── NominaBuilder (orquesta el cálculo del complemento de Nómina)
            ├── NominaPercepcionesCalculator
            ├── NominaDeduccionesCalculator
            └── NominaTotalesCalculator

XmlMapper          → Comprobante (modelo) → XML
    └── ComplementoRegistry → despacha cada complemento a su propio XmlMapper
          ├── PagosXmlMapper → pago20:Pagos
          └── NominaXmlMapper → nomina12:Nomina

CadenaOriginalService → XML → Cadena Original (XSLT oficial SAT)
Sellador           → Cadena Original + CSD → Sello digital
CsdLoader          → .cer/.key → CsdCredential
```

Cada pieza está definida por un contrato en `Contracts/` — puedes sustituir
cualquier implementación sin tocar el resto del paquete. Nuevos complementos
se agregan implementando `ComplementoXmlMapperInterface` y registrándolos en
`ComplementoRegistry`, sin modificar `XmlMapper`.

## Testing

El paquete incluye tests unitarios y de integración. Los tests que requieren
un CSD real (sellado, cadena original, complementos de Pagos y Nómina)
necesitan un CSD de **pruebas** del SAT:

```bash
composer install
vendor/bin/phpunit
```

Ver [`tests/Fixtures/csd/README.md`](tests/Fixtures/csd/README.md) para
instrucciones de cómo obtener un CSD de pruebas.

## Roadmap

- [ ] Validación de la matriz completa `c_FormaPago` para el complemento de Pagos
- [ ] Complemento de Carta Porte
- [ ] Contrato `TimbradoInterface` (opcional, sin implementación propia)
- [ ] Representación impresa (PDF)

## Reportar un problema

¿Encontraste un bug? Abre un Issue usando la plantilla correspondiente. 
Para vulnerabilidades de seguridad, revisa SECURITY.md en vez de abrir un Issue público.

## Contribuir

Los pull requests son bienvenidos. Revisa [CONTRIBUTING.md](CONTRIBUTING.md)
para las convenciones del proyecto antes de abrir uno.

## Licencia

[MIT](LICENSE)