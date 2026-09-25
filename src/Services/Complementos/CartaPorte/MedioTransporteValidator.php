<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Contracts\MedioTransporteInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\AereoMedioTransporte;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\AutotransporteMedioTransporte;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\FerroviarioMedioTransporte;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\MaritimoMedioTransporte;

class MedioTransporteValidator
{
    /** @param MedioTransporteInterface[] $medios */
    public function __construct(
        private array $medios,
    ) {
    }

    /** @return string[] */
    public function validar(CartaPorteMercancias $mercancias): array
    {
        $presentes = array_filter($this->medios, fn(MedioTransporteInterface $m) => $m->estaPresente($mercancias));

        if (count($presentes) === 0) {
            return ['Debe especificarse exactamente un medio de transporte (Autotransporte, Marítimo, Aéreo o Ferroviario); no se encontró ninguno.'];
        }

        if (count($presentes) > 1) {
            $nombres = implode(', ', array_map(fn(MedioTransporteInterface $m) => $m->nombre(), $presentes));
            return ["Solo puede especificarse UN medio de transporte; se encontraron varios: {$nombres}."];
        }

        return [];
    }

    public static function conMediosEstandar(): self
    {
        return new self([
            new AutotransporteMedioTransporte(),
            new MaritimoMedioTransporte(),
            new AereoMedioTransporte(),
            new FerroviarioMedioTransporte(),
        ]);
    }
}