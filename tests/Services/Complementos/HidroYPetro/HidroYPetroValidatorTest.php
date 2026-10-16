<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\HidroYPetro;

use ChabJose\CfdiGenerator\Catalogs\ClaveHYP;
use ChabJose\CfdiGenerator\Catalogs\SubProductoHYP;
use ChabJose\CfdiGenerator\Catalogs\TipoPermiso;
use ChabJose\CfdiGenerator\Exceptions\HidroYPetroValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\HidroYPetro\HidroYPetro;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoACuentaTerceros;
use ChabJose\CfdiGenerator\Services\Complementos\HidroYPetro\HidroYPetroValidator;
use PHPUnit\Framework\TestCase;

class HidroYPetroValidatorTest extends TestCase
{
    private HidroYPetroValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new HidroYPetroValidator();
    }

    public function testAceptaComplementoValidoConEnums(): void
    {
        $this->validator->validar($this->complementoValido(), $this->conceptoDiesel(), $this->comprobante('I'));

        $this->addToAssertionCount(1); // no lanzó excepción
    }

    public function testAceptaComplementoValidoConStrings(): void
    {
        $complemento = new HidroYPetro();
        $complemento->TipoPermiso = 'PER01';
        $complemento->NumeroPermiso = 'PL/12345/EXP/ES/2026';
        $complemento->ClaveHYP = '15101505';
        $complemento->SubProductoHYP = 'SP18';

        $this->validator->validar($complemento, $this->conceptoDiesel(), $this->comprobante('I'));

        $this->addToAssertionCount(1);
    }

    public function testAceptaComprobanteDeEgreso(): void
    {
        $this->validator->validar($this->complementoValido(), $this->conceptoDiesel(), $this->comprobante('E'));

        $this->addToAssertionCount(1);
    }

    public function testRechazaTipoDeComprobanteDistintoDeIngresoOEgreso(): void
    {
        $this->expectException(HidroYPetroValidationException::class);
        $this->expectExceptionMessageMatches('/Ingreso|Egreso/');

        $this->validator->validar($this->complementoValido(), $this->conceptoDiesel(), $this->comprobante('P'));
    }

    public function testRechazaConceptoConACuentaTerceros(): void
    {
        $concepto = $this->conceptoDiesel();
        $concepto->ACuentaTerceros = new ComprobanteConceptoACuentaTerceros();

        $this->expectException(HidroYPetroValidationException::class);
        $this->expectExceptionMessageMatches('/ACuentaTerceros/');

        $this->validator->validar($this->complementoValido(), $concepto, $this->comprobante('I'));
    }

    public function testRechazaClaveHYPDistintaDeClaveProdServ(): void
    {
        $complemento = new HidroYPetro();
        $complemento->TipoPermiso = TipoPermiso::Per01;
        $complemento->NumeroPermiso = 'PL/12345/EXP/ES/2026';
        $complemento->ClaveHYP = ClaveHYP::GasolinaRegular;
        $complemento->SubProductoHYP = SubProductoHYP::Sp16;

        $this->expectException(HidroYPetroValidationException::class);
        $this->expectExceptionMessageMatches('/ClaveHYP/');

        // El concepto sigue siendo diésel (15101505)
        $this->validator->validar($complemento, $this->conceptoDiesel(), $this->comprobante('I'));
    }

    public function testRechazaClaveUnidadDistintaDeLitro(): void
    {
        $concepto = $this->conceptoDiesel();
        $concepto->ClaveUnidad = 'H87';

        $this->expectException(HidroYPetroValidationException::class);
        $this->expectExceptionMessageMatches('/ClaveUnidad/');

        $this->validator->validar($this->complementoValido(), $concepto, $this->comprobante('I'));
    }

    public function testRechazaSubProductoQueNoCorrespondeALaClave(): void
    {
        $complemento = new HidroYPetro();
        $complemento->TipoPermiso = TipoPermiso::Per01;
        $complemento->NumeroPermiso = 'PL/12345/EXP/ES/2026';
        $complemento->ClaveHYP = ClaveHYP::CombustibleDiesel;
        $complemento->SubProductoHYP = SubProductoHYP::Sp16;

        $this->expectException(HidroYPetroValidationException::class);
        $this->expectExceptionMessageMatches('/SubProductoHYP/');

        $this->validator->validar($complemento, $this->conceptoDiesel(), $this->comprobante('I'));
    }

    public function testRechazaNumeroPermisoQueNoCoincideConElTipo(): void
    {
        $complemento = new HidroYPetro();
        $complemento->TipoPermiso = TipoPermiso::Per02;
        $complemento->NumeroPermiso = 'PL/12345/EXP/ES/2026';
        $complemento->ClaveHYP = ClaveHYP::CombustibleDiesel;
        $complemento->SubProductoHYP = SubProductoHYP::Sp18;

        $this->expectException(HidroYPetroValidationException::class);
        $this->expectExceptionMessageMatches('/NumeroPermiso/');

        $this->validator->validar($complemento, $this->conceptoDiesel(), $this->comprobante('I'));
    }

    public function testRechazaTipoPermisoInexistente(): void
    {
        $complemento = new HidroYPetro();
        $complemento->TipoPermiso = 'PER99';
        $complemento->NumeroPermiso = 'PL/12345/EXP/ES/2026';
        $complemento->ClaveHYP = '15101505';
        $complemento->SubProductoHYP = 'SP18';

        $this->expectException(HidroYPetroValidationException::class);

        $this->validator->validar($complemento, $this->conceptoDiesel(), $this->comprobante('I'));
    }

    // --- Helpers ---

    private function complementoValido(): HidroYPetro
    {
        $complemento = new HidroYPetro();
        $complemento->TipoPermiso = TipoPermiso::Per01;
        $complemento->NumeroPermiso = 'PL/12345/EXP/ES/2026';
        $complemento->ClaveHYP = ClaveHYP::CombustibleDiesel;
        $complemento->SubProductoHYP = SubProductoHYP::Sp18;

        return $complemento;
    }

    private function conceptoDiesel(): ComprobanteConcepto
    {
        $concepto = new ComprobanteConcepto();
        $concepto->ClaveProdServ = '15101505';
        $concepto->ClaveUnidad = 'LTR';
        $concepto->Descripcion = 'Diésel automotriz';
        $concepto->Cantidad = 10.0;
        $concepto->ValorUnitario = 25.0;
        $concepto->Importe = 250.0;
        $concepto->ObjetoImp = '02';

        return $concepto;
    }

    private function comprobante(string $tipo): Comprobante
    {
        $comprobante = new Comprobante();
        $comprobante->TipoDeComprobante = $tipo;

        return $comprobante;
    }
}
