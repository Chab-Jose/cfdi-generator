<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Models;

use ChabJose\CfdiGenerator\Models\Complementos\HidroYPetro\HidroYPetro;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteConceptoComplementoConcepto;
use PHPUnit\Framework\TestCase;

class ComprobanteConceptoTest extends TestCase
{
    public function testAddComplementoConceptoCreaElContenedorBajoDemanda(): void
    {
        $concepto = new ComprobanteConcepto();
        $this->assertNull($concepto->ComplementoConcepto);

        $concepto->addComplementoConcepto($this->hidroYPetro());

        $this->assertInstanceOf(ComprobanteConceptoComplementoConcepto::class, $concepto->ComplementoConcepto);
        $this->assertCount(1, $concepto->ComplementoConcepto->Any);
    }

    public function testUsaElNombreCortoDeLaClaseComoClave(): void
    {
        $concepto = new ComprobanteConcepto();
        $complemento = $this->hidroYPetro();

        $concepto->addComplementoConcepto($complemento);

        $this->assertSame($complemento, $concepto->ComplementoConcepto->Any['HidroYPetro']);
    }

    public function testEsFluente(): void
    {
        $concepto = new ComprobanteConcepto();

        $this->assertSame($concepto, $concepto->addComplementoConcepto($this->hidroYPetro()));
    }

    public function testReutilizaElContenedorExistente(): void
    {
        $concepto = new ComprobanteConcepto();
        $concepto->addComplementoConcepto($this->hidroYPetro());
        $contenedor = $concepto->ComplementoConcepto;

        $concepto->addComplementoConcepto(new \stdClass());

        $this->assertSame($contenedor, $concepto->ComplementoConcepto);
        $this->assertCount(2, $concepto->ComplementoConcepto->Any);
    }

    private function hidroYPetro(): HidroYPetro
    {
        $complemento = new HidroYPetro();
        $complemento->TipoPermiso = 'PER01';
        $complemento->NumeroPermiso = 'PL/12345/EXP/ES/2026';
        $complemento->ClaveHYP = '15101505';
        $complemento->SubProductoHYP = 'SP18';

        return $complemento;
    }
}
