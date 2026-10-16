<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\HidroYPetro;


use ChabJose\CfdiGenerator\Exceptions\HidroYPetroValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\HidroYPetro\HidroYPetro;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Utils\Xml\NormalizaValorCatalogoTrait;

class HidroYPetroValidator
{
    use NormalizaValorCatalogoTrait;

    public function validar(HidroYPetro $complemento, ComprobanteConcepto $concepto, Comprobante $comprobante): void
    {
        $tipoDeComprobante = $this->valorEscalar($comprobante->TipoDeComprobante);
        if (!in_array($tipoDeComprobante, ['I', 'E'], true)) {
            throw new HidroYPetroValidationException(
                'HidroYPetro solo aplica en comprobantes de tipo Ingreso (I) o Egreso (E).'
            );
        }

        if ($concepto->ACuentaTerceros !== null) {
            throw new HidroYPetroValidationException(
                'Un Concepto con complemento HidroYPetro no puede tener ACuentaTerceros.'
            );
        }

        $claveHYP = $this->valorEscalar($complemento->ClaveHYP);
        if ($claveHYP !== $concepto->ClaveProdServ) {
            throw new HidroYPetroValidationException(
                "ClaveHYP ({$claveHYP}) debe ser igual al ClaveProdServ del Concepto ({$concepto->ClaveProdServ})."
            );
        }

        if ($concepto->ClaveUnidad !== HidroYPetroMatriz::CLAVE_UNIDAD_REQUERIDA) {
            throw new HidroYPetroValidationException(
                'ClaveUnidad del Concepto debe ser "' . HidroYPetroMatriz::CLAVE_UNIDAD_REQUERIDA . '" cuando lleva HidroYPetro.'
            );
        }

        $subProducto = $this->valorEscalar($complemento->SubProductoHYP);
        if (!HidroYPetroMatriz::subProductoValido($claveHYP, $subProducto)) {
            throw new HidroYPetroValidationException(
                "SubProductoHYP ({$subProducto}) no es válido para ClaveHYP ({$claveHYP})."
            );
        }

        $tipoPermiso = $this->valorEscalar($complemento->TipoPermiso);
        if (!HidroYPetroMatriz::numeroPermisoValido($tipoPermiso, $complemento->NumeroPermiso)) {
            throw new HidroYPetroValidationException(
                "NumeroPermiso ({$complemento->NumeroPermiso}) no coincide con el patrón de {$tipoPermiso}."
            );
        }
    }
}