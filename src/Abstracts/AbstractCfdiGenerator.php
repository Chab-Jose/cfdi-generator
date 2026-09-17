<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator;

use ChabJose\CfdiGenerator\Contracts\ComprobanteBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\SelladorInterface;
use ChabJose\CfdiGenerator\Contracts\TimbradoInterface;
use ChabJose\CfdiGenerator\Contracts\XmlMapperInterface;
use ChabJose\CfdiGenerator\Domain\CsdCredential;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteEmisor;
use ChabJose\CfdiGenerator\Models\ComprobanteReceptor;

abstract class AbstractCfdiGenerator
{
    protected Comprobante $comprobante;
    protected ?Comprobante $comprobanteConstruido = null;
    protected string $xmlTimbrado;

    public function __construct(
        protected ComprobanteBuilderInterface $builder,
        protected XmlMapperInterface $xmlMapper,
        protected ?SelladorInterface $sellador = null,
    ) {
        $this->comprobante = new Comprobante();
    }

    protected function setEmisor(
        string $rfc,
        string $nombre,
        string $regimenFiscal,
        ?string $facAtrAdquirente = null,
    ): void {
        $emisor = new ComprobanteEmisor();
        $emisor->Rfc = $rfc;
        $emisor->Nombre = $nombre;
        $emisor->RegimenFiscal = $regimenFiscal;
        $emisor->FacAtrAdquirente = $facAtrAdquirente;

        $this->comprobante->Emisor = $emisor;
    }

    protected function setReceptor(
        string $rfc,
        string $nombre,
        string $domicilioFiscalReceptor,
        string $regimenFiscalReceptor,
        string $usoCFDI,
        ?string $residenciaFiscal = null,
        ?string $numRegIdTrib = null,
    ): void {
        $receptor = new ComprobanteReceptor();
        $receptor->Rfc = $rfc;
        $receptor->Nombre = $nombre;
        $receptor->DomicilioFiscalReceptor = $domicilioFiscalReceptor;
        $receptor->RegimenFiscalReceptor = $regimenFiscalReceptor;
        $receptor->UsoCFDI = $usoCFDI;
        $receptor->ResidenciaFiscal = $residenciaFiscal;
        $receptor->NumRegIdTrib = $numRegIdTrib;

        $this->comprobante->Receptor = $receptor;
    }

    public function build(): Comprobante
    {
        if ($this->comprobanteConstruido === null) {
            $this->comprobanteConstruido = $this->builder->build($this->comprobante);
        }

        return $this->comprobanteConstruido;
    }

    public function buildXml(): string
    {
        return $this->xmlMapper->toXml($this->build());
    }

    public function sellar(CsdCredential $csd): static
    {
        if ($this->sellador === null) {
            throw new \LogicException('No se configuró un Sellador.');
        }

        $comprobante = $this->build();
        $comprobante->NoCertificado = $csd->noCertificado;
        $comprobante->Certificado = $csd->certificadoBase64;

        $xmlSinSello = $this->xmlMapper->toXml($comprobante);
        $cadenaOriginal = $this->sellador->generarCadenaOriginal($xmlSinSello);
        $comprobante->Sello = $this->sellador->sellar($cadenaOriginal, $csd);

        $this->comprobanteConstruido = $comprobante;

        return $this;
    }

    public function buildXmlBase64(): string
    {
        return base64_encode($this->buildXml());
    }

    public function timbrar(TimbradoInterface $timbrador): self
    {
        $xmlSellado = $this->buildXml();
        $xmlTimbrado = $timbrador->timbrar($xmlSellado);

        $this->xmlTimbrado = $xmlTimbrado;

        return $this;
    }

    public function xmlFinal(): string
    {
        return $this->xmlTimbrado ?? $this->buildXml();
    }
}