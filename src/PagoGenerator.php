<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator;

use ChabJose\CfdiGenerator\Contracts\ComprobanteBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\PagosBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\SelladorInterface;
use ChabJose\CfdiGenerator\Contracts\XmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\Pagos;
use ChabJose\CfdiGenerator\Models\Complementos\Pagos\PagosPago;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteComplemento;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Services\ComprobanteBuilder;
use ChabJose\CfdiGenerator\Services\ComprobanteImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\ComprobanteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\DoctoRelacionadoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagosBuilder;
use ChabJose\CfdiGenerator\Services\Complementos\Pagos\PagosTotalesCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use ChabJose\CfdiGenerator\Services\XmlMapper;

class PagoGenerator extends AbstractCfdiGenerator
{
    public function __construct(
        ComprobanteBuilderInterface $builder,
        XmlMapperInterface $xmlMapper,
        private PagosBuilderInterface $pagosBuilder,
        ?SelladorInterface $sellador = null,
    ) {
        parent::__construct($builder, $xmlMapper, $sellador);
        $this->configurarComoTipoPago();
    }

    public function comprobante(string $lugarExpedicion, ?string $serie = null, ?string $folio = null): self
    {
        $this->comprobante->LugarExpedicion = $lugarExpedicion;
        $this->comprobante->Serie = $serie;
        $this->comprobante->Folio = $folio;
        $this->comprobante->Fecha = (new \DateTimeImmutable())->format('Y-m-d\TH:i:s');

        return $this;
    }

    public function emisor(string $rfc, string $nombre, string $regimenFiscal, ?string $facAtrAdquirente = null): self
    {
        $this->setEmisor($rfc, $nombre, $regimenFiscal, $facAtrAdquirente);
        return $this;
    }

    public function receptor(
        string $rfc,
        string $nombre,
        string $domicilioFiscalReceptor,
        string $regimenFiscalReceptor,
        string $usoCFDI,
        ?string $residenciaFiscal = null,
        ?string $numRegIdTrib = null,
    ): self {
        $this->setReceptor($rfc, $nombre, $domicilioFiscalReceptor, $regimenFiscalReceptor, $usoCFDI, $residenciaFiscal, $numRegIdTrib);
        return $this;
    }

    public function pago(PagosPago $pago): self
    {
        $this->comprobante->Complemento ??= new ComprobanteComplemento();

        $pagosExistente = $this->comprobante->Complemento->Any['Pagos'] ?? null;

        if ($pagosExistente instanceof Pagos) {
            $pagosExistente->addPago($pago);
        } else {
            $pagos = new Pagos();
            $pagos->addPago($pago);
            $this->comprobante->Complemento->addComplemento('Pagos', $pagos);
        }

        return $this;
    }

    public function build(): Comprobante
    {
        if ($this->comprobanteConstruido !== null) {
            return $this->comprobanteConstruido;
        }

        $pagos = $this->comprobante->Complemento->Any['Pagos'] ?? null;

        if ($pagos instanceof Pagos) {
            $this->pagosBuilder->build($pagos);
        }

        return parent::build();
    }

    private function configurarComoTipoPago(): void
    {
        $this->comprobante->TipoDeComprobante = 'P';
        $this->comprobante->Moneda = 'XXX';
        $this->comprobante->SubTotal = 0.0;
        $this->comprobante->Total = 0.0;
        $this->comprobante->Exportacion = '01';

        $concepto = new ComprobanteConcepto();
        $concepto->ClaveProdServ = '84111506';
        $concepto->NoIdentificacion = 'Pago';
        $concepto->Cantidad = 1.0;
        $concepto->ClaveUnidad = 'ACT';
        $concepto->Descripcion = 'Pago';
        $concepto->ValorUnitario = 0.0;
        $concepto->Importe = 0.0;
        $concepto->ObjetoImp = '01';

        $this->comprobante->Conceptos = [$concepto];
    }

    public static function make(?SelladorInterface $sellador = null): self
    {
        $factorResolver = new FactorImpuestoResolver();

        $comprobanteBuilder = new ComprobanteBuilder(
            new ConceptoImpuestosCalculator($factorResolver),
            new ConceptoCalculator(),
            new ComprobanteImpuestosCalculator(),
            new ComprobanteTotalesCalculator(),
        );

        $pagosBuilder = new PagosBuilder(
            new DoctoRelacionadoImpuestosCalculator($factorResolver),
            new PagoImpuestosCalculator(),
            new PagosTotalesCalculator(),
        );

        return new self($comprobanteBuilder, new XmlMapper(), $pagosBuilder, $sellador);
    }
}
