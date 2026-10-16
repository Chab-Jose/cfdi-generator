<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

enum TipoPermiso: string
{
    case Per01 = 'PER01';
    case Per02 = 'PER02';
    case Per03 = 'PER03';
    case Per04 = 'PER04';
    case Per05 = 'PER05';
    case Per06 = 'PER06';
    case Per07 = 'PER07';
    case Per08 = 'PER08';
    case Per09 = 'PER09';
    case Per10 = 'PER10';
    case Per11 = 'PER11';

    public function descripcion(): string
    {
        return match ($this) {
            self::Per01 => 'Expendio en estaciones de servicio de petrolíferos',
            self::Per02 => 'Comercialización',
            self::Per03 => 'Distribución por otros medios distintos a ducto',
            self::Per04 => 'Expendio en estaciones de servicio multimodal de petrolíferos',
            self::Per05 => 'Expendio en estaciones de servicio de petrolíferos',
            self::Per06 => 'Comercialización',
            self::Per07 => 'Distribución por otros medios distintos a ducto',
            self::Per08 => 'Expendio en estaciones de servicio multimodal de petrolíferos',
            self::Per09 => 'Comercialización',
            self::Per10 => 'Comercialización',
            self::Per11 => 'Comercialización',
        };
    }
}