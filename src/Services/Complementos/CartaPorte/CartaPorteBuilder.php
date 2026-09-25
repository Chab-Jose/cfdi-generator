<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Contracts\CartaPorteBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\CartaPorteMercanciasCalculatorInterface;
use ChabJose\CfdiGenerator\Contracts\CartaPorteTotalesCalculatorInterface;
use ChabJose\CfdiGenerator\Exceptions\CartaPorteValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;

class CartaPorteBuilder implements CartaPorteBuilderInterface
{
    public function __construct(
        private CartaPorteMercanciasCalculatorInterface $mercanciasCalculator,
        private CartaPorteTotalesCalculatorInterface $totalesCalculator,
        private MedioTransporteValidator $medioTransporteValidator
    ) {}

    public function build(CartaPorte $cartaPorte): CartaPorte
    {
        if ($cartaPorte->Mercancias !== null) {
            $errores = $this->medioTransporteValidator->validar($cartaPorte->Mercancias);
            if (!empty($errores)) {
                throw new CartaPorteValidationException($errores);
            }

            $this->mercanciasCalculator->calcular($cartaPorte->Mercancias);
        }

        $this->totalesCalculator->calcular($cartaPorte);

        return $cartaPorte;
    }
}
