<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\SelladorInterface;
use ChabJose\CfdiGenerator\Contracts\CadenaOriginalServiceInterface;
use ChabJose\CfdiGenerator\Domain\CsdCredential;
use ChabJose\CfdiGenerator\Exceptions\SelladoException;

final class Sellador implements SelladorInterface
{
    public function __construct(
        private readonly CadenaOriginalServiceInterface $cadenaOriginal,
    ) {}

    public function generarCadenaOriginal(string $xmlSinSello): string
    {
        return $this->cadenaOriginal->generar($xmlSinSello);
    }

    public function sellar(string $cadenaOriginal, CsdCredential $csd): string
    {
        $llavePrivada = openssl_pkey_get_private($csd->llavePrivadaPem, $csd->llavePrivadaPassword ?? '');

        if ($llavePrivada === false) {
            throw new SelladoException(
                'No se pudo cargar la llave privada del CSD. Verifica el PEM y la contraseña.'
            );
        }

        $firmado = openssl_sign($cadenaOriginal, $sello, $llavePrivada, OPENSSL_ALGO_SHA256);

        if (!$firmado) {
            throw new SelladoException('openssl_sign falló al generar el sello digital.');
        }

        return base64_encode($sello);
    }
}