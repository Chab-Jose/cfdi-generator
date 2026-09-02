<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteEmisor
{
    public string $Rfc;
    public string $Nombre;
    public string $RegimenFiscal;    
    public ?string $FacAtrAdquirente = null;
}
