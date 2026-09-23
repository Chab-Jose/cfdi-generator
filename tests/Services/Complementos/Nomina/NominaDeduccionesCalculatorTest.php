<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeduccion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeducciones;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaDeduccionesCalculator;
use PHPUnit\Framework\TestCase;

class NominaDeduccionesCalculatorTest extends TestCase
{
    private NominaDeduccionesCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new NominaDeduccionesCalculator();
    }

    public function testCalculaTotalImpuestosRetenidosSoloConClave002Isr(): void
    {
        // Arrange
        $deducciones = new NominaDeducciones();
        $deducciones->addDeduccion($this->deduccion('002', importe: 850.00)); // ISR
        $deducciones->addDeduccion($this->deduccion('001', importe: 300.00)); // Seguridad social, no cuenta aquí

        // Act
        $this->calculator->calcular($deducciones);

        // Assert
        $this->assertSame(850.0, $deducciones->TotalImpuestosRetenidos);
    }

    public function testCalculaTotalOtrasDeduccionesConTodoLoQueNoEsIsr(): void
    {
        // Arrange
        $deducciones = new NominaDeducciones();
        $deducciones->addDeduccion($this->deduccion('002', importe: 850.00)); // ISR
        $deducciones->addDeduccion($this->deduccion('001', importe: 300.00)); // Seguridad social
        $deducciones->addDeduccion($this->deduccion('003', importe: 150.00)); // Infonavit

        // Act
        $this->calculator->calcular($deducciones);

        // Assert: 300 (Seguridad social) + 150 (Infonavit), sin el ISR
        $this->assertSame(450.0, $deducciones->TotalOtrasDeducciones);
    }

    public function testTotalImpuestosRetenidosEsNullSinDeduccionesDeIsr(): void
    {
        // Arrange
        $deducciones = new NominaDeducciones();
        $deducciones->addDeduccion($this->deduccion('001', importe: 300.00));

        // Act
        $this->calculator->calcular($deducciones);

        // Assert
        $this->assertNull($deducciones->TotalImpuestosRetenidos);
    }

    public function testTotalOtrasDeduccionesEsNullSiSoloHayIsr(): void
    {
        // Arrange
        $deducciones = new NominaDeducciones();
        $deducciones->addDeduccion($this->deduccion('002', importe: 850.00));

        // Act
        $this->calculator->calcular($deducciones);

        // Assert
        $this->assertNull($deducciones->TotalOtrasDeducciones);
    }

    public function testAmbosTotalesConMultiplesDeduccionesDeCadaTipo(): void
    {
        // Arrange: caso realista con varias deducciones mezcladas
        $deducciones = new NominaDeducciones();
        $deducciones->addDeduccion($this->deduccion('001', importe: 300.00)); // Seguridad social
        $deducciones->addDeduccion($this->deduccion('002', importe: 850.00)); // ISR
        $deducciones->addDeduccion($this->deduccion('003', importe: 150.00)); // Infonavit
        $deducciones->addDeduccion($this->deduccion('004', importe: 200.00)); // Pensión alimenticia

        // Act
        $this->calculator->calcular($deducciones);

        // Assert
        $this->assertSame(850.0, $deducciones->TotalImpuestosRetenidos);
        $this->assertSame(650.0, $deducciones->TotalOtrasDeducciones); // 300 + 150 + 200
    }

    // --- Helper ---

    private function deduccion(string $tipoDeduccion, float $importe): NominaDeduccion
    {
        $deduccion = new NominaDeduccion();
        $deduccion->TipoDeduccion = $tipoDeduccion;
        $deduccion->Clave = 'D' . $tipoDeduccion;
        $deduccion->Concepto = 'Deducción de prueba';
        $deduccion->Importe = $importe;

        return $deduccion;
    }
}