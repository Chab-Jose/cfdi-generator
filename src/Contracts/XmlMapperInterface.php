<?php
namespace ChabJose\CfdiGenerator\Contracts;

use ChabJose\CfdiGenerator\Models\Comprobante;

interface XmlMapperInterface
{
    public function map(Comprobante $comprobante): \DOMDocument;
}