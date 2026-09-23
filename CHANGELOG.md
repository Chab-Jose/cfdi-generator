# Changelog

Todos los cambios notables de este paquete se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto sigue [Versionado Semántico](https://semver.org/lang/es/).

## [1.2.0] - 2026-09-15

### Agregado
- Complemento de Nómina 1.2 completo:
  - Modelos de dominio (`Nomina`, `NominaEmisor`, `NominaReceptor`,
    `NominaPercepcion`, `NominaDeduccion`, `NominaOtroPago`, `NominaIncapacidad`
    y sub-nodos especiales: `HorasExtra`, `AccionesOTitulos`,
    `JubilacionPensionRetiro`, `SeparacionIndemnizacion`, `SubsidioAlEmpleo`,
    `CompensacionSaldosAFavor`) validados contra el XSLT oficial `nomina12.xslt`
  - Cálculo de `TotalGravado`/`TotalExento`/`TotalSueldos` por percepciones
    (`NominaPercepcionesCalculator`), incluyendo `TotalJubilacionPensionRetiro`
    con la fórmula oficial de la guía de llenado del SAT (suma de
    ImporteGravado+ImporteExento de percepciones con clave 039/044, con
    exclusión mutua respecto a `TotalSueldos`)
  - Cálculo de `TotalOtrasDeducciones`/`TotalImpuestosRetenidos`
    (`NominaDeduccionesCalculator`), separando ISR (clave 002) del resto
  - Agregación de totales a nivel raíz (`NominaTotalesCalculator`)
  - Orquestación completa (`NominaBuilder`)
  - Mapeo a XML del complemento (`NominaXmlMapper`), incluyendo atributos
    con caracteres UTF-8 (`Antigüedad`, `Año`)
- `NominaGenerator`: facade dedicado para CFDIs tipo "N" (Nómina), con
  configuración automática de las reglas estructurales fijas del SAT
  (`TipoDeComprobante=N`, `Moneda=MXN`, `FormaPago=99`, `MetodoPago=PUE`,
  `Receptor.RegimenFiscalReceptor=605`, `Receptor.UsoCFDI=CN01`, Concepto
  fijo `ClaveProdServ=84111505`) y propagación automática de los totales
  del complemento al Concepto del Comprobante

## [1.1.0] - 2026-09-11

### Agregado
- Complemento de Pagos (REP) 2.0 Revisión B completo:
  - Modelos de dominio (`Pagos`, `PagosPago`, `PagosDoctoRelacionado`,
    `PagosImpuestosDR`/`PagosImpuestosP`, `PagosTotales`) validados contra
    el XSD oficial `Pagos20.xsd`
  - Cálculo de impuestos por documento relacionado
    (`DoctoRelacionadoImpuestosCalculator`), reutilizando `FactorImpuestoResolver`
  - Agregación de impuestos por Pago (`PagoImpuestosCalculator`), agrupando
    Traslados por Impuesto+TipoFactor+TasaOCuota y Retenciones por Impuesto
  - Agregación de Totales del complemento (`PagosTotalesCalculator`), con
    **conversión automática a MXN** de pagos en moneda extranjera usando
    `TipoCambioP`, y desglose por tasa de IVA (16%, 8%, 0%, Exento)
  - Orquestación completa (`PagosBuilder`)
  - Mapeo a XML del complemento (`PagosXmlMapper`)
  - Validaciones de reglas cruzadas documentadas del SAT (`PagoValidator`):
    TipoCadPago↔CertPago/CadPago/SelloPago, FormaDePagoP≠99, MonedaP≠XXX,
    TipoCambioP requerido si MonedaP≠MXN, consistencia de cuenta
    ordenante/beneficiaria
- `AbstractCfdiGenerator`: nueva clase base que factoriza la plomería común
  (Emisor, Receptor, build, buildXml, sellar) entre distintos tipos de
  comprobante, sin heredar métodos que no aplican a cada tipo
- `PagoGenerator`: facade dedicado para CFDIs tipo "P" (Pago), con
  configuración automática de las reglas estructurales del tipo (Concepto
  fijo obligatorio, SubTotal/Total en cero, Moneda "XXX")
- `ComplementoRegistry` y `ComplementoXmlMapperInterface`: mecanismo de
  despacho extensible para que futuros complementos (Nómina, Carta Porte)
  se integren sin modificar `XmlMapper`
- Soporte para múltiples complementos simultáneos en `cfdi:Complemento`
  (`ComprobanteComplemento::Any`)

### Pendiente (documentado como limitación conocida)
- El complemento de Pagos no valida aún la matriz completa de
  obligatoriedad por código de `FormaDePagoP` publicada en el catálogo
  `c_FormaPago` del SAT — solo las reglas cruzadas documentadas de forma
  verificable (ver Roadmap en README)

## [1.0.0] - 2026-09-11

### Agregado
- API fluida `CfdiGenerator::make()` para construir comprobantes CFDI 4.0
- Modelos de dominio (`Comprobante`, `Emisor`, `Receptor`, `Concepto`, `Impuestos`)
  con propiedades en PascalCase mapeando 1:1 al esquema XSD del SAT
- Cálculo automático de Importes de Concepto (`ConceptoCalculator`)
- Cálculo automático de Impuestos por Concepto: Traslados y Retenciones,
  con resolución por `TipoFactor` (Tasa/Cuota/Exento) vía `FactorImpuestoResolver`
- Agregación de Impuestos a nivel Comprobante (`ComprobanteImpuestosCalculator`),
  agrupando por Impuesto + TipoFactor + TasaOCuota (normalizando `null`/`0.0`)
- Cálculo de SubTotal, Descuento y Total (`ComprobanteTotalesCalculator`)
- Orquestación de todos los cálculos en orden correcto (`ComprobanteBuilder`)
- Mapeo a XML válido conforme al esquema CFDI 4.0 (`XmlMapper`), con validación
  de campos requeridos vs. opcionales según el schema del SAT
- Generación de cadena original usando el XSLT oficial del SAT vía
  `XSLTProcessor` (`CadenaOriginalService`)
- Sellado digital SHA256 de la cadena original (`Sellador`)
- Carga de CSD (.cer/.key) con conversión DER→PEM y extracción de
  NoCertificado/RFC (`CsdLoader`)
- Contratos (`Contracts/`) para cada servicio, permitiendo sustituir
  cualquier implementación sin modificar el resto del paquete
- Suite de tests unitarios y de integración, incluyendo verificación
  criptográfica end-to-end del sello digital contra un CSD real

### Fuera de alcance (intencional)
- Timbrado (conexión a PAC) — no incluido, cada proyecto conecta su propio PAC
- Complementos del SAT (Nómina, Pagos, Carta Porte, INE, IEDU, Comercio Exterior)
- Representación impresa (PDF)

[1.2.0]: https://github.com/Chab-Jose/cfdi-generator/releases/tag/v1.2.0
[1.1.0]: https://github.com/Chab-Jose/cfdi-generator/releases/tag/v1.1.0
[1.0.0]: https://github.com/Chab-Jose/cfdi-generator/releases/tag/v1.0.0