# Changelog

Todos los cambios notables de este paquete se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto sigue [Versionado Semántico](https://semver.org/lang/es/).

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

[1.0.0]: https://github.com/Chab-Jose/cfdi-generator/releases/tag/v1.0.0