<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteAddenda
{
    /** @var array  (XmlElement[] → array) */
    public array $Any = [];

    public function addAddenda(string $nombre, $addenda):self
    {
        $this->Any[$nombre] = $addenda;
        return $this;
    }
}
