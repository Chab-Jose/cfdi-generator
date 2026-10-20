<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Iedu;

use ChabJose\CfdiGenerator\Catalogs\NivelEducativo;
use ChabJose\CfdiGenerator\Exceptions\IeduValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\Iedu\InstEducativas;
use ChabJose\CfdiGenerator\Utils\Xml\NormalizaValorCatalogoTrait;

class InstEducativasValidator
{
    use NormalizaValorCatalogoTrait;

    /** tCURP del XSD. Se omiten las comas dentro de las clases de caracteres (artefacto del XSD). */
    private const PATRON_CURP = '~^[A-Z][AEIOUX][A-Z]{2}[0-9]{2}[0-1][0-9][0-3][0-9][MHX][A-Z]{2}[BCDFGHJKLMNÑPQRSTVWXYZ]{3}[0-9A-Z][0-9]$~Du';

    /** tRFC del XSD, con longitud 12-13 exigida por minLength/maxLength. */
    private const PATRON_RFC = '~^(?=.{12,13}$)[A-ZÑ&]{3,4}[0-9]{2}[0-1][0-9][0-3][0-9][A-Z0-9]{0,3}$~Du';

    public function validar(InstEducativas $complemento): void
    {
        if ($complemento->version !== '1.0') {
            throw new IeduValidationException('IEDU: version debe ser "1.0".');
        }

        if (trim($complemento->nombreAlumno) === '') {
            throw new IeduValidationException('IEDU: nombreAlumno es obligatorio.');
        }

        if (preg_match(self::PATRON_CURP, $complemento->CURP) !== 1) {
            throw new IeduValidationException("IEDU: CURP inválida ({$complemento->CURP}).");
        }

        $nivel = $this->valorEscalar($complemento->nivelEducativo);
        if ($nivel === null || NivelEducativo::tryFrom($nivel) === null) {
            throw new IeduValidationException(
                'IEDU: nivelEducativo no es válido; valores permitidos: '
                . implode(', ', array_map(fn (NivelEducativo $n) => $n->value, NivelEducativo::cases())) . '.'
            );
        }

        if (trim($complemento->autRVOE) === '') {
            throw new IeduValidationException('IEDU: autRVOE es obligatorio.');
        }

        if ($complemento->rfcPago !== null && preg_match(self::PATRON_RFC, $complemento->rfcPago) !== 1) {
            throw new IeduValidationException("IEDU: rfcPago inválido ({$complemento->rfcPago}).");
        }
    }
}