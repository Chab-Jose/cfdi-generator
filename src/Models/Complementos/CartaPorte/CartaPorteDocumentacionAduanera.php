<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorteDocumentacionAduanera
{
    public string $TipoDocumento = '';
    public ?string $NumPedimento = null;
    public ?string $IdentDocAduanero = null;
    public ?string $RFCImpo = null;
}