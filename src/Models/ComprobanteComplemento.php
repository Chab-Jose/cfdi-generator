<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteComplemento
{
    /** @var array  (XmlElement[] → array) */
    public array $Any;

    public function addComplemento(string $nombre, object $complemento):self
    {
        $this->Any[$nombre] = $complemento;
        return $this;
    }
}
