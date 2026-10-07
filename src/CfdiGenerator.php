<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator;

use ChabJose\CfdiGenerator\Abstracts\AbstractCfdiGenerator;
use ChabJose\CfdiGenerator\Contracts\ComprobanteBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\ValidadorInterface;
use ChabJose\CfdiGenerator\Contracts\SelladorInterface;
use ChabJose\CfdiGenerator\Contracts\XmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteBuilder;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteComplementoBuilderAdapter;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteMercanciasCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\CartaPorteXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\IdCcpGenerator;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\AereoXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\AutotransporteXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\FerroviarioXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MediosTransporte\MaritimoXmlMapper;
use ChabJose\CfdiGenerator\Services\Complementos\CartaPorte\MedioTransporteValidator;
use ChabJose\CfdiGenerator\Services\Complementos\ComplementoBuilderRegistry;
use ChabJose\CfdiGenerator\Services\Complementos\ComplementoRegistry;
use ChabJose\CfdiGenerator\Services\ComprobanteBuilder;
use ChabJose\CfdiGenerator\Services\ComprobanteImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\ComprobanteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use ChabJose\CfdiGenerator\Services\XmlMapper;

class CfdiGenerator extends AbstractCfdiGenerator
{
    public function __construct(
        ComprobanteBuilderInterface $builder,
        XmlMapperInterface $xmlMapper,
        private ComplementoBuilderRegistry $complementoBuilderRegistry,
        ?SelladorInterface $sellador = null,
    ) {
        parent::__construct($builder, $xmlMapper, $sellador);
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

    public function cartaPorte(CartaPorte $cartaPorte): self
    {
        $this->attachComplemento('CartaPorte', $cartaPorte);
        return $this;
    }

    public function build(): Comprobante
    {
        if ($this->comprobanteConstruido !== null) {
            return $this->comprobanteConstruido;
        }

        if ($this->comprobante->Complemento !== null) {
            foreach ($this->comprobante->Complemento->Any as $complemento) {
                $builder = $this->complementoBuilderRegistry->encontrarPara($complemento);
                $builder?->build($complemento); // null-safe: si no hay builder, se omite sin error
            }
        }

        return parent::build();
    }

    public static function make(?ValidadorInterface $validador = null, ?SelladorInterface $sellador = null): self
    {
        $factorResolver = new FactorImpuestoResolver();

        $comprobanteBuilder = new ComprobanteBuilder(
            new ConceptoImpuestosCalculator($factorResolver),
            new ConceptoCalculator(),
            new ComprobanteImpuestosCalculator(),
            new ComprobanteTotalesCalculator(),
            $validador,
        );

        $xmlComplementoRegistry = new ComplementoRegistry();
        $xmlComplementoRegistry->registrar(new CartaPorteXmlMapper([
            new AutotransporteXmlMapper(),
            new MaritimoXmlMapper(),
            new AereoXmlMapper(),
            new FerroviarioXmlMapper(),
        ]));


        $complementoBuilderRegistry = new ComplementoBuilderRegistry();
        $complementoBuilderRegistry->registrar(new CartaPorteComplementoBuilderAdapter(
            new CartaPorteBuilder(
                new CartaPorteMercanciasCalculator(),
                new CartaPorteTotalesCalculator(),
                MedioTransporteValidator::conMediosEstandar(),
                new IdCcpGenerator(),
            )
        ));

        return new self($comprobanteBuilder, new XmlMapper($xmlComplementoRegistry), $complementoBuilderRegistry, $sellador);
    }
}