<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\HidroYPetro;

final class HidroYPetroMatriz
{
    public const CLAVE_UNIDAD_REQUERIDA = 'LTR';

    /** Patrón de NumeroPermiso por TipoPermiso, tal como lo publica el SAT (c_TipoPermiso). */
    private const PATRONES_PERMISO = [
        'PER01' => 'PL/[0-9]{1,20}/EXP/ES/[0-9]{4}',
        'PER02' => 'H/[0-9]{4,24}/COM/[0-9]{4}',
        'PER03' => 'PL/[0-9]{1,20}/DIS/OM/[0-9]{4}',
        'PER04' => 'PL/[0-9]{1,17}/EXP/ES/MM/[0-9]{4}',
        'PER05' => 'CNE/PL/[0-9]{1,16}/EXP/ES/[0-9]{4}',
        'PER06' => 'CNE/H/[0-9]{1,20}/COM/[0-9]{4}',
        'PER07' => 'CNE/PL/[0-9]{1,16}/DIS/OM/[0-9]{4}',
        'PER08' => 'CNE/PL/[0-9]{1,13}/EXP/ES/MM/[0-9]{4}',
        'PER09' => 'CNE/PL/[0-9]{1,19}/COM/[0-9]{4}',
        // Fuente SAT trae '#[a-zA-Z0-9]{14} ' con espacio final; se trata como
        // defecto de captura (mismo tipo de anomalía ya visto en c_FormaPago) y se recorta.
        'PER10' => '#[a-zA-Z0-9]{14}',
        'PER11' => 'F00\.07\.UH/[0-9]{1,20}/[0-9]{4}',
    ];

    /** SubProductoHYP válidos por ClaveHYP (c_ClaveHYP, columna SubProducto). */
    private const SUBPRODUCTOS_POR_CLAVE = [
        '15101505' => ['SP18', 'SP19', 'SP22', 'SP23', 'SP24', 'SP25', 'SP48'],
        '15101514' => ['SP16'],
        '15101515' => ['SP17'],
    ];

    public static function patronNumeroPermiso(string $tipoPermiso): ?string
    {
        return self::PATRONES_PERMISO[$tipoPermiso] ?? null;
    }

    public static function numeroPermisoValido(string $tipoPermiso, string $numeroPermiso): bool
    {
        $patron = self::patronNumeroPermiso($tipoPermiso);

        if ($patron === null) {
            return false;
        }

        return preg_match('~^' . $patron . '$~D', $numeroPermiso) === 1;
    }

    public static function subProductosValidosPara(string $claveHYP): array
    {
        return self::SUBPRODUCTOS_POR_CLAVE[$claveHYP] ?? [];
    }

    public static function subProductoValido(string $claveHYP, string $subProductoHYP): bool
    {
        return in_array($subProductoHYP, self::subProductosValidosPara($claveHYP), true);
    }
}
