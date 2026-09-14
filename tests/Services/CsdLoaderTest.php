<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Domain\CsdCredential;
use ChabJose\CfdiGenerator\Exceptions\CsdException;
use ChabJose\CfdiGenerator\Services\CsdLoader;
use PHPUnit\Framework\TestCase;

class CsdLoaderTest extends TestCase
{
    private const RUTA_CER = __DIR__ . '/../Fixtures/csd/prueba.cer';
    private const RUTA_KEY = __DIR__ . '/../Fixtures/csd/prueba.key';

    private CsdLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new CsdLoader();
    }

    public function testCargaUnCsdValidoYRegresaCsdCredential(): void
    {
        $credential = $this->loader->cargar(self::RUTA_CER, self::RUTA_KEY, $this->password());

        $this->assertInstanceOf(CsdCredential::class, $credential);
        $this->assertNotEmpty($credential->noCertificado);
        $this->assertNotEmpty($credential->certificadoBase64);
    }

    public function testNoCertificadoEsUnaCadenaNumericaDe20Digitos(): void
    {
        $credential = $this->loader->cargar(self::RUTA_CER, self::RUTA_KEY, $this->password());

        $this->assertMatchesRegularExpression('/^\d{20}$/', $credential->noCertificado);
    }

    public function testLlavePrivadaQuedaEncriptadaYSeAbreConPassword(): void
    {
        $credential = $this->loader->cargar(self::RUTA_CER, self::RUTA_KEY, $this->password());

        // Ya NO se abre sin password (a diferencia del diseño anterior)
        $sinPassword = openssl_pkey_get_private($credential->llavePrivadaPemEncriptada);
        $this->assertFalse($sinPassword, 'La llave debe seguir encriptada, no desbloqueada.');

        // Sí se abre correctamente con el password correcto
        $conPassword = openssl_pkey_get_private($credential->llavePrivadaPemEncriptada, $credential->llavePrivadaPassword);
        $this->assertNotFalse($conPassword);
    }

    public function testLanzaExcepcionConPasswordIncorrecto(): void
    {
        $this->expectException(CsdException::class);
        $this->expectExceptionMessageMatches('/desbloquear la llave privada/');

        $this->loader->cargar(self::RUTA_CER, self::RUTA_KEY, 'password_incorrecto_a_proposito');
    }

    public function testLanzaExcepcionSiElArchivoCerNoExiste(): void
    {
        $this->expectException(CsdException::class);
        $this->expectExceptionMessageMatches('/No se encontró el archivo/');

        $this->loader->cargar('/ruta/inexistente.cer', self::RUTA_KEY, $this->password());
    }

    private function password(): string
    {
        return getenv('CSD_PASSWORD') ?: '12345678a';
    }
}
