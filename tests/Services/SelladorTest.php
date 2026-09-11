<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services;

use ChabJose\CfdiGenerator\Contracts\CadenaOriginalServiceInterface;
use ChabJose\CfdiGenerator\Domain\CsdCredential;
use ChabJose\CfdiGenerator\Services\CsdLoader;
use ChabJose\CfdiGenerator\Services\Sellador;
use PHPUnit\Framework\TestCase;

class SelladorTest extends TestCase
{
    private const RUTA_CER = __DIR__ . '/../Fixtures/csd/prueba.cer';
    private const RUTA_KEY = __DIR__ . '/../Fixtures/csd/prueba.key';
    private const PASSWORD = '12345678a';

    public function testGenerarCadenaOriginalDelegaAlServicioInyectado(): void
    {
        // Arrange: creamos un "doble" (mock) de la dependencia.
        // No usamos el XSLT real - solo confirmamos que Sellador LLAMA al servicio correctamente.
        $cadenaOriginalMock = $this->createMock(CadenaOriginalServiceInterface::class);
        $cadenaOriginalMock
            ->expects($this->once())
            ->method('generar')
            ->with('<xml de prueba/>')
            ->willReturn('||cadena original simulada||');

        $sellador = new Sellador($cadenaOriginalMock);

        // Act
        $resultado = $sellador->generarCadenaOriginal('<xml de prueba/>');

        // Assert
        $this->assertSame('||cadena original simulada||', $resultado);
    }

    public function testSellarProduceUnSelloValidoConCsdReal(): void
    {
        // Arrange: aquí SÍ usamos un CSD real, porque sellar() es pura criptografía,
        // no involucra el XSLT en absoluto (el mock de arriba ya cubrió esa parte).
        $csd = (new CsdLoader())->cargar(self::RUTA_CER, self::RUTA_KEY, self::PASSWORD);
        $cadenaOriginalMock = $this->createMock(CadenaOriginalServiceInterface::class);
        $sellador = new Sellador($cadenaOriginalMock);

        // Act
        $sello = $sellador->sellar('||cadena de prueba conocida||', $csd);

        // Assert: el sello debe ser un base64 válido y no vacío
        $this->assertNotEmpty($sello);
        $this->assertNotFalse(base64_decode($sello, true), 'El sello debe ser base64 válido.');
    }

    public function testSelloEsVerificableConLaLlavePublicaDelCertificado(): void
    {
        // Arrange: round-trip real de firma + verificación con openssl directo
        // (no depende de un método validarSello() que Sellador no expone)
        $csd = (new CsdLoader())->cargar(self::RUTA_CER, self::RUTA_KEY, self::PASSWORD);
        $cadenaOriginalMock = $this->createMock(CadenaOriginalServiceInterface::class);
        $sellador = new Sellador($cadenaOriginalMock);

        $cadenaOriginal = '||cadena de prueba conocida||';
        $sello = $sellador->sellar($cadenaOriginal, $csd);

        // Act: verificamos usando openssl directamente, como lo haría un tercero
        $certificadoPem = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split($csd->certificadoBase64, 64, "\n")
            . "-----END CERTIFICATE-----\n";
        $llavePublica = openssl_pkey_get_public($certificadoPem);
        $resultado = openssl_verify($cadenaOriginal, base64_decode($sello), $llavePublica, OPENSSL_ALGO_SHA256);

        // Assert
        $this->assertSame(1, $resultado, 'El sello debe ser válido contra la llave pública del certificado.');
    }

    public function testDosCadenasDiferentesProducenSellosDiferentes(): void
    {
        // Arrange
        $csd = (new CsdLoader())->cargar(self::RUTA_CER, self::RUTA_KEY, self::PASSWORD);
        $cadenaOriginalMock = $this->createMock(CadenaOriginalServiceInterface::class);
        $sellador = new Sellador($cadenaOriginalMock);

        // Act
        $selloUno = $sellador->sellar('||cadena uno||', $csd);
        $selloDos = $sellador->sellar('||cadena dos||', $csd);

        // Assert
        $this->assertNotSame($selloUno, $selloDos);
    }
}