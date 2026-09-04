<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator;

use ChabJose\CfdiGenerator\Contracts\ComprobanteBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\SelladorInterface;
use ChabJose\CfdiGenerator\Contracts\ValidadorInterface;
use ChabJose\CfdiGenerator\Contracts\XmlMapperInterface;
use ChabJose\CfdiGenerator\Domain\CsdCredential;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Models\ComprobanteEmisor;
use ChabJose\CfdiGenerator\Models\ComprobanteReceptor;
use ChabJose\CfdiGenerator\Services\ComprobanteBuilder;
use ChabJose\CfdiGenerator\Services\ComprobanteImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\ComprobanteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use ChabJose\CfdiGenerator\Services\XmlMapper;

class CfdiGenerator
{
    private Comprobante $comprobante;
    private ?Comprobante $comprobanteConstruido = null;

    public function __construct(
        private ComprobanteBuilderInterface $builder,
        private XmlMapperInterface $xmlMapper,
        private ?SelladorInterface $sellador = null,
    ) {
        $this->comprobante = new Comprobante();
    }

    /**
     * Factory conveniente: arma toda la cadena de dependencias por defecto.
     * Para casos avanzados (mockear en tests, sustituir un calculador),
     * usa el constructor directamente con tus propias implementaciones.
     */
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

    public function emisor(
        string $rfc,
        string $nombre,
        string $regimenFiscal,
        ?string $facAtrAdquirente = null,
    ): self {
        $emisor = new ComprobanteEmisor();
        $emisor->Rfc = $rfc;
        $emisor->Nombre = $nombre;
        $emisor->RegimenFiscal = $regimenFiscal;
        $emisor->FacAtrAdquirente = $facAtrAdquirente;

        $this->comprobante->Emisor = $emisor;

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
        $receptor = new ComprobanteReceptor();
        $receptor->Rfc = $rfc;
        $receptor->Nombre = $nombre;
        $receptor->DomicilioFiscalReceptor = $domicilioFiscalReceptor;
        $receptor->RegimenFiscalReceptor = $regimenFiscalReceptor;
        $receptor->UsoCFDI = $usoCFDI;
        $receptor->ResidenciaFiscal = $residenciaFiscal;
        $receptor->NumRegIdTrib = $numRegIdTrib;

        $this->comprobante->Receptor = $receptor;

        return $this;
    }

    public function addConcepto(ComprobanteConcepto $concepto): self
    {
        $this->comprobante->Conceptos[] = $concepto;

        return $this;
    }

    /**
     * Ejecuta todos los cálculos (impuestos, importes, totales) y valida.
     * Cachea el resultado: llamar build() varias veces no repite el cálculo
     * salvo que se modifique el comprobante después.
     */
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

    public function sellar(CsdCredential $csd): self
    {
        if ($this->sellador === null) {
            throw new \LogicException(
                'No se configuró un Sellador. Pásalo en CfdiGenerator::make(sellador: ...) o en el constructor.'
            );
        }

        $comprobante = $this->build();
        $comprobante->NoCertificado = $csd->noCertificado;
        $comprobante->Certificado = $csd->certificadoBase64;

        $xmlSinSello = $this->xmlMapper->toXml($comprobante);
        $cadenaOriginal = $this->sellador->generarCadenaOriginal($xmlSinSello);
        $comprobante->Sello = $this->sellador->sellar($cadenaOriginal, $csd);

        // Invalida el cache para que buildXml() regenere el XML con el Sello ya incluido
        $this->comprobanteConstruido = $comprobante;

        return $this;
    }
}