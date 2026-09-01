<?php

namespace ChabJose\CfdiGenerator\Models;

class ComprobanteCfdiRelacionados
{
    /** @var ComprobanteCfdiRelacionadosCfdiRelacionado[] */
    public array $CfdiRelacionado = [];

    public ?string $TipoRelacion = null;

    public function addCfdiRelacionado(ComprobanteCfdiRelacionadosCfdiRelacionado $cfdiRelacionado): self
    {
        $this->CfdiRelacionado[] = $cfdiRelacionado;
        return $this;
    }
}
