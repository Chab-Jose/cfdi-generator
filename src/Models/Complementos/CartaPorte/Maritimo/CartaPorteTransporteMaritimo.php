<?php

declare(strict_types=1);

namespace ChabJose\CfdiGenerator\Models\Complementos\CartaPorte\Maritimo;

class CartaPorteTransporteMaritimo
{
    public ?string $PermSCT = null;
    public ?string $NumPermisoSCT = null;
    public ?string $NombreAseg = null;
    public ?string $NumPolizaSeguro = null;
    public string $TipoEmbarcacion = '';
    public string $Matricula = '';
    public string $NumeroOMI = '';
    public ?string $AnioEmbarcacion = null;
    public ?string $NombreEmbarc = null;
    public string $NacionalidadEmbarc = '';
    public float $UnidadesDeArqBruto = 0.0;
    public string $TipoCarga = '';
    public ?float $Eslora = null;
    public ?float $Manga = null;
    public ?float $Calado = null;
    public ?float $Puntal = null;
    public ?string $LineaNaviera = null;
    public string $NombreAgenteNaviero = '';
    public string $NumAutorizacionNaviero = '';
    public ?string $NumViaje = null;
    public ?string $NumConocEmbarc = null;
    public ?string $PermisoTempNavegacion = null;

    /** @var CartaPorteContenedorMaritimo[] */
    public array $Contenedor = [];

    public function addContenedor(CartaPorteContenedorMaritimo $contenedor): self
    {
        $this->Contenedor[] = $contenedor;
        return $this;
    }
}