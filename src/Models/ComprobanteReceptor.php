<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteReceptor
{
    public string $Rfc;
    public string $Nombre;
    public string $DomicilioFiscalReceptor;
    public string $RegimenFiscalReceptor;
    public string $UsoCFDI;
    
    public ?string $ResidenciaFiscal = null;
    public ?string $NumRegIdTrib = null;
}
