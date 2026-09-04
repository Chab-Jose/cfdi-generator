<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Domain;

final class CsdCredential
{
    public function __construct(
        public readonly string $noCertificado,
        public readonly string $certificadoBase64,
        public readonly string $llavePrivadaPem,
        public readonly ?string $rfc = null,
    ) {}
}