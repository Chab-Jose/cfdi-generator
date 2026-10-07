<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogos;

/** Catálogo c_UsoCFDI (CFDI 4.0). */
enum UsoCfdi: string
{
    case AdquisicionDeMercancias = 'G01';
    case DevolucionesDescuentosOBonificaciones = 'G02';
    case GastosEnGeneral = 'G03';
    case Construcciones = 'I01';
    case MobiliarioYEquipoDeOficinaPorInversiones = 'I02';
    case EquipoDeTransporte = 'I03';
    case EquipoDeComputoYAccesorios = 'I04';
    case DadosTroquelesMoldesMatricesYOtrosActivos = 'I05';
    case ComunicacionesTelefonicas = 'I06';
    case ComunicacionesSatelitales = 'I07';
    case OtraMaquinariaYEquipo = 'I08';
    case HonorariosMedicosDentalesYGastosHospitalarios = 'D01';
    case GastosMedicosPorIncapacidadODiscapacidad = 'D02';
    case GastosFunerales = 'D03';
    case Donativos = 'D04';
    case InteresesRealesEfectivamentePagadosPorCreditosHipotecarios = 'D05';
    case AportacionesVoluntariasAlSAR = 'D06';
    case PrimasPorSegurosDeGastosMedicos = 'D07';
    case GastosDeTransportacionEscolarObligatoria = 'D08';
    case DepositosEnCuentasParaElAhorroPensiones = 'D09';
    case PagosPorServiciosEducativos = 'D10';
    case SinEfectosFiscales = 'S01';
    case Pagos = 'CP01';
    case Nomina = 'CN01';

    public function descripcion(): string
    {
        return match ($this) {
            self::AdquisicionDeMercancias => 'Adquisición de mercancías',
            self::DevolucionesDescuentosOBonificaciones => 'Devoluciones, descuentos o bonificaciones',
            self::GastosEnGeneral => 'Gastos en general',
            self::Construcciones => 'Construcciones',
            self::MobiliarioYEquipoDeOficinaPorInversiones => 'Mobiliario y equipo de oficina por inversiones',
            self::EquipoDeTransporte => 'Equipo de transporte',
            self::EquipoDeComputoYAccesorios => 'Equipo de cómputo y accesorios',
            self::DadosTroquelesMoldesMatricesYOtrosActivos => 'Dados, troqueles, moldes, matrices y otros activos',
            self::ComunicacionesTelefonicas => 'Comunicaciones telefónicas',
            self::ComunicacionesSatelitales => 'Comunicaciones satelitales',
            self::OtraMaquinariaYEquipo => 'Otra maquinaria y equipo',
            self::HonorariosMedicosDentalesYGastosHospitalarios => 'Honorarios médicos, dentales y gastos hospitalarios',
            self::GastosMedicosPorIncapacidadODiscapacidad => 'Gastos médicos por incapacidad o discapacidad',
            self::GastosFunerales => 'Gastos funerales',
            self::Donativos => 'Donativos',
            self::InteresesRealesEfectivamentePagadosPorCreditosHipotecarios => 'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación)',
            self::AportacionesVoluntariasAlSAR => 'Aportaciones voluntarias al SAR',
            self::PrimasPorSegurosDeGastosMedicos => 'Primas por seguros de gastos médicos',
            self::GastosDeTransportacionEscolarObligatoria => 'Gastos de transportación escolar obligatoria',
            self::DepositosEnCuentasParaElAhorroPensiones => 'Depósitos en cuentas para el ahorro, pensiones',
            self::PagosPorServiciosEducativos => 'Pagos por servicios educativos (colegiaturas)',
            self::SinEfectosFiscales => 'Sin efectos fiscales',
            self::Pagos => 'Pagos',
            self::Nomina => 'Nómina',
        };
    }
}