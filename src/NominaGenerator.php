<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator;

use ChabJose\CfdiGenerator\Abstracts\AbstractCfdiGenerator;
use ChabJose\CfdiGenerator\Contracts\ComprobanteBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\NominaBuilderInterface;
use ChabJose\CfdiGenerator\Contracts\SelladorInterface;
use ChabJose\CfdiGenerator\Contracts\XmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;
use ChabJose\CfdiGenerator\Models\Comprobante;
use ChabJose\CfdiGenerator\Models\ComprobanteComplemento;
use ChabJose\CfdiGenerator\Models\ComprobanteConcepto;
use ChabJose\CfdiGenerator\Services\Complementos\ComplementoRegistry;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaBuilder;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaDeduccionesCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaPercepcionesCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaTotalesCalculator;
use ChabJose\CfdiGenerator\Services\Complementos\Nomina\NominaXmlMapper;
use ChabJose\CfdiGenerator\Services\ComprobanteBuilder;
use ChabJose\CfdiGenerator\Services\ComprobanteImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\ComprobanteTotalesCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoCalculator;
use ChabJose\CfdiGenerator\Services\ConceptoImpuestosCalculator;
use ChabJose\CfdiGenerator\Services\FactorImpuestoResolver;
use ChabJose\CfdiGenerator\Services\XmlMapper;

class NominaGenerator extends AbstractCfdiGenerator
{
    private ?Nomina $nomina = null;

    public function __construct(
        ComprobanteBuilderInterface $builder,
        XmlMapperInterface $xmlMapper,
        private NominaBuilderInterface $nominaBuilder,
        ?SelladorInterface $sellador = null,
    ) {
        parent::__construct($builder, $xmlMapper, $sellador);
        $this->configurarComoTipoNomina();
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

    /**
     * RegimenFiscalReceptor ("605") y UsoCFDI ("CN01") se fijan automáticamente,
     * son obligatorios y fijos para cualquier CFDI de Nómina (ver NOM11/NOM12).
     */
    public function receptor(string $rfc, string $nombre, string $domicilioFiscalReceptor): self
    {
        $this->setReceptor(
            rfc: $rfc,
            nombre: $nombre,
            domicilioFiscalReceptor: $domicilioFiscalReceptor,
            regimenFiscalReceptor: '605',
            usoCFDI: 'CN01',
        );

        return $this;
    }

    public function nomina(Nomina $nomina): self
    {
        $this->nomina = $nomina;
        $this->comprobante->Complemento ??= new ComprobanteComplemento();
        $this->comprobante->Complemento->addComplemento('Nomina', $nomina);

        return $this;
    }

    public function build(): Comprobante
    {
        if ($this->comprobanteConstruido !== null) {
            return $this->comprobanteConstruido;
        }

        if ($this->nomina !== null) {
            $this->nominaBuilder->build($this->nomina);
            $this->propagarTotalesAlConceptoFijo($this->nomina);
        }

        return parent::build();
    }

    private function propagarTotalesAlConceptoFijo(Nomina $nomina): void
    {
        $concepto = $this->comprobante->Conceptos[0];

        $concepto->ValorUnitario = ($nomina->TotalPercepciones ?? 0.0) + ($nomina->TotalOtrosPagos ?? 0.0);
        $concepto->Descuento = $nomina->TotalDeducciones;
        // Importe se calcula solo (ValorUnitario * Cantidad) vía ConceptoCalculator
        // dentro de ComprobanteBuilder, ya que Importe sigue en 0.0 aquí.
    }

    private function configurarComoTipoNomina(): void
    {
        $this->comprobante->TipoDeComprobante = 'N';
        $this->comprobante->Moneda = 'MXN';
        $this->comprobante->FormaPago = '99';
        $this->comprobante->MetodoPago = 'PUE';
        $this->comprobante->Exportacion = '01';

        $concepto = new ComprobanteConcepto();
        $concepto->ClaveProdServ = '84111505';
        $concepto->Cantidad = 1.0;
        $concepto->ClaveUnidad = 'ACT';
        $concepto->Descripcion = 'Pago de nómina';
        $concepto->ObjetoImp = '01';
        // NoIdentificacion se deja null a propósito: "Este campo no debe existir"

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

        $nominaBuilder = new NominaBuilder(
            new NominaPercepcionesCalculator(),
            new NominaDeduccionesCalculator(),
            new NominaTotalesCalculator(),
        );

        $registry = new ComplementoRegistry();
        $registry->registrar(new NominaXmlMapper());

        return new self($comprobanteBuilder, new XmlMapper($registry), $nominaBuilder, $sellador);
    }
}