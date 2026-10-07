<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogos;

/**
 * Catálogo c_RegimenFiscal (CFDI 4.0).
 *
 * Fuente: Anexo 20 SAT, catálogo CFDI 4.0 vigente.
 * ⚠️ Verifica contra el catálogo oficial del SAT antes de cada release
 *    que modifique este archivo — el SAT puede agregar/retirar claves
 *    vía Resolución Miscelánea Fiscal.
 */
enum RegimenFiscal: string
{
    case GeneralDeLeyPersonasMorales = '601';
    case PersonasMoralesConFinesNoLucrativos = '603';
    case SueldosYSalariosEIngresosAsimiladosASalarios = '605';
    case Arrendamiento = '606';
    case RegimenDeEnajenacionOAdquisicionDeBienes = '607';
    case DemasIngresos = '608';
    case ResidentesEnElExtranjeroSinEstablecimientoPermanente = '610';
    case IngresosPorDividendos = '611';
    case PersonasFisicasConActividadesEmpresarialesYProfesionales = '612';
    case IngresosPorIntereses = '614';
    case RegimenDeIngresosPorObtencionDePremios = '615';
    case SinObligacionesFiscales = '616';
    case SociedadesCooperativasDeProduccionQueOptanPorDiferirSusIngresos = '620';
    case IncorporacionFiscal = '621';
    case ActividadesAgricolasGanaderasSilvicolasYPesqueras = '622';
    case OpcionalParaGruposDeSociedades = '623';
    case Coordinados = '624';
    case ActividadesEmpresarialesConIngresosATravesDePlataformasTecnologicas = '625';
    case RegimenSimplificadoDeConfianza = '626';

    public function descripcion(): string
    {
        return match ($this) {
            self::GeneralDeLeyPersonasMorales => 'General de Ley Personas Morales',
            self::PersonasMoralesConFinesNoLucrativos => 'Personas Morales con Fines no Lucrativos',
            self::SueldosYSalariosEIngresosAsimiladosASalarios => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
            self::Arrendamiento => 'Arrendamiento',
            self::RegimenDeEnajenacionOAdquisicionDeBienes => 'Régimen de Enajenación o Adquisición de Bienes',
            self::DemasIngresos => 'Demás ingresos',
            self::ResidentesEnElExtranjeroSinEstablecimientoPermanente => 'Residentes en el Extranjero sin Establecimiento Permanente en México',
            self::IngresosPorDividendos => 'Ingresos por Dividendos (socios y accionistas)',
            self::PersonasFisicasConActividadesEmpresarialesYProfesionales => 'Personas Físicas con Actividades Empresariales y Profesionales',
            self::IngresosPorIntereses => 'Ingresos por intereses',
            self::RegimenDeIngresosPorObtencionDePremios => 'Régimen de los ingresos por obtención de premios',
            self::SinObligacionesFiscales => 'Sin obligaciones fiscales',
            self::SociedadesCooperativasDeProduccionQueOptanPorDiferirSusIngresos => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
            self::IncorporacionFiscal => 'Incorporación Fiscal',
            self::ActividadesAgricolasGanaderasSilvicolasYPesqueras => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
            self::OpcionalParaGruposDeSociedades => 'Opcional para Grupos de Sociedades',
            self::Coordinados => 'Coordinados',
            self::ActividadesEmpresarialesConIngresosATravesDePlataformasTecnologicas => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
            self::RegimenSimplificadoDeConfianza => 'Régimen Simplificado de Confianza',
        };
    }
}