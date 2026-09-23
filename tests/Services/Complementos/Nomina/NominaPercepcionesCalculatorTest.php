<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Tests\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepciones;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaSeparacionIndemnizacion;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaPercepcionesCalculator;
use PHPUnit\Framework\TestCase;

class NominaPercepcionesCalculatorTest extends TestCase
{
    private NominaPercepcionesCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new NominaPercepcionesCalculator();
    }

    public function testCalculaTotalGravadoYExentoSumandoTodasLasPercepciones(): void
    {
        // Arrange
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('001', gravado: 3030.51, exento: 0.0));
        $percepciones->addPercepcion($this->percepcion('028', gravado: 150.00, exento: 0.0));
        $percepciones->addPercepcion($this->percepcion('021', gravado: 0.0, exento: 50.00));

        // Act
        $this->calculator->calcular($percepciones);

        // Assert: replica el ejemplo de la guía oficial del SAT
        $this->assertSame(3180.51, $percepciones->TotalGravado);
        $this->assertSame(50.0, $percepciones->TotalExento);
    }

    public function testCalculaTotalSueldosSoloConClave001(): void
    {
        // Arrange
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('001', gravado: 3000.00, exento: 0.0)); // Sueldos
        $percepciones->addPercepcion($this->percepcion('028', gravado: 150.00, exento: 0.0)); // Comisiones, no cuenta

        // Act
        $this->calculator->calcular($percepciones);

        // Assert
        $this->assertSame(3000.0, $percepciones->TotalSueldos);
    }

    public function testTotalSueldosEsNullSiNoHayPercepcionClave001(): void
    {
        // Arrange
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('028', gravado: 150.00, exento: 0.0));

        // Act
        $this->calculator->calcular($percepciones);

        // Assert
        $this->assertNull($percepciones->TotalSueldos);
    }

    public function testCalculaTotalJubilacionPensionRetiroConClave039UnaExhibicion(): void
    {
        // Arrange
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('039', gravado: 5000.00, exento: 2000.00));

        // Act
        $this->calculator->calcular($percepciones);

        // Assert: TotalJubilacionPensionRetiro = ImporteGravado + ImporteExento
        $this->assertSame(7000.0, $percepciones->TotalJubilacionPensionRetiro);
    }

    public function testCalculaTotalJubilacionPensionRetiroConClave044Parcialidades(): void
    {
        // Arrange
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('044', gravado: 3000.00, exento: 1000.00));

        // Act
        $this->calculator->calcular($percepciones);

        // Assert
        $this->assertSame(4000.0, $percepciones->TotalJubilacionPensionRetiro);
    }

    public function testExclusionMutuaEntreSueldosYJubilacion(): void
    {
        // Arrange: un trabajador con sueldo normal Y una percepción de jubilación
        // (caso inusual pero válido, ej. jubilado que sigue prestando servicios)
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('001', gravado: 3000.00, exento: 0.0));
        $percepciones->addPercepcion($this->percepcion('039', gravado: 5000.00, exento: 2000.00));

        // Act
        $this->calculator->calcular($percepciones);

        // Assert: cada total solo cuenta lo que le corresponde, sin mezclarse
        $this->assertSame(3000.0, $percepciones->TotalSueldos);
        $this->assertSame(7000.0, $percepciones->TotalJubilacionPensionRetiro);
        // Y el total general sigue sumando TODAS las percepciones sin distinción
        $this->assertSame(8000.0, $percepciones->TotalGravado);
        $this->assertSame(2000.0, $percepciones->TotalExento);
    }

    public function testTotalJubilacionPensionRetiroEsNullSinPercepcionesDeJubilacion(): void
    {
        // Arrange
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('001', gravado: 3000.00, exento: 0.0));

        // Act
        $this->calculator->calcular($percepciones);

        // Assert
        $this->assertNull($percepciones->TotalJubilacionPensionRetiro);
    }

    public function testCalculaTotalSeparacionIndemnizacionDesdeElNodoDedicado(): void
    {
        // Arrange
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('023', gravado: 10000.00, exento: 5000.00));

        $separacion = new NominaSeparacionIndemnizacion();
        $separacion->TotalPagado = 15000.00;
        $separacion->NumAñosServicio = 5;
        $separacion->UltimoSueldoMensOrd = 20000.00;
        $separacion->IngresoAcumulable = 10000.00;
        $separacion->IngresoNoAcumulable = 5000.00;
        $percepciones->SeparacionIndemnizacion = $separacion;

        // Act
        $this->calculator->calcular($percepciones);

        // Assert
        $this->assertSame(15000.0, $percepciones->TotalSeparacionIndemnizacion);
    }

    public function testTotalSeparacionIndemnizacionEsNullSinElNodo(): void
    {
        // Arrange
        $percepciones = new NominaPercepciones();
        $percepciones->addPercepcion($this->percepcion('001', gravado: 3000.00, exento: 0.0));
        $percepciones->SeparacionIndemnizacion = null;

        // Act
        $this->calculator->calcular($percepciones);

        // Assert
        $this->assertNull($percepciones->TotalSeparacionIndemnizacion);
    }

    // --- Helper ---

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
}