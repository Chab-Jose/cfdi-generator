<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Pagos;

/**
 * Matriz de reglas del catálogo c_FormaPago aplicada al complemento de Pagos 2.0.
 *
 * Fuente: catCFDI_V_4.xls publicado por el SAT (hoja c_FormaPago).
 * ⚠️ Si el SAT actualiza esta hoja, hay que regenerar esta matriz —
 *    no se adivina, se transcribe directo del catálogo oficial.
 */
final class FormaPagoMatriz
{
    private const REGLAS = [
        '02' => ['ordenante' => '[0-9]{11}|[0-9]{18}', 'beneficiaria' => '[0-9]{10,11}|[0-9]{15,16}|[0-9]{18}|[A-Z0-9_]{10,50}', 'tipoCadPago' => false, 'nomBancoOrdExtSiExtranjero' => true],
        '03' => ['ordenante' => '[0-9]{10}|[0-9]{16}|[0-9]{18}', 'beneficiaria' => '[0-9]{10}|[0-9]{18}', 'tipoCadPago' => true, 'nomBancoOrdExtSiExtranjero' => true],
        '04' => ['ordenante' => '[0-9]{16}', 'beneficiaria' => '[0-9]{10,11}|[0-9]{15,16}|[0-9]{18}|[A-Z0-9_]{10,50}', 'tipoCadPago' => false, 'nomBancoOrdExtSiExtranjero' => true],
        '05' => ['ordenante' => '[0-9]{10,11}|[0-9]{15,16}|[0-9]{18}|[A-Z0-9_]{10,50}', 'beneficiaria' => '[0-9]{10,11}|[0-9]{15,16}|[0-9]{18}|[A-Z0-9_]{10,50}', 'tipoCadPago' => false, 'nomBancoOrdExtSiExtranjero' => false],
        '06' => ['ordenante' => '[0-9]{10}', 'beneficiaria' => null, 'tipoCadPago' => false, 'nomBancoOrdExtSiExtranjero' => false],
        '28' => ['ordenante' => '[0-9]{16}', 'beneficiaria' => '[0-9]{10,11}|[0-9]{15,16}|[0-9]{18}|[A-Z0-9_]{10,50}', 'tipoCadPago' => false, 'nomBancoOrdExtSiExtranjero' => true],
        '29' => ['ordenante' => '[0-9]{15,16}', 'beneficiaria' => '[0-9]{10,11}|[0-9]{15,16}|[0-9]{18}|[A-Z0-9_]{10,50}', 'tipoCadPago' => false, 'nomBancoOrdExtSiExtranjero' => true],
    ];

    /** Códigos bancarizados: 02,03,04,05,06,28,29 (admiten cuenta ordenante). */
    public static function aplicaCuentaOrdenante(string $formaPago): bool
    {
        return isset(self::REGLAS[$formaPago]);
    }

    public static function patronCuentaOrdenante(string $formaPago): ?string
    {
        return self::REGLAS[$formaPago]['ordenante'] ?? null;
    }

    /** Beneficiario aplica para 02,03,04,05,28,29 — NO para 06. */
    public static function aplicaCuentaBeneficiaria(string $formaPago): bool
    {
        return isset(self::REGLAS[$formaPago]) && self::REGLAS[$formaPago]['beneficiaria'] !== null;
    }

    public static function patronCuentaBeneficiaria(string $formaPago): ?string
    {
        return self::REGLAS[$formaPago]['beneficiaria'] ?? null;
    }

    /** Solo FormaDePagoP=03 admite TipoCadPago. */
    public static function permiteTipoCadenaPago(string $formaPago): bool
    {
        return self::REGLAS[$formaPago]['tipoCadPago'] ?? false;
    }

    public static function requiereNomBancoOrdExtSiExtranjero(string $formaPago): bool
    {
        return self::REGLAS[$formaPago]['nomBancoOrdExtSiExtranjero'] ?? false;
    }
}