<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeduccion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeducciones;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaOtroPago;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepciones;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaBuilder;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaDeduccionesCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaPercepcionesCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaTotalesCalculator;
use PHPUnit\Framework\TestCase;

class NominaBuilderTest extends TestCase
{
    private NominaBuilder $builder;

    protected function setUp(): void
    {
        // Implementaciones reales de los 3 niveles, igual que hicimos
        // con ComprobanteBuilderTest y PagosBuilderTest.
        $this->builder = new NominaBuilder(
            new NominaPercepcionesCalculator(),
            new NominaDeduccionesCalculator(),
            new NominaTotalesCalculator(),
        );
    }

    public function testBuildCalculaLosTresNivelesDePuntaAPuntaConUnaNominaCompleta(): void
    {
        // Arrange: nómina cruda, nada pre-calculado
        $nomina = new Nomina();

        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('001', gravado: 3030.51, exento: 0.0));
        $percepciones->addPercepcion($this->percepcion('021', gravado: 0.0, exento: 50.00));
        $nomina->Percepciones = $percepciones;

        $deducciones = new NominaDeducciones();
        $deducciones->addDeduccion($this->deduccion('002', importe: 400.00)); // ISR
        $deducciones->addDeduccion($this->deduccion('001', importe: 200.00)); // Seguridad social
        $nomina->Deducciones = $deducciones;

        $nomina->addOtroPago($this->otroPago(importe: 150.00));

        // Act
        $resultado = $this->builder->build($nomina);

        // Assert: verificamos CADA nivel de la cadena
        $this->assertSame(3030.51, $resultado->Percepciones->TotalGravado); // nivel Percepciones
        $this->assertSame(50.0, $resultado->Percepciones->TotalExento);
        $this->assertSame(3030.51, $resultado->Percepciones->TotalSueldos);

        $this->assertSame(400.0, $resultado->Deducciones->TotalImpuestosRetenidos); // nivel Deducciones
        $this->assertSame(200.0, $resultado->Deducciones->TotalOtrasDeducciones);

        $this->assertSame(3080.51, $resultado->TotalPercepciones); // nivel Nomina (raíz)
        $this->assertSame(600.0, $resultado->TotalDeducciones);
        $this->assertSame(150.0, $resultado->TotalOtrosPagos);
    }

    public function testBuildConNominaDeJubilacionPropagaCorrectamenteATodosLosNiveles(): void
    {
        // Arrange: caso específico de la regla que corregimos con la guía del SAT
        $nomina = new Nomina();

        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('039', gravado: 5000.00, exento: 2000.00));
        $nomina->Percepciones = $percepciones;

        // Act
        $resultado = $this->builder->build($nomina);

        // Assert: la jubilación se propaga hasta TotalPercepciones a nivel raíz
        $this->assertNull($resultado->Percepciones->TotalSueldos); // exclusión mutua
        $this->assertSame(7000.0, $resultado->Percepciones->TotalJubilacionPensionRetiro);
        $this->assertSame(7000.0, $resultado->TotalPercepciones); // 5000 + 2000
    }

    public function testBuildSinPercepcionesNiDeduccionesSoloCalculaOtrosPagos(): void
    {
        // Arrange: nómina mínima, solo con un OtroPago
        $nomina = new Nomina();
        $nomina->addOtroPago($this->otroPago(importe: 300.00));

        // Act
        $resultado = $this->builder->build($nomina);

        // Assert
        $this->assertNull($resultado->TotalPercepciones);
        $this->assertNull($resultado->TotalDeducciones);
        $this->assertSame(300.0, $resultado->TotalOtrosPagos);
    }

    // --- Helpers ---

    private function percepcion(string $tipoPercepcion, float $gravado, float $exento): NominaPercepcion
    {
        $percepcion = new NominaPercepcion();
        $percepcion->TipoPercepcion = $tipoPercepcion;
        $percepcion->Clave = 'P' . $tipoPercepcion;
        $percepcion->Concepto = 'Percepción de prueba';
        $percepcion->ImporteGravado = $gravado;
        $percepcion->ImporteExento = $exento;

        return $percepcion;
    }

    private function deduccion(string $tipoDeduccion, float $importe): NominaDeduccion
    {
        $deduccion = new NominaDeduccion();
        $deduccion->TipoDeduccion = $tipoDeduccion;
        $deduccion->Clave = 'D' . $tipoDeduccion;
        $deduccion->Concepto = 'Deducción de prueba';
        $deduccion->Importe = $importe;

        return $deduccion;
    }

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