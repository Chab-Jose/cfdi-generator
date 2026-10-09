<?php

namespace ChabJose\CfdiGenerator\Models;

use ChabJose\CfdiGenerator\Catalogs\RegimenFiscal;
use ChabJose\CfdiGenerator\Catalogs\UsoCfdi;

class ComprobanteReceptor
{
    public string $Rfc = '';
    public string $Nombre = '';
    public string $DomicilioFiscalReceptor = '';
    public string|RegimenFiscal $RegimenFiscalReceptor = '';
    public string|UsoCfdi $UsoCFDI = '';
    
    public ?string $ResidenciaFiscal = null;
    public ?string $NumRegIdTrib = null;
}
