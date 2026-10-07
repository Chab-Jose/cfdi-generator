<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\CartaPorte;

use ChabJose\CfdiGenerator\Contracts\ComplementoXmlMapperInterface;
use ChabJose\CfdiGenerator\Contracts\MedioTransporteXmlMapperInterface;
use ChabJose\CfdiGenerator\Exceptions\CartaPorteValidationException;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteDomicilio;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteFiguraTransporte;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancia;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteMercancias;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteTipoFigura;
use ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\CartaPorteUbicacion;
use ChabJose\CfdiGenerator\Utils\Xml\XmlAttributeHelpersTrait;

class CartaPorteXmlMapper implements ComplementoXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS_URI = 'http://www.sat.gob.mx/CartaPorte31';
    private const NS_PREFIX = 'cartaporte31';

    /** @param MedioTransporteXmlMapperInterface[] $mediosTransporte */
    public function __construct(
        private array $mediosTransporte,
    ) {
    }

    public function soporta(object $complemento): bool
    {
        return $complemento instanceof CartaPorte;
    }

    public function namespacePrefix(): string
    {
        return self::NS_PREFIX;
    }

    public function namespaceUri(): string
    {
        return self::NS_URI;
    }

    public function schemaLocation(): string
    {
        return self::NS_URI . ' http://www.sat.gob.mx/sitio_internet/cfd/CartaPorte/CartaPorte31.xsd';
    }

    public function toXmlElement(\DOMDocument $doc, object $complemento): \DOMElement
    {
        if (!$complemento instanceof CartaPorte) {
            throw new \InvalidArgumentException('CartaPorteXmlMapper solo procesa instancias de CartaPorte.');
        }

        $root = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':CartaPorte');

        $this->setRequiredAttr($root, 'Version', $complemento->Version);
        $this->setRequiredAttr($root, 'IdCCP', $complemento->IdCCP);
        $this->setRequiredAttr($root, 'TranspInternac', $complemento->TranspInternac);
        $this->setAttr($root, 'EntradaSalidaMerc', $complemento->EntradaSalidaMerc);
        $this->setAttr($root, 'PaisOrigenDestino', $complemento->PaisOrigenDestino);
        $this->setAttr($root, 'ViaEntradaSalida', $complemento->ViaEntradaSalida);
        $this->setAttr($root, 'TotalDistRec', $this->formatDecimal($complemento->TotalDistRec, 3));
        $this->setAttr($root, 'RegistroISTMO', $complemento->RegistroISTMO);
        $this->setAttr($root, 'UbicacionPoloOrigen', $complemento->UbicacionPoloOrigen);
        $this->setAttr($root, 'UbicacionPoloDestino', $complemento->UbicacionPoloDestino);

        if (!empty($complemento->RegimenesAduaneros)) {
            $regimenesNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':RegimenesAduaneros');
            foreach ($complemento->RegimenesAduaneros as $regimen) {
                $regimenNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':RegimenAduaneroCCP');
                $this->setRequiredAttr($regimenNode, 'RegimenAduanero', $regimen->RegimenAduanero);
                $regimenesNode->appendChild($regimenNode);
            }
            $root->appendChild($regimenesNode);
        }

        $ubicacionesNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Ubicaciones');
        foreach ($complemento->Ubicaciones as $ubicacion) {
            $ubicacionesNode->appendChild($this->crearUbicacion($doc, $ubicacion));
        }
        $root->appendChild($ubicacionesNode);

        if ($complemento->Mercancias !== null) {
            $root->appendChild($this->crearMercancias($doc, $complemento->Mercancias));
        }

        if (!empty($complemento->FiguraTransporte)) {
            $root->appendChild($this->crearFiguraTransporteContenedor($doc, $complemento->FiguraTransporte));
        }

        return $root;
    }

    // ---------- Ubicaciones ----------

    private function crearUbicacion(\DOMDocument $doc, CartaPorteUbicacion $u): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Ubicacion');

        $this->setRequiredAttr($node, 'TipoUbicacion', $u->TipoUbicacion);
        $this->setAttr($node, 'IDUbicacion', $u->IDUbicacion);
        $this->setRequiredAttr($node, 'RFCRemitenteDestinatario', $u->RFCRemitenteDestinatario);
        $this->setAttr($node, 'NombreRemitenteDestinatario', $u->NombreRemitenteDestinatario);
        $this->setAttr($node, 'NumRegIdTrib', $u->NumRegIdTrib);
        $this->setAttr($node, 'ResidenciaFiscal', $u->ResidenciaFiscal);
        $this->setAttr($node, 'NumEstacion', $u->NumEstacion);
        $this->setAttr($node, 'NombreEstacion', $u->NombreEstacion);
        $this->setAttr($node, 'NavegacionTrafico', $u->NavegacionTrafico);
        $this->setRequiredAttr($node, 'FechaHoraSalidaLlegada', $u->FechaHoraSalidaLlegada);
        $this->setAttr($node, 'TipoEstacion', $u->TipoEstacion);
        $this->setAttr($node, 'DistanciaRecorrida', $this->formatDecimal($u->DistanciaRecorrida, 3));

        if ($u->Domicilio !== null) {
            $node->appendChild($this->crearDomicilio($doc, $u->Domicilio));
        }

        return $node;
    }

    private function crearDomicilio(\DOMDocument $doc, CartaPorteDomicilio $d): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Domicilio');
        $this->setAttr($node, 'Calle', $d->Calle);
        $this->setAttr($node, 'NumeroExterior', $d->NumeroExterior);
        $this->setAttr($node, 'NumeroInterior', $d->NumeroInterior);
        $this->setAttr($node, 'Colonia', $d->Colonia);
        $this->setAttr($node, 'Localidad', $d->Localidad);
        $this->setAttr($node, 'Referencia', $d->Referencia);
        $this->setAttr($node, 'Municipio', $d->Municipio);
        $this->setRequiredAttr($node, 'Estado', $d->Estado);
        $this->setRequiredAttr($node, 'Pais', $d->Pais);
        $this->setRequiredAttr($node, 'CodigoPostal', $d->CodigoPostal);
        return $node;
    }

    // ---------- Mercancias ----------

    private function crearMercancias(\DOMDocument $doc, CartaPorteMercancias $m): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Mercancias');

        $this->setRequiredAttr($node, 'PesoBrutoTotal', $this->formatDecimal($m->PesoBrutoTotal, 3) ?? '');
        $this->setRequiredAttr($node, 'UnidadPeso', $m->UnidadPeso);
        $this->setAttr($node, 'PesoNetoTotal', $this->formatDecimal($m->PesoNetoTotal, 3));
        $this->setRequiredAttr($node, 'NumTotalMercancias', (string) $m->NumTotalMercancias);
        $this->setAttr($node, 'CargoPorTasacion', $this->formatDecimal($m->CargoPorTasacion));

        foreach ($m->Mercancia as $mercancia) {
            $node->appendChild($this->crearMercancia($doc, $mercancia));
        }

        $mapperDelMedio = $this->encontrarMapperDelMedio($m);
        $node->appendChild($mapperDelMedio->mapear($doc, $m));

        return $node;
    }

    private function encontrarMapperDelMedio(CartaPorteMercancias $m): MedioTransporteXmlMapperInterface
    {
        foreach ($this->mediosTransporte as $mapper) {
            if ($mapper->soporta($m)) {
                return $mapper;
            }
        }

        throw new CartaPorteValidationException([
            'No se encontró un medio de transporte válido (Autotransporte, Marítimo, Aéreo o Ferroviario) en Mercancias.',
        ]);
    }

    private function crearMercancia(\DOMDocument $doc, CartaPorteMercancia $mercancia): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Mercancia');

        $this->setRequiredAttr($node, 'BienesTransp', $mercancia->BienesTransp);
        $this->setAttr($node, 'ClaveSTCC', $mercancia->ClaveSTCC);
        $this->setRequiredAttr($node, 'Descripcion', $mercancia->Descripcion);
        $this->setRequiredAttr($node, 'Cantidad', $this->formatDecimal($mercancia->Cantidad) ?? '');
        $this->setRequiredAttr($node, 'ClaveUnidad', $mercancia->ClaveUnidad);
        $this->setAttr($node, 'Unidad', $mercancia->Unidad);
        $this->setAttr($node, 'Dimensiones', $mercancia->Dimensiones);
        $this->setAttr($node, 'MaterialPeligroso', $mercancia->MaterialPeligroso);
        $this->setAttr($node, 'CveMaterialPeligroso', $mercancia->CveMaterialPeligroso);
        $this->setAttr($node, 'Embalaje', $mercancia->Embalaje);
        $this->setAttr($node, 'DescripEmbalaje', $mercancia->DescripEmbalaje);
        $this->setAttr($node, 'SectorCOFEPRIS', $mercancia->SectorCOFEPRIS);
        $this->setAttr($node, 'NombreIngredienteActivo', $mercancia->NombreIngredienteActivo);
        $this->setAttr($node, 'NomQuimico', $mercancia->NomQuimico);
        $this->setAttr($node, 'DenominacionGenericaProd', $mercancia->DenominacionGenericaProd);
        $this->setAttr($node, 'DenominacionDistintivaProd', $mercancia->DenominacionDistintivaProd);
        $this->setAttr($node, 'Fabricante', $mercancia->Fabricante);
        $this->setAttr($node, 'FechaCaducidad', $mercancia->FechaCaducidad);
        $this->setAttr($node, 'LoteMedicamento', $mercancia->LoteMedicamento);
        $this->setAttr($node, 'FormaFarmaceutica', $mercancia->FormaFarmaceutica);
        $this->setAttr($node, 'CondicionesEspTransp', $mercancia->CondicionesEspTransp);
        $this->setAttr($node, 'RegistroSanitarioFolioAutorizacion', $mercancia->RegistroSanitarioFolioAutorizacion);
        $this->setAttr($node, 'PermisoImportacion', $mercancia->PermisoImportacion);
        $this->setAttr($node, 'FolioImpoVUCEM', $mercancia->FolioImpoVUCEM);
        $this->setAttr($node, 'NumCAS', $mercancia->NumCAS);
        $this->setAttr($node, 'RazonSocialEmpImp', $mercancia->RazonSocialEmpImp);
        $this->setAttr($node, 'NumRegSanPlagCOFEPRIS', $mercancia->NumRegSanPlagCOFEPRIS);
        $this->setAttr($node, 'DatosFabricante', $mercancia->DatosFabricante);
        $this->setAttr($node, 'DatosFormulador', $mercancia->DatosFormulador);
        $this->setAttr($node, 'DatosMaquilador', $mercancia->DatosMaquilador);
        $this->setAttr($node, 'UsoAutorizado', $mercancia->UsoAutorizado);
        $this->setRequiredAttr($node, 'PesoEnKg', $this->formatDecimal($mercancia->PesoEnKg, 3) ?? '');
        $this->setAttr($node, 'ValorMercancia', $this->formatDecimal($mercancia->ValorMercancia));
        $this->setAttr($node, 'Moneda', $mercancia->Moneda);
        $this->setAttr($node, 'FraccionArancelaria', $mercancia->FraccionArancelaria);
        $this->setAttr($node, 'UUIDComercioExt', $mercancia->UUIDComercioExt);
        $this->setAttr($node, 'TipoMateria', $mercancia->TipoMateria);
        $this->setAttr($node, 'DescripcionMateria', $mercancia->DescripcionMateria);

        foreach ($mercancia->DocumentacionAduanera as $documentacion) {
            $docNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':DocumentacionAduanera');
            $this->setRequiredAttr($docNode, 'TipoDocumento', $documentacion->TipoDocumento);
            $this->setAttr($docNode, 'NumPedimento', $documentacion->NumPedimento);
            $this->setAttr($docNode, 'IdentDocAduanero', $documentacion->IdentDocAduanero);
            $this->setAttr($docNode, 'RFCImpo', $documentacion->RFCImpo);
            $node->appendChild($docNode);
        }

        foreach ($mercancia->GuiasIdentificacion as $guia) {
            $guiaNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':GuiasIdentificacion');
            $this->setRequiredAttr($guiaNode, 'NumeroGuiaIdentificacion', $guia->NumeroGuiaIdentificacion);
            $this->setRequiredAttr($guiaNode, 'DescripGuiaIdentificacion', $guia->DescripGuiaIdentificacion);
            $this->setRequiredAttr($guiaNode, 'PesoGuiaIdentificacion', $this->formatDecimal($guia->PesoGuiaIdentificacion) ?? '');
            $node->appendChild($guiaNode);
        }

        foreach ($mercancia->CantidadTransporta as $cantidad) {
            $cantidadNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':CantidadTransporta');
            $this->setRequiredAttr($cantidadNode, 'Cantidad', $this->formatDecimal($cantidad->Cantidad) ?? '');
            $this->setRequiredAttr($cantidadNode, 'IDOrigen', $cantidad->IDOrigen);
            $this->setRequiredAttr($cantidadNode, 'IDDestino', $cantidad->IDDestino);
            $this->setAttr($cantidadNode, 'CvesTransporte', $cantidad->CvesTransporte);
            $node->appendChild($cantidadNode);
        }

        if ($mercancia->DetalleMercancia !== null) {
            $dm = $mercancia->DetalleMercancia;
            $dmNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':DetalleMercancia');
            $this->setRequiredAttr($dmNode, 'UnidadPesoMerc', $dm->UnidadPesoMerc);
            $this->setRequiredAttr($dmNode, 'PesoBruto', $this->formatDecimal($dm->PesoBruto, 3) ?? '');
            $this->setRequiredAttr($dmNode, 'PesoNeto', $this->formatDecimal($dm->PesoNeto, 3) ?? '');
            $this->setRequiredAttr($dmNode, 'PesoTara', $this->formatDecimal($dm->PesoTara, 3) ?? '');
            $this->setAttr($dmNode, 'NumPiezas', $dm->NumPiezas !== null ? (string) $dm->NumPiezas : null);
            $node->appendChild($dmNode);
        }

        return $node;
    }

    // ---------- FiguraTransporte ----------

    /** @param CartaPorteFiguraTransporte[] $figurasTransporte */
    private function crearFiguraTransporteContenedor(\DOMDocument $doc, array $figurasTransporte): \DOMElement
    {
        $contenedorNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':FiguraTransporte');

        foreach ($figurasTransporte as $figuraTransporte) {
            foreach ($figuraTransporte->TiposFigura as $tipoFigura) {
                $contenedorNode->appendChild($this->crearTipoFigura($doc, $tipoFigura));
            }
        }

        return $contenedorNode;
    }

    private function crearTipoFigura(\DOMDocument $doc, CartaPorteTipoFigura $f): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':TiposFigura');

        $this->setRequiredAttr($node, 'TipoFigura', $f->TipoFigura);
        $this->setAttr($node, 'RFCFigura', $f->RFCFigura);
        $this->setAttr($node, 'NumLicencia', $f->NumLicencia);
        $this->setRequiredAttr($node, 'NombreFigura', $f->NombreFigura);
        $this->setAttr($node, 'NumRegIdTribFigura', $f->NumRegIdTribFigura);
        $this->setAttr($node, 'ResidenciaFiscalFigura', $f->ResidenciaFiscalFigura);

        foreach ($f->PartesTransporte as $parte) {
            $parteNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':PartesTransporte');
            $this->setRequiredAttr($parteNode, 'ParteTransporte', $parte->ParteTransporte);
            $node->appendChild($parteNode);
        }

        if ($f->Domicilio !== null) {
            $node->appendChild($this->crearDomicilio($doc, $f->Domicilio));
        }

        return $node;
    }
}