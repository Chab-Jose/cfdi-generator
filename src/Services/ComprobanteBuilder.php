<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services;

use ChabJose\CfdiGenerator\Contracts\ComprobanteBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\ComprobanteImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\ConceptoCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\ConceptoImpuestosCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\TotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\ValidadorInterface;
use ChabJose\CfdiGenerator\Exceptions\ComprobanteValidationException;
use ChabJose\CfdiGenerator\Models\Comprobante;

class ComprobanteBuilder implements ComprobanteBuilderInterface
{
    public function __construct(
        private ConceptoImpuestosCalculatorInterface $conceptoImpuestosCalculator,
        private ConceptoCalculatorInterface $conceptoCalculator,
        private ComprobanteImpuestosCalculatorInterface $comprobanteImpuestosCalculator,
        private TotalesCalculatorInterface $totalesCalculator,
        private ?ValidadorInterface $validador = null,
    ) {
    }

    public function build(Comprobante $comprobante): Comprobante
    {
        foreach ($comprobante->Conceptos as $concepto) {
            $this->conceptoImpuestosCalculator->calcular($concepto);
            $this->conceptoCalculator->calcularImporte($concepto);
        }

        $this->comprobanteImpuestosCalculator->calcular($comprobante);
        $this->totalesCalculator->calcular($comprobante);

        if ($this->validador !== null && !$this->validador->validar($comprobante)) {
            throw new ComprobanteValidationException($this->validador->obtenerErrores());
        }

        return $comprobante;
    }
}