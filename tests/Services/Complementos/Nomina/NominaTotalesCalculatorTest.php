<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeducciones;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaOtroPago;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepciones;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaTotalesCalculator;
use PHPUnit\Framework\TestCase;

class NominaTotalesCalculatorTest extends TestCase
{
    private NominaTotalesCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new NominaTotalesCalculator();
    }

    public function testCalculaTotalPercepcionesComoSumaDeGravadoYExento(): void
    {
        // Arrange
        $nomina = new Nomina();
        $nomina->Percepciones = new NominaPercepciones();
        $nomina->Percepciones->TotalGravado = 3180.51;
        $nomina->Percepciones->TotalExento = 150.00;

        // Act
        $this->calculator->calcular($nomina);

        // Assert
        $this->assertSame(3330.51, $nomina->TotalPercepciones);
    }

    public function testTotalPercepcionesEsNullSinNodoPercepciones(): void
    {
        // Arrange
        $nomina = new Nomina();
        $nomina->Percepciones = null;

        // Act
        $this->calculator->calcular($nomina);

        // Assert
        $this->assertNull($nomina->TotalPercepciones);
    }

    public function testCalculaTotalDeduccionesSumandoOtrasEImpuestos(): void
    {
        // Arrange
        $nomina = new Nomina();
        $nomina->Deducciones = new NominaDeducciones();
        $nomina->Deducciones->TotalOtrasDeducciones = 450.00;
        $nomina->Deducciones->TotalImpuestosRetenidos = 850.00;

        // Act
        $this->calculator->calcular($nomina);

        // Assert
        $this->assertSame(1300.0, $nomina->TotalDeducciones);
    }

    public function testCalculaTotalDeduccionesConSoloUnTipoPresente(): void
    {
        // Arrange: solo ISR, sin otras deducciones
        $nomina = new Nomina();
        $nomina->Deducciones = new NominaDeducciones();
        $nomina->Deducciones->TotalOtrasDeducciones = null;
        $nomina->Deducciones->TotalImpuestosRetenidos = 850.00;

        // Act
        $this->calculator->calcular($nomina);

        // Assert
        $this->assertSame(850.0, $nomina->TotalDeducciones);
    }

    public function testTotalDeduccionesEsNullSinNodoDeducciones(): void
    {
        // Arrange
        $nomina = new Nomina();
        $nomina->Deducciones = null;

        // Act
        $this->calculator->calcular($nomina);

        // Assert
        $this->assertNull($nomina->TotalDeducciones);
    }

    public function testCalculaTotalOtrosPagosSumandoTodosLosOtroPago(): void
    {
        // Arrange
        $nomina = new Nomina();
        $nomina->addOtroPago($this->otroPago(importe: 500.00));
        $nomina->addOtroPago($this->otroPago(importe: 300.00));

        // Act
        $this->calculator->calcular($nomina);

        // Assert
        $this->assertSame(800.0, $nomina->TotalOtrosPagos);
    }

    public function testTotalOtrosPagosEsNullSinOtrosPagos(): void
    {
        // Arrange
        $nomina = new Nomina();

        // Act
        $this->calculator->calcular($nomina);

        // Assert
        $this->assertNull($nomina->TotalOtrosPagos);
    }

    public function testCalculaLosTresTotalesJuntosEnUnCasoCompleto(): void
    {
        // Arrange: nómina realista con Percepciones, Deducciones y OtrosPagos
        $nomina = new Nomina();

        $nomina->Percepciones = new NominaPercepciones();
        $nomina->Percepciones->TotalGravado = 3180.51;
        $nomina->Percepciones->TotalExento = 150.00;

        $nomina->Deducciones = new NominaDeducciones();
        $nomina->Deducciones->TotalOtrasDeducciones = 300.00;
        $nomina->Deducciones->TotalImpuestosRetenidos = 400.00;

        $nomina->addOtroPago($this->otroPago(importe: 250.00));

        // Act
        $this->calculator->calcular($nomina);

        // Assert
        $this->assertSame(3330.51, $nomina->TotalPercepciones);
        $this->assertSame(700.0, $nomina->TotalDeducciones);
        $this->assertSame(250.0, $nomina->TotalOtrosPagos);
    }

    // --- Helper ---

    private function otroPago(float $importe): NominaOtroPago
    {
        $otroPago = new NominaOtroPago();
        $otroPago->TipoOtroPago = '002';
        $otroPago->Clave = 'OP002';
        $otroPago->Concepto = 'Subsidio al empleo';
        $otroPago->Importe = $importe;

        return $otroPago;
    }
}