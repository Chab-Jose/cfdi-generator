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

        $llavePrivadaPemEncriptada = $this->derALlavePrivadaPemEncriptada($llaveDer);

        // Validamos el password AHORA (falla rápido y con mensaje claro),
        // pero NO reexportamos - solo confirmamos que abre correctamente.
        $this->validarPassword($llavePrivadaPemEncriptada, $password);

        return new CsdCredential(
            noCertificado: $datosCertificado['noCertificado'],
            certificadoBase64: base64_encode($certificadoDer),
            llavePrivadaPemEncriptada: $llavePrivadaPemEncriptada,
            llavePrivadaPassword: $password,
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

        $recurso = openssl_x509_read($pem);
        if ($recurso === false) {
            throw new CsdException('El archivo .cer no es un certificado X.509 válido.');
        }

        return $pem;
    }

    private function derALlavePrivadaPemEncriptada(string $llaveDer): string
    {
        return "-----BEGIN ENCRYPTED PRIVATE KEY-----\n"
            . chunk_split(base64_encode($llaveDer), 64, "\n")
            . "-----END ENCRYPTED PRIVATE KEY-----\n";
    }

    private function validarPassword(string $llavePrivadaPemEncriptada, string $password): void
    {
        $llave = openssl_pkey_get_private($llavePrivadaPemEncriptada, $password);

        if ($llave === false) {
            throw new CsdException(
                'No se pudo desbloquear la llave privada (.key). Verifica el password o que el archivo corresponda al CSD. '
                . 'Detalle OpenSSL: ' . openssl_error_string()
            );
        }
    }

    /** @return array{noCertificado: string, rfc: ?string} */
    private function parsearCertificado(string $certificadoPem): array
    {
        $datos = openssl_x509_parse($certificadoPem);

        if ($datos === false) {
            throw new CsdException('No se pudo parsear el certificado para extraer sus datos.');
        }

        $serialHex = $datos['serialNumberHex'] ?? null;

        if ($serialHex === null) {
            throw new CsdException('El certificado no contiene número de serie (serialNumberHex).');
        }

        $noCertificado = '';
        foreach (str_split($serialHex, 2) as $par) {
            $noCertificado .= chr((int) hexdec($par));
        }

        $rfc = $datos['subject']['x500UniqueIdentifier']
            ?? $datos['subject']['serialNumber']
            ?? null;

        return [
            'noCertificado' => $noCertificado,
            'rfc' => $rfc,
        ];
    }
}