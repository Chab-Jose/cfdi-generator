<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte;

class CartaPorteMercancia
{
    public string $BienesTransp = '';
    public ?string $ClaveSTCC = null;
    public string $Descripcion = '';
    public float $Cantidad = 0.0;
    public string $ClaveUnidad = '';
    public ?string $Unidad = null;
    public ?string $Dimensiones = null;
    public ?string $MaterialPeligroso = null;
    public ?string $CveMaterialPeligroso = null;
    public ?string $Embalaje = null;
    public ?string $DescripEmbalaje = null;
    public ?string $SectorCOFEPRIS = null;
    public ?string $NombreIngredienteActivo = null;
    public ?string $NomQuimico = null;
    public ?string $DenominacionGenericaProd = null;
    public ?string $DenominacionDistintivaProd = null;
    public ?string $Fabricante = null;
    public ?string $FechaCaducidad = null;
    public ?string $LoteMedicamento = null;
    public ?string $FormaFarmaceutica = null;
    public ?string $CondicionesEspTransp = null;
    public ?string $RegistroSanitarioFolioAutorizacion = null;
    public ?string $PermisoImportacion = null;
    public ?string $FolioImpoVUCEM = null;
    public ?string $NumCAS = null;
    public ?string $RazonSocialEmpImp = null;
    public ?string $NumRegSanPlagCOFEPRIS = null;
    public ?string $DatosFabricante = null;
    public ?string $DatosFormulador = null;
    public ?string $DatosMaquilador = null;
    public ?string $UsoAutorizado = null;
    public float $PesoEnKg = 0.0;
    public ?float $ValorMercancia = null;
    public ?string $Moneda = null;
    public ?string $FraccionArancelaria = null;
    public ?string $UUIDComercioExt = null;
    public ?string $TipoMateria = null;
    public ?string $DescripcionMateria = null;

    /** @var CartaPorteDocumentacionAduanera[] */
    public array $DocumentacionAduanera = [];

    /** @var CartaPorteGuiaIdentificacion[] */
    public array $GuiasIdentificacion = [];

    /** @var CartaPorteCantidadTransporta[] */
    public array $CantidadTransporta = [];

    public ?CartaPorteDetalleMercancia $DetalleMercancia = null;

    public function addDocumentacionAduanera(CartaPorteDocumentacionAduanera $doc): self
    {
        $this->DocumentacionAduanera[] = $doc;
        return $this;
    }

    public function addGuiaIdentificacion(CartaPorteGuiaIdentificacion $guia): self
    {
        $this->GuiasIdentificacion[] = $guia;
        return $this;
    }

    public function addCantidadTransporta(CartaPorteCantidadTransporta $cantidad): self
    {
        $this->CantidadTransporta[] = $cantidad;
        return $this;
    }
}