<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

enum TipoPermiso: string
{
    case ExpendioEstacionServicioPetroliferos = 'PER01';
    case Comercializacion = 'PER02';
    case DistribucionOtrosMediosDistintosADucto = 'PER03';
    case ExpendioEstacionServicioMultimodal = 'PER04';
    case ExpendioEstacionServicioPetroliferosCNE = 'PER05';
    case ComercializacionCNE = 'PER06';
    case DistribucionOtrosMediosDistintosADuctoCNE = 'PER07';
    case ExpendioEstacionServicioMultimodalCNE = 'PER08';
    case ComercializacionCNE2 = 'PER09';
    case ComercializacionGenerico = 'PER10';
    case ComercializacionUH = 'PER11';
}