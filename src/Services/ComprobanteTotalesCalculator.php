
    // Cálculos de totales
    public function getSubtotal()
    {
        $subtotalFinal = array_sum(array_map(fn(ComprobanteConcepto $c) => round($c->Importe,2, PHP_ROUND_HALF_UP), $this->Conceptos));
        $this->SubTotal = $subtotalFinal;
        return $subtotalFinal;
    }

    public function getDescuento()
    {
        $descuentos = array_filter(
            array_map(
                fn(ComprobanteConcepto $c) => isset($c->Descuento)
                    ? round($c->Descuento, 2, PHP_ROUND_HALF_UP)
                    : null,
                $this->Conceptos
            ),
            fn($d) => $d !== null
        );

        $totalDescuento = empty($descuentos) ? null : array_sum($descuentos);
        $this->Descuento = $totalDescuento;

        return $totalDescuento;
    }

    public function getTotal()
    {
        if ($this->Impuestos !== null) {
            $this->Impuestos->getImpuestosRetenidos();
            $this->Impuestos->getImpuestosTraslados();
        }
        
        $subtotalFinal = $this->getSubtotal() ?? 0.0;
        $descuentoFinal = $this->getDescuento() ?? 0.0;
        
        $impuestosTraslados = $this->Impuestos->TotalImpuestosTrasladados ?? 0.0;
        $impuestosRetenidos = $this->Impuestos->TotalImpuestosRetenidos ?? 0.0;

        $this->Total = round($subtotalFinal - $descuentoFinal + $impuestosTraslados - $impuestosRetenidos, 2, PHP_ROUND_HALF_UP);    
        return $this->Total;
    }

    public function CalcularConceptoImporte(){
        $this->Importe = round($this->ValorUnitario * $this->Cantidad, 6, PHP_ROUND_HALF_UP);
    }

    public function calcularRetencionImporte()
    {
        $this->Importe = round($this->Base * $this->TasaOCuota, 4, PHP_ROUND_HALF_UP);
    }

    public function calcularTrasladoImporte()
    {
        $this->Importe = round($this->Base * $this->TasaOCuota, 4, PHP_ROUND_HALF_UP);
    }

    public function getImpuestosTraslados()
    {
        if (count($this->Traslados) > 0){
            $total = array_sum(array_map(fn(ComprobanteImpuestosTraslado $t) => round($t->Importe, 6, PHP_ROUND_HALF_UP), $this->Traslados));
            $this->TotalImpuestosTrasladados = $total;
        }
    }

    public function getImpuestosRetenidos()
    {
        if (count($this->Retenciones) > 0){
            $total = array_sum(array_map(fn(ComprobanteImpuestosRetencion $r) => round($r->Importe, 6, PHP_ROUND_HALF_UP), $this->Retenciones));
            $this->TotalImpuestosRetenidos = $total;
        }
    }