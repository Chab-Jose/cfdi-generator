<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\HidroYPetro;

use ChabJose\CfdiGenerator\Catalogs\ClaveHYP;
use ChabJose\CfdiGenerator\Catalogs\SubProductoHYP;
use ChabJose\CfdiGenerator\Catalogs\TipoPermiso;
use ChabJose\CfdiGenerator\Contracts\ConceptoCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\ConceptoImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\ComprobanteImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\TotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Exceptions\HidroYPetroValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\HidroYPetro\HidroYPetro;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Services\Complementos\ComplementoConceptoBuilderRegistry;
use ChabJose\CfdiGenerator\Services\Complementos\HidroYPetro\HidroYPetroComplementoBuilderAdapter;
use ChabJose\CfdiGenerator\Services\ComprobanteBuilder;
use PHPUnit\Framework\TestCase;

class HidroYPetroComplementoBuilderAdapterTest extends TestCase
{
    public function testSoportaSoloHidroYPetro(): void
    {
        $adapter = new HidroYPetroComplementoBuilderAdapter();

        $this->assertTrue($adapter->soporta($this->complemento()));
        $this->assertFalse($adapter->soporta(new \stdClass()));
    }

    public function testBuildDevuelveElMismoComplementoSiEsValido(): void
    {
        $adapter = new HidroYPetroComplementoBuilderAdapter();
        $complemento = $this->complemento();

        $resultado = $adapter->build($complemento, $this->concepto(), $this->comprobante('I'));

        $this->assertSame($complemento, $resultado);
    }

    public function testRegistryEncuentraElAdapterParaHidroYPetro(): void
    {
        $registry = (new ComplementoConceptoBuilderRegistry())
            ->registrar(new HidroYPetroComplementoBuilderAdapter());

        $this->assertInstanceOf(
            HidroYPetroComplementoBuilderAdapter::class,
            $registry->encontrarPara($this->complemento()),
        );
        $this->assertNull($registry->encontrarPara(new \stdClass()));
    }

    public function testComprobanteBuilderValidaElComplementoDeConcepto(): void
    {
        $comprobante = $this->comprobante('P'); // HidroYPetro no aplica en tipo P
        $concepto = $this->concepto()->addComplementoConcepto($this->complemento());
        $comprobante->Conceptos = [$concepto];

        $this->expectException(HidroYPetroValidationException::class);

        $this->builder()->build($comprobante);
    }

    public function testComprobanteBuilderAceptaComplementoValido(): void
    {
        $comprobante = $this->comprobante('I');
        $comprobante->Conceptos = [$this->concepto()->addComplementoConcepto($this->complemento())];

        $resultado = $this->builder()->build($comprobante);

        $this->assertSame($comprobante, $resultado);
    }

    public function testComprobanteBuilderIgnoraComplementosSinBuilderRegistrado(): void
    {
        $comprobante = $this->comprobante('I');
        $comprobante->Conceptos = [$this->concepto()->addComplementoConcepto(new \stdClass())];

        $resultado = $this->builder()->build($comprobante);

        $this->assertSame($comprobante, $resultado); // null-safe: sin builder, se omite
    }

    // --- Helpers ---

    private function builder(): ComprobanteBuilder
    {
        $registry = (new ComplementoConceptoBuilderRegistry())
            ->registrar(new HidroYPetroComplementoBuilderAdapter());

        return new ComprobanteBuilder(
            $this->createMock(ConceptoImpuestosCalculatorInterface::class),
            $this->createMock(ConceptoCalculatorInterface::class),
            $this->createMock(ComprobanteImpuestosCalculatorInterface::class),
            $this->createMock(TotalesCalculatorInterface::class),
            null,
            $registry,
        );
    }

    private function complemento(): HidroYPetro
    {
        $complemento = new HidroYPetro();
        $complemento->TipoPermiso = TipoPermiso::Per01;
        $complemento->NumeroPermiso = 'PL/12345/EXP/ES/2026';
        $complemento->ClaveHYP = ClaveHYP::CombustibleDiesel;
        $complemento->SubProductoHYP = SubProductoHYP::Sp18;

        return $complemento;
    }

    private function concepto(): ComprobanteConcepto
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
