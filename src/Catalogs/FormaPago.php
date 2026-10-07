<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogos;

/** Catálogo c_FormaPago (CFDI 4.0). */
enum FormaPago: string
{
    case Efectivo = '01';
    case ChequeNominativo = '02';
    case TransferenciaElectronicaDeFondos = '03';
    case TarjetaDeCredito = '04';
    case MonederoElectronico = '05';
    case DineroElectronico = '06';
    case ValesDeDespensa = '08';
    case DacionEnPago = '12';
    case PagoPorSubrogacion = '13';
    case PagoPorConsignacion = '14';
    case Condonacion = '15';
    case Compensacion = '17';
    case Novacion = '23';
    case Confusion = '24';
    case RemisionDeDeuda = '25';
    case PrescripcionOCaducidad = '26';
    case ASatisfaccionDelAcreedor = '27';
    case TarjetaDeDebito = '28';
    case TarjetaDeServicios = '29';
    case AplicacionDeAnticipos = '30';
    case IntermediarioPagos = '31';
    case PorDefinir = '99';

    public function descripcion(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::ChequeNominativo => 'Cheque nominativo',
            self::TransferenciaElectronicaDeFondos => 'Transferencia electrónica de fondos',
            self::TarjetaDeCredito => 'Tarjeta de crédito',
            self::MonederoElectronico => 'Monedero electrónico',
            self::DineroElectronico => 'Dinero electrónico',
            self::ValesDeDespensa => 'Vales de despensa',
            self::DacionEnPago => 'Dación en pago',
            self::PagoPorSubrogacion => 'Pago por subrogación',
            self::PagoPorConsignacion => 'Pago por consignación',
            self::Condonacion => 'Condonación',
            self::Compensacion => 'Compensación',
            self::Novacion => 'Novación',
            self::Confusion => 'Confusión',
            self::RemisionDeDeuda => 'Remisión de deuda',
            self::PrescripcionOCaducidad => 'Prescripción o caducidad',
            self::ASatisfaccionDelAcreedor => 'A satisfacción del acreedor',
            self::TarjetaDeDebito => 'Tarjeta de débito',
            self::TarjetaDeServicios => 'Tarjeta de servicios',
            self::AplicacionDeAnticipos => 'Aplicación de anticipos',
            self::IntermediarioPagos => 'Intermediario pagos',
            self::PorDefinir => 'Por definir',
        };
    }
}