<?php

namespace ChabJose\CfdiGenerator\Models\Complementos\Pagos;

class PagosPago
{
    /** @var PagosPagoDoctoRelacionado[] */
    public array $DoctoRelacionado = [];

    public ?PagosPagoImpuestosP $ImpuestosP = null;

    public string $FechaPago;
    public string $FormaDePagoP;
    public string $MonedaP;
    public float $Monto = 0.0;
    
    
    public ?float $TipoCambioP = null;
    public ?string $NumOperacion = null;
    public ?string $RfcEmisorCtaOrd = null;
    public ?string $NomBancoOrdExt = null;
    public ?string $CtaOrdenante = null;
    public ?string $RfcEmisorCtaBen = null;
    public ?string $CtaBeneficiario = null;
    public ?string $TipoCadPago = null;
    public ?string $CertPago = null;
    public ?string $CadPago = null;
    public ?string $SelloPago = null;

    public function addDoctoRelacionado(PagosPagoDoctoRelacionado $doctoRelacionado): self
    {
        $this->DoctoRelacionado[] = $doctoRelacionado;
        return $this;
    }
}
