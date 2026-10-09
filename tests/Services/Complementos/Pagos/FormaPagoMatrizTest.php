<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Pagos;

use ChabJose\CfdiGenerator\Services\Complementos\Pagos\FormaPagoMatriz;
use PHPUnit\Framework\TestCase;

final class FormaPagoMatrizTest extends TestCase
{
    public static function codigosBancarizadosProvider(): array
    {
        return [
            ['02'], ['03'], ['04'], ['05'], ['06'], ['28'], ['29'],
        ];
    }

    public static function codigosNoBancarizadosProvider(): array
    {
        return [
            ['01'], ['08'], ['12'], ['17'], ['30'], ['31'],
        ];
    }

    /** @dataProvider codigosBancarizadosProvider */
    public function testAplicaCuentaOrdenanteParaCodigosBancarizados(string $codigo): void
    {
        $this->assertTrue(FormaPagoMatriz::aplicaCuentaOrdenante($codigo));
        $this->assertNotNull(FormaPagoMatriz::patronCuentaOrdenante($codigo));
    }

    /** @dataProvider codigosNoBancarizadosProvider */
    public function testNoAplicaCuentaOrdenanteParaCodigosNoBancarizados(string $codigo): void
    {
        $this->assertFalse(FormaPagoMatriz::aplicaCuentaOrdenante($codigo));
    }

    public function testCodigo06NoAplicaCuentaBeneficiaria(): void
    {
        // Única excepción: 06 está bancarizado pero no admite beneficiario.
        $this->assertTrue(FormaPagoMatriz::aplicaCuentaOrdenante('06'));
        $this->assertFalse(FormaPagoMatriz::aplicaCuentaBeneficiaria('06'));
    }

    public function testSolo03PermiteTipoCadenaPago(): void
    {
        $this->assertTrue(FormaPagoMatriz::permiteTipoCadenaPago('03'));
        $this->assertFalse(FormaPagoMatriz::permiteTipoCadenaPago('02'));
        $this->assertFalse(FormaPagoMatriz::permiteTipoCadenaPago('04'));
    }

    public static function patronesOrdenanteProvider(): array
    {
        return [
            'cheque 11 dígitos' => ['02', '12345678901', true],
            'cheque 18 dígitos' => ['02', '123456789012345678', true],
            'cheque 10 dígitos inválido' => ['02', '1234567890', false],
            'transferencia CLABE 18' => ['03', '123456789012345678', true],
            'tarjeta crédito 16 dígitos' => ['04', '1234567890123456', true],
            'tarjeta crédito 15 dígitos inválida' => ['04', '123456789012345', false],
            'tarjeta servicios 15 dígitos' => ['29', '123456789012345', true],
            'tarjeta servicios 16 dígitos' => ['29', '1234567890123456', true],
            'dinero electrónico 10 dígitos' => ['06', '1234567890', true],
            'dinero electrónico 11 dígitos inválido' => ['06', '12345678901', false],
        ];
    }

    /** @dataProvider patronesOrdenanteProvider */
    public function testPatronCuentaOrdenante(string $codigo, string $cuenta, bool $esperado): void
    {
        $patron = FormaPagoMatriz::patronCuentaOrdenante($codigo);
        $cumple = preg_match('/^(' . $patron . ')$/', $cuenta) === 1;

        $this->assertSame($esperado, $cumple);
    }
}