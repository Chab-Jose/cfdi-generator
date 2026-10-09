<?php

namespace ChabJose\CfdiGenerator\Models;

use ChabJose\CfdiGenerator\Catalogs\RegimenFiscal;

class ComprobanteEmisor
{
    public string $Rfc = '';
    public string $Nombre = '';
    public string|RegimenFiscal $RegimenFiscal = '';    
    public ?string $FacAtrAdquirente = null;
}
