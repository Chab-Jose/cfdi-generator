<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator;

use ChabJose\CfdiGenerator\Abstracts\AbstractCfdiGenerator;
use ChabJose\CfdiGenerator\Contracts\ValidadorInterface;
use ChabJose\CfdiGenerator\Contracts\SelladorInterface;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Services\ComprobanteBuilder;
use ChabJose\CfdiGenerator\Services\ComprobanteImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\ComprobanteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use ChabJose\CfdiGenerator\Services\XmlMapper;

class CfdiGenerator extends AbstractCfdiGenerator
{
    public function comprobante(
        string $tipoDeComprobante,
        string $moneda,
        string $lugarExpedicion,
        string $exportacion = '01',
        ?string $serie = null,
        ?string $folio = null,
        ?string $formaPago = null,
        ?string $metodoPago = null,
        ?string $condicionesDePago = null,
        ?float $tipoCambio = null,
    ): self {
        $this->comprobante->TipoDeComprobante = $tipoDeComprobante;
        $this->comprobante->Moneda = $moneda;
        $this->comprobante->LugarExpedicion = $lugarExpedicion;
        $this->comprobante->Exportacion = $exportacion;
        $this->comprobante->Serie = $serie;
        $this->comprobante->Folio = $folio;
        $this->comprobante->FormaPago = $formaPago;
        $this->comprobante->MetodoPago = $metodoPago;
        $this->comprobante->CondicionesDePago = $condicionesDePago;
        $this->comprobante->TipoCambio = $tipoCambio;
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

    public function addConcepto(ComprobanteConcepto $concepto): self
    {
        $this->comprobante->Conceptos[] = $concepto;
        return $this;
    }

    public static function make(?ValidadorInterface $validador = null, ?SelladorInterface $sellador = null): self
    {
        $factorResolver = new FactorImpuestoResolver();

        $builder = new ComprobanteBuilder(
            new ConceptoImpuestosCalculator($factorResolver),
            new ConceptoCalculator(),
            new ComprobanteImpuestosCalculator(),
            new ComprobanteTotalesCalculator(),
            $validador,
        );

        return new self($builder, new XmlMapper(), $sellador);
    }
}