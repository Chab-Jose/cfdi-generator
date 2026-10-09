<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Exceptions\PagosCalculoException;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\FormaPagoMatriz;
use ChabJose\CfdiGenerator\Utils\Xml\NormalizaValorCatalogoTrait;

final class PagoValidator
{
    use NormalizaValorCatalogoTrait;

    public function validar(PagosPago $pago): void
    {
        $this->validarFormaDePagoNoEs99($pago);
        $this->validarTipoCadenaPago($pago);
        $this->validarCuentaOrdenante($pago);
        $this->validarCuentaBeneficiaria($pago);
        // + lo que ya tuvieras: MonedaP≠XXX, TipoCambioP requerido si MonedaP≠MXN, etc.
    }

    private function validarFormaDePagoNoEs99(PagosPago $pago): void
    {
        if ($this->valorEscalar($pago->FormaDePagoP) === '99') {
            throw new PagosCalculoException("FormaDePagoP no puede ser '99' en el complemento de Pagos.");
        }
    }

    private function validarTipoCadenaPago(PagosPago $pago): void
    {
        $formaPago = $this->valorEscalar($pago->FormaDePagoP);
        $tienePago = $pago->TipoCadPago !== null;

        if ($tienePago && !FormaPagoMatriz::permiteTipoCadenaPago($formaPago)) {
            throw new PagosCalculoException("FormaDePagoP '{$formaPago}' no admite TipoCadPago.");
        }

        if ($tienePago) {
            foreach (['CertPago', 'CadPago', 'SelloPago'] as $campo) {
                if ($pago->{$campo} === null) {
                    throw new PagosCalculoException("{$campo} es obligatorio cuando TipoCadPago está presente.");
                }
            }
        } else {
            foreach (['CertPago', 'CadPago', 'SelloPago'] as $campo) {
                if ($pago->{$campo} !== null) {
                    throw new PagosCalculoException("{$campo} no debe registrarse si TipoCadPago no está presente.");
                }
            }
        }
    }


    // dentro de la clase, asumiendo que ya usa NormalizaValorCatalogoTrait o agrégalo:

    private function validarCuentaOrdenante(PagosPago $pago): void
    {
        $formaPago = $this->valorEscalar($pago->FormaDePagoP);

        if (!FormaPagoMatriz::aplicaCuentaOrdenante($formaPago)) {
            if ($pago->RfcEmisorCtaOrd !== null || $pago->CtaOrdenante !== null) {
                throw new PagosCalculoException(
                    "FormaDePagoP '{$formaPago}' no admite RfcEmisorCtaOrd/CtaOrdenante."
                );
            }
            return;
        }

        if ($pago->CtaOrdenante !== null) {
            $patron = FormaPagoMatriz::patronCuentaOrdenante($formaPago);
            if (preg_match('/^(' . $patron . ')$/', $pago->CtaOrdenante) !== 1) {
                throw new PagosCalculoException(
                    "CtaOrdenante no cumple el patrón requerido para FormaDePagoP '{$formaPago}'."
                );
            }
        }

        if (
            $pago->RfcEmisorCtaOrd === 'XEXX010101000'
            && FormaPagoMatriz::requiereNomBancoOrdExtSiExtranjero($formaPago)
            && ($pago->NomBancoOrdExt === null || $pago->NomBancoOrdExt === '')
        ) {
            throw new PagosCalculoException(
                "NomBancoOrdExt es obligatorio cuando RfcEmisorCtaOrd es 'XEXX010101000' (FormaDePagoP '{$formaPago}')."
            );
        }
    }

    private function validarCuentaBeneficiaria(PagosPago $pago): void
    {
        $formaPago = $this->valorEscalar($pago->FormaDePagoP);

        if (!FormaPagoMatriz::aplicaCuentaBeneficiaria($formaPago)) {
            if ($pago->RfcEmisorCtaBen !== null || $pago->CtaBeneficiario !== null) {
                throw new PagosCalculoException(
                    "FormaDePagoP '{$formaPago}' no admite RfcEmisorCtaBen/CtaBeneficiario."
                );
            }
            return;
        }

        if ($pago->CtaBeneficiario !== null) {
            $patron = FormaPagoMatriz::patronCuentaBeneficiaria($formaPago);
            if (preg_match('/^(' . $patron . ')$/', $pago->CtaBeneficiario) !== 1) {
                throw new PagosCalculoException(
                    "CtaBeneficiario no cumple el patrón requerido para FormaDePagoP '{$formaPago}'."
                );
            }
        }
    }
}
