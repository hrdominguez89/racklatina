<?php

namespace App\Services;

use App\Repository\StockAdvisorRepository;

class StockAdvisorService
{
    public function __construct(
        private StockAdvisorRepository $stockRepo,
    ) {}

    /**
     * Valida si se puede agregar $cantidadSolicitada unidades de un artículo
     * considerando que ya hay $cantidadActual unidades en el proyecto.
     *
     * Retorna true si hay stock suficiente o si el artículo no está en stock_advisor.
     * Retorna false si el stock disponible es menor a la cantidad total resultante.
     *
     * @param string $codigoCalipso
     * @param int    $cantidadSolicitada  Unidades que se quieren agregar
     * @param int    $cantidadActual      Unidades ya existentes en el proyecto para ese artículo
     */
    public function validarStock(string $codigoCalipso, int $cantidadSolicitada, int $cantidadActual = 0): StockValidationResult
    {
        $stockDisponible = $this->stockRepo->getStock($codigoCalipso);

        // Si el artículo no está en stock_advisor, se trata como sin stock
        if ($stockDisponible === null) {
            return StockValidationResult::sinStock();
        }

        $totalResultante = $cantidadActual + $cantidadSolicitada;
        $stockEntero = (int)floor($stockDisponible);

        if ($stockEntero <= 0) {
            return StockValidationResult::sinStock();
        }

        if ($totalResultante > $stockEntero) {
            $disponibleParaAgregar = max(0, $stockEntero - $cantidadActual);
            return StockValidationResult::excedido($stockEntero, $disponibleParaAgregar);
        }

        return StockValidationResult::ok($stockEntero);
    }

    /**
     * Valida si se puede actualizar la cantidad de un item ya existente en el proyecto.
     *
     * @param string $codigoCalipso
     * @param int    $cantidadNueva  Nueva cantidad total deseada para ese item
     */
    public function validarCantidadUpdate(string $codigoCalipso, int $cantidadNueva): StockValidationResult
    {
        return $this->validarStock($codigoCalipso, $cantidadNueva, 0);
    }

    /**
     * Retorna el stock disponible para un artículo (null si no está en stock_advisor).
     */
    public function getStock(string $codigoCalipso): ?float
    {
        return $this->stockRepo->getStock($codigoCalipso);
    }

    /**
     * Retorna el mapa de stock para un conjunto de artículos en una sola query.
     *
     * @param string[] $codigos
     * @return array<string, float>
     */
    public function getStockMap(array $codigos): array
    {
        return $this->stockRepo->getStockMap($codigos);
    }
}
