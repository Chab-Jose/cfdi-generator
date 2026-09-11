<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteConceptoComplementoConcepto
{
    public array $Any = [];

    public function addComplementoConcepto(string $nombre, $complemento): self
    {
        $this->Any[$nombre] = $complemento;
        return $this;
    }
}
