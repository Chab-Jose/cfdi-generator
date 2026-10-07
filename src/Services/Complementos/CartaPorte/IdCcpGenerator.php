<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Contracts\IdCcpGeneratorInterface;

class IdCcpGenerator implements IdCcpGeneratorInterface
{
    public function generar(): string
    {
        return 'CCP' . strtoupper(sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6)),
        ));
    }
}