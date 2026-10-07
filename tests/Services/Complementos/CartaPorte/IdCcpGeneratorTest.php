<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\IdCcpGenerator;
use PHPUnit\Framework\TestCase;

class IdCcpGeneratorTest extends TestCase
{
    public function testGeneraUnIdCcpConElFormatoCorrecto(): void
    {
        $generador = new IdCcpGenerator();

        $id = $generador->generar();

        $this->assertMatchesRegularExpression(
            '/^CCP[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/',
            $id
        );
    }

    public function testGeneraIdsDiferentesEnCadaLlamada(): void
    {
        $generador = new IdCcpGenerator();

        $this->assertNotSame($generador->generar(), $generador->generar());
    }

    public function testElIdSiempreEmpiezaConElPrefijoCcp(): void
    {
        $generador = new IdCcpGenerator();

        $this->assertStringStartsWith('CCP', $generador->generar());
    }
}