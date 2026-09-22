<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Services\Complementos\Nomina;

use ChabJose\CfdiGenerator\Contracts\ComplementoXmlMapperInterface;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\Nomina;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaDeduccion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaIncapacidad;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaOtroPago;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaPercepcion;
use ChabJose\CfdiGenerator\Models\Complementos\Nomina\NominaReceptorSubContratacion;
use ChabJose\CfdiGenerator\Utils\Xml\XmlAttributeHelpersTrait;

class NominaXmlMapper implements ComplementoXmlMapperInterface
{
    use XmlAttributeHelpersTrait;

    private const NS_URI = 'http://www.sat.gob.mx/nomina12';
    private const NS_PREFIX = 'nomina12';

    public function soporta(object $complemento): bool
    {
        return $complemento instanceof Nomina;
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
        return self::NS_URI . ' http://www.sat.gob.mx/sitio_internet/cfd/nomina/nomina12.xsd';
    }

    public function toXmlElement(\DOMDocument $doc, object $complemento): \DOMElement
    {
        if (!$complemento instanceof Nomina) {
            throw new \InvalidArgumentException('NominaXmlMapper solo procesa instancias de Nomina.');
        }

        $root = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Nomina');

        $this->setRequiredAttr($root, 'Version', $complemento->Version);
        $this->setRequiredAttr($root, 'TipoNomina', $complemento->TipoNomina);
        $this->setRequiredAttr($root, 'FechaPago', $complemento->FechaPago);
        $this->setRequiredAttr($root, 'FechaInicialPago', $complemento->FechaInicialPago);
        $this->setRequiredAttr($root, 'FechaFinalPago', $complemento->FechaFinalPago);
        $this->setRequiredAttr($root, 'NumDiasPagados', $this->formatDecimal($complemento->NumDiasPagados, 3) ?? '');
        $this->setAttr($root, 'TotalPercepciones', $this->formatDecimal($complemento->TotalPercepciones));
        $this->setAttr($root, 'TotalDeducciones', $this->formatDecimal($complemento->TotalDeducciones));
        $this->setAttr($root, 'TotalOtrosPagos', $this->formatDecimal($complemento->TotalOtrosPagos));

        if ($complemento->Emisor !== null) {
            $root->appendChild($this->crearEmisor($doc, $complemento->Emisor));
        }

        if ($complemento->Receptor !== null) {
            $root->appendChild($this->crearReceptor($doc, $complemento->Receptor));
        }

        if ($complemento->Percepciones !== null) {
            $root->appendChild($this->crearPercepciones($doc, $complemento->Percepciones));
        }

        if ($complemento->Deducciones !== null) {
            $root->appendChild($this->crearDeducciones($doc, $complemento->Deducciones));
        }

        if (!empty($complemento->OtrosPagos)) {
            $otrosPagosNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':OtrosPagos');
            foreach ($complemento->OtrosPagos as $otroPago) {
                $otrosPagosNode->appendChild($this->crearOtroPago($doc, $otroPago));
            }
            $root->appendChild($otrosPagosNode);
        }

        if (!empty($complemento->Incapacidades)) {
            $incapacidadesNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Incapacidades');
            foreach ($complemento->Incapacidades as $incapacidad) {
                $incapacidadesNode->appendChild($this->crearIncapacidad($doc, $incapacidad));
            }
            $root->appendChild($incapacidadesNode);
        }

