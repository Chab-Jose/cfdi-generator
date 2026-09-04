<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\CsdLoaderInterface;
use ChabJose\CfdiGenerator\Domain\CsdCredential;
use ChabJose\CfdiGenerator\Exceptions\CsdException;

final class CsdLoader implements CsdLoaderInterface
{
    public function cargar(string $rutaCer, string $rutaKey, string $password): CsdCredential
    {
        $certificadoDer = $this->leerArchivo($rutaCer);
        $llaveDer = $this->leerArchivo($rutaKey);

        $certificadoPem = $this->derACertificadoPem($certificadoDer);
        $datosCertificado = $this->parsearCertificado($certificadoPem);

        $llavePrivadaPem = $this->derALlavePrivadaPem($llaveDer, $password);

        return new CsdCredential(
            noCertificado: $datosCertificado['noCertificado'],
            certificadoBase64: base64_encode($certificadoDer),
            llavePrivadaPem: $llavePrivadaPem,
            rfc: $datosCertificado['rfc'],
        );
    }

    private function leerArchivo(string $ruta): string
    {
        if (!is_file($ruta)) {
            throw new CsdException("No se encontró el archivo: {$ruta}");
        }

        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            throw new CsdException("No se pudo leer el archivo: {$ruta}");
        }

        return $contenido;
    }

    private function derACertificadoPem(string $certificadoDer): string
    {
        $pem = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($certificadoDer), 64, "\n")
            . "-----END CERTIFICATE-----\n";

        // Validamos que openssl realmente pueda parsear el resultado
        $recurso = openssl_x509_read($pem);
        if ($recurso === false) {
            throw new CsdException('El archivo .cer no es un certificado X.509 válido.');
        }

        return $pem;
    }

    private function derALlavePrivadaPem(string $llaveDer, string $password): string
    {
        $pem = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n"
            . chunk_split(base64_encode($llaveDer), 64, "\n")
            . "-----END ENCRYPTED PRIVATE KEY-----\n";

        $llave = openssl_pkey_get_private($pem, $password);

        if ($llave === false) {
            throw new CsdException(
                'No se pudo desbloquear la llave privada (.key). Verifica el password o que el archivo corresponda al CSD.'
            );
        }

        // Reexportamos a PEM sin cifrar: Sellador ya no necesita el password
        $llavePemDesbloqueada = '';
        openssl_pkey_export($llave, $llavePemDesbloqueada);

        return $llavePemDesbloqueada;
    }

    /** @return array{noCertificado: string, rfc: ?string} */
    private function parsearCertificado(string $certificadoPem): array
    {
        $datos = openssl_x509_parse($certificadoPem);

        if ($datos === false) {
            throw new CsdException('No se pudo parsear el certificado para extraer sus datos.');
        }

        // El número de certificado SAT viene en el serialNumberHex (20 bytes en hexadecimal)
        $serialHex = $datos['serialNumberHex'] ?? null;

        if ($serialHex === null) {
            throw new CsdException('El certificado no contiene número de serie (serialNumberHex).');
        }

        // Conversión: cada par de hex representa un dígito ASCII del NoCertificado real
        $noCertificado = '';
        foreach (str_split($serialHex, 2) as $par) {
            $noCertificado .= chr((int) hexdec($par));
        }

        // El RFC del emisor suele venir embebido en el subject (OID 2.5.4.45 o similar, varía por AC)
        $rfc = $datos['subject']['x500UniqueIdentifier']
            ?? $datos['subject']['serialNumber']
            ?? null;

        return [
            'noCertificado' => $noCertificado,
            'rfc' => $rfc,
        ];
    }
}