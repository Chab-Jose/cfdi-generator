<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Domain;

final class ImpuestoAgrupacionKey
{
    private function __construct(
        public readonly string $impuesto,
        public readonly ?string $tipoFactor,
        public readonly ?float $tasaOCuota,
    ) {
    }

    public static function paraTraslado(string $impuesto, string $tipoFactor, ?float $tasaOCuota): self
    {
        return new self($impuesto, $tipoFactor, self::normalizarTasa($tasaOCuota));
    }

    public static function paraRetencion(string $impuesto): self
    {
        // Las retenciones a nivel Comprobante solo agrupan por Impuesto,
        // según el XSLT del SAT (no por TipoFactor ni TasaOCuota).
        return new self($impuesto, null, null);
    }

    /**
     * Trata null y 0.0 como equivalentes: ambos representan
     * "no hay tasa aplicable" para efectos de agrupación.
     */
    private static function normalizarTasa(?float $tasaOCuota): ?float
    {
        if ($tasaOCuota === null || $tasaOCuota === 0.0) {
            return null;
        }

        return $tasaOCuota;
    }

    public function toString(): string
    {
        $tasa = $this->tasaOCuota === null ? 'null' : (string) $this->tasaOCuota;

        return "{$this->impuesto}|{$this->tipoFactor}|{$tasa}";
    }
}