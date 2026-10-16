<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\HidroYPetro;

use ChabJose\CfdiGenerator\Services\Complementos\HidroYPetro\HidroYPetroMatriz;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HidroYPetroMatrizTest extends TestCase
{
    public static function permisosValidos(): array
    {
        return [
            'PER01' => ['PER01', 'PL/12345/EXP/ES/2026'],
            'PER02' => ['PER02', 'H/1234/COM/2026'],
            'PER03' => ['PER03', 'PL/1/DIS/OM/2026'],
            'PER04' => ['PER04', 'PL/123/EXP/ES/MM/2026'],
            'PER05' => ['PER05', 'CNE/PL/123/EXP/ES/2026'],
            'PER06' => ['PER06', 'CNE/H/123/COM/2026'],
            'PER07' => ['PER07', 'CNE/PL/123/DIS/OM/2026'],
            'PER08' => ['PER08', 'CNE/PL/123/EXP/ES/MM/2026'],
            'PER09' => ['PER09', 'CNE/PL/123/COM/2026'],
            'PER10' => ['PER10', '#ABCDEFGHIJ1234'],
            'PER11' => ['PER11', 'F00.07.UH/123/2026'],
        ];
    }

    #[DataProvider('permisosValidos')]
    public function testAceptaNumeroPermisoValidoParaCadaTipo(string $tipo, string $numero): void
    {
        $this->assertTrue(HidroYPetroMatriz::numeroPermisoValido($tipo, $numero));
    }

    public function testRechazaNumeroPermisoConFormatoIncorrecto(): void
    {
        // PER02 exige mínimo 4 dígitos de consecutivo
        $this->assertFalse(HidroYPetroMatriz::numeroPermisoValido('PER02', 'H/123/COM/2026'));
        // año de 3 dígitos
        $this->assertFalse(HidroYPetroMatriz::numeroPermisoValido('PER01', 'PL/12345/EXP/ES/202'));
    }

    public function testRechazaNumeroPermisoDeOtroTipo(): void
    {
        // Formato válido de PER01 usado bajo PER02
        $this->assertFalse(HidroYPetroMatriz::numeroPermisoValido('PER02', 'PL/12345/EXP/ES/2026'));
    }

    public function testRechazaNumeroPermisoConTextoAdicionalAntesODespues(): void
    {
        $this->assertFalse(HidroYPetroMatriz::numeroPermisoValido('PER01', 'XPL/12345/EXP/ES/2026'));
        $this->assertFalse(HidroYPetroMatriz::numeroPermisoValido('PER01', 'PL/12345/EXP/ES/2026X'));
    }

    public function testPer10RechazaLongitudDistintaDe14(): void
    {
        $this->assertFalse(HidroYPetroMatriz::numeroPermisoValido('PER10', '#ABCDEFGHIJ123'));
        $this->assertFalse(HidroYPetroMatriz::numeroPermisoValido('PER10', '#ABCDEFGHIJ12345'));
    }

    public function testTipoPermisoDesconocidoEsInvalido(): void
    {
        $this->assertNull(HidroYPetroMatriz::patronNumeroPermiso('PER99'));
        $this->assertFalse(HidroYPetroMatriz::numeroPermisoValido('PER99', 'PL/12345/EXP/ES/2026'));
    }

    public function testSubProductosValidosPorClave(): void
    {
        $this->assertSame(
            ['SP18', 'SP19', 'SP22', 'SP23', 'SP24', 'SP25', 'SP48'],
            HidroYPetroMatriz::subProductosValidosPara('15101505'),
        );
        $this->assertSame(['SP16'], HidroYPetroMatriz::subProductosValidosPara('15101514'));
        $this->assertSame(['SP17'], HidroYPetroMatriz::subProductosValidosPara('15101515'));
        $this->assertSame([], HidroYPetroMatriz::subProductosValidosPara('99999999'));
    }

    public function testSubProductoDebeCorresponderALaClave(): void
    {
        $this->assertTrue(HidroYPetroMatriz::subProductoValido('15101505', 'SP18'));
        $this->assertFalse(HidroYPetroMatriz::subProductoValido('15101505', 'SP16')); // gasolina bajo diésel
        $this->assertFalse(HidroYPetroMatriz::subProductoValido('15101514', 'SP17')); // premium bajo regular
    }

    public function testClaveUnidadRequeridaEsLitro(): void
    {
        $this->assertSame('LTR', HidroYPetroMatriz::CLAVE_UNIDAD_REQUERIDA);
    }
}