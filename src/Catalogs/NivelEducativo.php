<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Catalogs;

enum NivelEducativo: string
{
    case Preescolar = 'Preescolar';
    case Primaria = 'Primaria';
    case Secundaria = 'Secundaria';
    case ProfesionalTecnico = 'Profesional técnico';
    case BachilleratoOEquivalente = 'Bachillerato o su equivalente';
}