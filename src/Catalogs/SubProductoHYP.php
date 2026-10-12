<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

enum SubProductoHYP: string
{
    case GasolinaRegular = 'SP16';
    case GasolinaPremium = 'SP17';
    case DieselAutomotriz = 'SP18';
    case DieselMarino = 'SP19';
    case IFO380 = 'SP22';
    case DieselIndustrial = 'SP23';
    case DieselUltraBajoAzufre = 'SP24';
    case DieselAgricola = 'SP25';
    case GasoleoDomestico = 'SP48';
}