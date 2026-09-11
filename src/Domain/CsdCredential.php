<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Domain;

final class CsdCredential
{
    public function __construct(
        public readonly string $noCertificado,
        public readonly string $certificadoBase64,
        public readonly string $llavePrivadaPemEncriptada,
        public readonly string $llavePrivadaPassword,
        public readonly ?string $rfc = null,
    ) {}

    public function __debugInfo(): array
    {
        return [
            'noCertificado' => $this->noCertificado,
            'rfc' => $this->rfc,
            'llavePrivadaPassword' => '***OCULTO***',
            'llavePrivadaPemEncriptada' => '***OCULTO***',
        ];
    }
}