        return $root;
    }

    // ---------- Emisor ----------

    private function crearEmisor(\DOMDocument $doc, $emisor): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Emisor');

        $this->setAttr($node, 'Curp', $emisor->Curp);
        $this->setAttr($node, 'RegistroPatronal', $emisor->RegistroPatronal);
        $this->setAttr($node, 'RfcPatronOrigen', $emisor->RfcPatronOrigen);

        if ($emisor->EntidadSNCF !== null) {
            $entidadNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':EntidadSNCF');
            $this->setRequiredAttr($entidadNode, 'OrigenRecurso', $emisor->EntidadSNCF->OrigenRecurso);
            $this->setAttr($entidadNode, 'MontoRecursoPropio', $this->formatDecimal($emisor->EntidadSNCF->MontoRecursoPropio));
            $node->appendChild($entidadNode);
        }

        return $node;
    }

    // ---------- Receptor ----------

    private function crearReceptor(\DOMDocument $doc, $receptor): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Receptor');

        $this->setRequiredAttr($node, 'Curp', $receptor->Curp);
        $this->setAttr($node, 'NumSeguridadSocial', $receptor->NumSeguridadSocial);
        $this->setAttr($node, 'FechaInicioRelLaboral', $receptor->FechaInicioRelLaboral);
        $this->setAttr($node, 'Antigüedad', $receptor->Antigüedad);
        $this->setRequiredAttr($node, 'TipoContrato', $receptor->TipoContrato);
        $this->setAttr($node, 'Sindicalizado', $receptor->Sindicalizado);
        $this->setAttr($node, 'TipoJornada', $receptor->TipoJornada);
        $this->setRequiredAttr($node, 'TipoRegimen', $receptor->TipoRegimen);
        $this->setRequiredAttr($node, 'NumEmpleado', $receptor->NumEmpleado);
        $this->setAttr($node, 'Departamento', $receptor->Departamento);
        $this->setAttr($node, 'Puesto', $receptor->Puesto);
        $this->setAttr($node, 'RiesgoPuesto', $receptor->RiesgoPuesto);
        $this->setRequiredAttr($node, 'PeriodicidadPago', $receptor->PeriodicidadPago);
        $this->setAttr($node, 'Banco', $receptor->Banco);
        $this->setAttr($node, 'CuentaBancaria', $receptor->CuentaBancaria);
        $this->setAttr($node, 'SalarioBaseCotApor', $this->formatDecimal($receptor->SalarioBaseCotApor));
        $this->setAttr($node, 'SalarioDiarioIntegrado', $this->formatDecimal($receptor->SalarioDiarioIntegrado));
        $this->setRequiredAttr($node, 'ClaveEntFed', $receptor->ClaveEntFed);

        foreach ($receptor->SubContratacion as $sub) {
            $node->appendChild($this->crearSubContratacion($doc, $sub));
        }

        return $node;
    }

    private function crearSubContratacion(\DOMDocument $doc, NominaReceptorSubContratacion $sub): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':SubContratacion');
        $this->setRequiredAttr($node, 'RfcLabora', $sub->RfcLabora);
        $this->setRequiredAttr($node, 'PorcentajeTiempo', $this->formatDecimal($sub->PorcentajeTiempo) ?? '');
        return $node;
    }

    // ---------- Percepciones ----------

    private function crearPercepciones(\DOMDocument $doc, $percepciones): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Percepciones');

        $this->setAttr($node, 'TotalSueldos', $this->formatDecimal($percepciones->TotalSueldos));
        $this->setAttr($node, 'TotalSeparacionIndemnizacion', $this->formatDecimal($percepciones->TotalSeparacionIndemnizacion));
        $this->setAttr($node, 'TotalJubilacionPensionRetiro', $this->formatDecimal($percepciones->TotalJubilacionPensionRetiro));
        $this->setRequiredAttr($node, 'TotalGravado', $this->formatDecimal($percepciones->TotalGravado) ?? '');
        $this->setRequiredAttr($node, 'TotalExento', $this->formatDecimal($percepciones->TotalExento) ?? '');

        foreach ($percepciones->Percepcion as $percepcion) {
            $node->appendChild($this->crearPercepcion($doc, $percepcion));
        }

        if ($percepciones->JubilacionPensionRetiro !== null) {
            $j = $percepciones->JubilacionPensionRetiro;
            $jNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':JubilacionPensionRetiro');
            $this->setAttr($jNode, 'TotalUnaExhibicion', $this->formatDecimal($j->TotalUnaExhibicion));
            $this->setAttr($jNode, 'TotalParcialidad', $this->formatDecimal($j->TotalParcialidad));
            $this->setAttr($jNode, 'MontoDiario', $this->formatDecimal($j->MontoDiario));
            $this->setRequiredAttr($jNode, 'IngresoAcumulable', $this->formatDecimal($j->IngresoAcumulable) ?? '');
            $this->setRequiredAttr($jNode, 'IngresoNoAcumulable', $this->formatDecimal($j->IngresoNoAcumulable) ?? '');
            $node->appendChild($jNode);
        }

        if ($percepciones->SeparacionIndemnizacion !== null) {
            $s = $percepciones->SeparacionIndemnizacion;
            $sNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':SeparacionIndemnizacion');
            $this->setRequiredAttr($sNode, 'TotalPagado', $this->formatDecimal($s->TotalPagado) ?? '');
            $this->setRequiredAttr($sNode, 'NumAñosServicio', (string) $s->NumAñosServicio);
            $this->setRequiredAttr($sNode, 'UltimoSueldoMensOrd', $this->formatDecimal($s->UltimoSueldoMensOrd) ?? '');
            $this->setRequiredAttr($sNode, 'IngresoAcumulable', $this->formatDecimal($s->IngresoAcumulable) ?? '');
            $this->setRequiredAttr($sNode, 'IngresoNoAcumulable', $this->formatDecimal($s->IngresoNoAcumulable) ?? '');
            $node->appendChild($sNode);
        }

        return $node;
    }

    private function crearPercepcion(\DOMDocument $doc, NominaPercepcion $percepcion): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Percepcion');

        $this->setRequiredAttr($node, 'TipoPercepcion', $percepcion->TipoPercepcion);
        $this->setRequiredAttr($node, 'Clave', $percepcion->Clave);
        $this->setRequiredAttr($node, 'Concepto', $percepcion->Concepto);
        $this->setRequiredAttr($node, 'ImporteGravado', $this->formatDecimal($percepcion->ImporteGravado) ?? '');
        $this->setRequiredAttr($node, 'ImporteExento', $this->formatDecimal($percepcion->ImporteExento) ?? '');

        foreach ($percepcion->HorasExtra as $horasExtra) {
            $node->appendChild($this->crearHorasExtra($doc, $horasExtra));
        }

        if ($percepcion->AccionesOTitulos !== null) {
            $accionesNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':AccionesOTitulos');
            $this->setRequiredAttr($accionesNode, 'ValorMercado', $this->formatDecimal($percepcion->AccionesOTitulos->ValorMercado) ?? '');
            $this->setRequiredAttr($accionesNode, 'PrecioAlOtorgarse', $this->formatDecimal($percepcion->AccionesOTitulos->PrecioAlOtorgarse) ?? '');
            $node->appendChild($accionesNode);
        }

        return $node;
    }

    private function crearHorasExtra(\DOMDocument $doc, $horasExtra): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':HorasExtra');
        $this->setRequiredAttr($node, 'Dias', (string) $horasExtra->Dias);
        $this->setRequiredAttr($node, 'TipoHoras', $horasExtra->TipoHoras);
        $this->setRequiredAttr($node, 'HorasExtra', (string) $horasExtra->HorasExtra);
        $this->setRequiredAttr($node, 'ImportePagado', $this->formatDecimal($horasExtra->ImportePagado) ?? '');
        return $node;
    }

    // ---------- Deducciones ----------

    private function crearDeducciones(\DOMDocument $doc, $deducciones): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Deducciones');

        $this->setAttr($node, 'TotalOtrasDeducciones', $this->formatDecimal($deducciones->TotalOtrasDeducciones));
        $this->setAttr($node, 'TotalImpuestosRetenidos', $this->formatDecimal($deducciones->TotalImpuestosRetenidos));

        foreach ($deducciones->Deduccion as $deduccion) {
            $node->appendChild($this->crearDeduccion($doc, $deduccion));
        }

        return $node;
    }

    private function crearDeduccion(\DOMDocument $doc, NominaDeduccion $deduccion): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Deduccion');
        $this->setRequiredAttr($node, 'TipoDeduccion', $deduccion->TipoDeduccion);
        $this->setRequiredAttr($node, 'Clave', $deduccion->Clave);
        $this->setRequiredAttr($node, 'Concepto', $deduccion->Concepto);
        $this->setRequiredAttr($node, 'Importe', $this->formatDecimal($deduccion->Importe) ?? '');
        return $node;
    }

    // ---------- OtroPago ----------

    private function crearOtroPago(\DOMDocument $doc, NominaOtroPago $otroPago): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':OtroPago');

        $this->setRequiredAttr($node, 'TipoOtroPago', $otroPago->TipoOtroPago);
        $this->setRequiredAttr($node, 'Clave', $otroPago->Clave);
        $this->setRequiredAttr($node, 'Concepto', $otroPago->Concepto);
        $this->setRequiredAttr($node, 'Importe', $this->formatDecimal($otroPago->Importe) ?? '');

        if ($otroPago->SubsidioAlEmpleo !== null) {
            $subsidioNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':SubsidioAlEmpleo');
            $this->setRequiredAttr($subsidioNode, 'SubsidioCausado', $this->formatDecimal($otroPago->SubsidioAlEmpleo->SubsidioCausado) ?? '');
            $node->appendChild($subsidioNode);
        }

        if ($otroPago->CompensacionSaldosAFavor !== null) {
            $c = $otroPago->CompensacionSaldosAFavor;
            $compensacionNode = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':CompensacionSaldosAFavor');
            $this->setRequiredAttr($compensacionNode, 'SaldoAFavor', $this->formatDecimal($c->SaldoAFavor) ?? '');
            $this->setRequiredAttr($compensacionNode, 'Año', (string) $c->Año);
            $this->setRequiredAttr($compensacionNode, 'RemanenteSalFav', $this->formatDecimal($c->RemanenteSalFav) ?? '');
            $node->appendChild($compensacionNode);
        }

        return $node;
    }

    // ---------- Incapacidad ----------

    private function crearIncapacidad(\DOMDocument $doc, NominaIncapacidad $incapacidad): \DOMElement
    {
        $node = $doc->createElementNS(self::NS_URI, self::NS_PREFIX . ':Incapacidad');
        $this->setRequiredAttr($node, 'DiasIncapacidad', (string) $incapacidad->DiasIncapacidad);
        $this->setRequiredAttr($node, 'TipoIncapacidad', $incapacidad->TipoIncapacidad);
        $this->setAttr($node, 'ImporteMonetario', $this->formatDecimal($incapacidad->ImporteMonetario));
        return $node;
    }
}