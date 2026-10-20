<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\Iedu;

use ChabJose\CfdiGenerator\Catalogs\NivelEducativo;

class InstEducativas
{
    public function __construct(
        public string $nombreAlumno,
        public string $CURP,
        public string|NivelEducativo $nivelEducativo,
        public string $autRVOE,
        public ?string $rfcPago = null,
        public string $version = '1.0',
    ) {
    }
}