<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Iedu;

use ChabJose\CfdiGenerator\Catalogs\NivelEducativo;
use ChabJose\CfdiGenerator\Exceptions\IeduValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\Iedu\InstEducativas;
use ChabJose\CfdiGenerator\Services\Complementos\Iedu\InstEducativasValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InstEducativasValidatorTest extends TestCase
{
    private InstEducativasValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new InstEducativasValidator();
    }

    public function testAceptaComplementoValidoConEnum(): void
    {
        $this->validator->validar($this->complemento());
        $this->addToAssertionCount(1);
    }

    public function testAceptaNivelEducativoComoString(): void
    {
        $this->validator->validar($this->complemento(nivelEducativo: 'Bachillerato o su equivalente'));
        $this->addToAssertionCount(1);
    }

    public function testAceptaRfcPagoDePersonaFisicaYMoral(): void
    {
        $this->validator->validar($this->complemento(rfcPago: 'XAXX010101000'));
        $this->validator->validar($this->complemento(rfcPago: 'ABC010101AB1'));
        $this->addToAssertionCount(2);
    }

    public static function curpsInvalidas(): array
    {
        return [
            '17 caracteres' => ['GOMC800101HDFRRL0'],
            '19 caracteres' => ['GOMC800101HDFRRL099'],
            'minusculas' => ['gomc800101hdfrrl09'],
            'vacia' => [''],
            'sexo invalido' => ['GOMC800101ZDFRRL09'],
            'con coma' => ['GOMC800101H,FRRL09'],
        ];
    }

    #[DataProvider('curpsInvalidas')]
    public function testRechazaCurpInvalida(string $curp): void
    {
        $this->expectException(IeduValidationException::class);
        $this->expectExceptionMessageMatches('/CURP/');

        $this->validator->validar($this->complemento(curp: $curp));
    }

    public function testRechazaNivelEducativoFueraDeLaEnumeracion(): void
    {
        $this->expectException(IeduValidationException::class);
        $this->expectExceptionMessageMatches('/nivelEducativo/');

        $this->validator->validar($this->complemento(nivelEducativo: 'Licenciatura'));
    }

    public function testRechazaAutRvoeVacio(): void
    {
        $this->expectException(IeduValidationException::class);
        $this->expectExceptionMessageMatches('/autRVOE/');

        $this->validator->validar($this->complemento(autRVOE: '  '));
    }

    public function testRechazaNombreAlumnoVacio(): void
    {
        $this->expectException(IeduValidationException::class);
        $this->expectExceptionMessageMatches('/nombreAlumno/');

        $this->validator->validar($this->complemento(nombre: ''));
    }

    public function testRechazaRfcPagoInvalido(): void
    {
        $this->expectException(IeduValidationException::class);
        $this->expectExceptionMessageMatches('/rfcPago/');

        $this->validator->validar($this->complemento(rfcPago: 'ABC123'));
    }

    public function testRechazaVersionDistintaDe10(): void
    {
        $complemento = $this->complemento();
        $complemento->version = '2.0';

        $this->expectException(IeduValidationException::class);
        $this->expectExceptionMessageMatches('/version/');

        $this->validator->validar($complemento);
    }

    private function complemento(
        string $nombre = 'JUAN PEREZ LOPEZ',
        string $curp = 'GOMC800101HDFRRL09',
        string|NivelEducativo $nivelEducativo = NivelEducativo::Primaria,
        string $autRVOE = '1234567890',
        ?string $rfcPago = null,
    ): InstEducativas {
        return new InstEducativas($nombre, $curp, $nivelEducativo, $autRVOE, $rfcPago);
    }
}