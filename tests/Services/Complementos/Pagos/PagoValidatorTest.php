<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Exceptions\PagosCalculoException;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagoValidator;
use PHPUnit\Framework\TestCase;

final class PagoValidatorTest extends TestCase
{
    private PagoValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PagoValidator();
    }

    private function pagoMinimo(string $formaDePagoP): PagosPago
    {
        $pago = new PagosPago();
        $pago->FormaDePagoP = $formaDePagoP;
        $pago->FechaPago = '2026-10-09T12:00:00';
        $pago->MonedaP = 'MXN';
        $pago->Monto = 1000.0;

        return $pago;
    }

    public function testRechazaFormaDePago99(): void
    {
        $pago = $this->pagoMinimo('99');

        $this->expectException(PagosCalculoException::class);
        $this->validator->validar($pago);
    }

    public function testAceptaEfectivoSinCuentas(): void
    {
        $pago = $this->pagoMinimo('01');

        $this->validator->validar($pago);
        $this->addToAssertionCount(1); // no debe lanzar excepción
    }

    public function testRechazaCuentaOrdenanteEnFormaDePagoNoBancarizada(): void
    {
        $pago = $this->pagoMinimo('01');
        $pago->CtaOrdenante = '1234567890123456';

        $this->expectException(PagosCalculoException::class);
        $this->validator->validar($pago);
    }

    public function testAceptaCuentaOrdenanteValidaParaTransferencia(): void
    {
        $pago = $this->pagoMinimo('03');
        $pago->CtaOrdenante = '123456789012345678'; // CLABE 18 dígitos

        $this->validator->validar($pago);
        $this->addToAssertionCount(1);
    }

    public function testRechazaCuentaOrdenanteConPatronInvalido(): void
    {
        $pago = $this->pagoMinimo('04'); // tarjeta de crédito, exige 16 dígitos
        $pago->CtaOrdenante = '12345'; // claramente inválido

        $this->expectException(PagosCalculoException::class);
        $this->validator->validar($pago);
    }

    public function testRequiereNomBancoOrdExtSiRfcEsGenericoExtranjero(): void
    {
        $pago = $this->pagoMinimo('03');
        $pago->CtaOrdenante = '123456789012345678';
        $pago->RfcEmisorCtaOrd = 'XEXX010101000';
        // NomBancoOrdExt no asignado a propósito

        $this->expectException(PagosCalculoException::class);
        $this->validator->validar($pago);
    }

    public function testAceptaRfcGenericoExtranjeroConNomBancoOrdExt(): void
    {
        $pago = $this->pagoMinimo('03');
        $pago->CtaOrdenante = '123456789012345678';
        $pago->RfcEmisorCtaOrd = 'XEXX010101000';
        $pago->NomBancoOrdExt = 'Banco Extranjero S.A.';

        $this->validator->validar($pago);
        $this->addToAssertionCount(1);
    }

    public function testCodigo06RechazaCuentaBeneficiaria(): void
    {
        $pago = $this->pagoMinimo('06');
        $pago->CtaOrdenante = '1234567890'; // válida para 06
        $pago->CtaBeneficiario = '123456789012345678'; // 06 nunca admite beneficiario

        $this->expectException(PagosCalculoException::class);
        $this->validator->validar($pago);
    }

    public function testRechazaTipoCadPagoFueraDeTransferencia(): void
    {
        $pago = $this->pagoMinimo('04'); // tarjeta de crédito
        $pago->TipoCadPago = '01';
        $pago->CertPago = 'cert';
        $pago->CadPago = 'cadena';
        $pago->SelloPago = 'sello';

        $this->expectException(PagosCalculoException::class);
        $this->validator->validar($pago);
    }

    public function testTipoCadPagoRequiereCertCadenaYSello(): void
    {
        $pago = $this->pagoMinimo('03');
        $pago->TipoCadPago = '01';
        // CertPago/CadPago/SelloPago no asignados a propósito

        $this->expectException(PagosCalculoException::class);
        $this->validator->validar($pago);
    }

    public function testAceptaTipoCadPagoCompleto(): void
    {
        $pago = $this->pagoMinimo('03');
        $pago->CtaOrdenante = '123456789012345678';
        $pago->TipoCadPago = '01';
        $pago->CertPago = 'cert';
        $pago->CadPago = 'cadena';
        $pago->SelloPago = 'sello';

        $this->validator->validar($pago);
        $this->addToAssertionCount(1);
    }
}