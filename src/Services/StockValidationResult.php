<?php

namespace App\Services;

class StockValidationResult
{
    private function __construct(
        public readonly bool   $valido,
        public readonly string $motivo,      // 'ok' | 'sin_datos' | 'sin_stock' | 'excedido'
        public readonly ?int   $stockTotal,  // stock total del artículo
        public readonly ?int   $disponible,  // cuántas unidades se pueden agregar aún
    ) {}

    public static function ok(int $stockTotal): self
    {
        return new self(true, 'ok', $stockTotal, null);
    }

    public static function sinDatos(): self
    {
        return new self(true, 'sin_datos', null, null);
    }

    public static function sinStock(): self
    {
        return new self(false, 'sin_stock', 0, 0);
    }

    public static function excedido(int $stockTotal, int $disponible): self
    {
        return new self(false, 'excedido', $stockTotal, $disponible);
    }

    public function getMensaje(): string
    {
        return match ($this->motivo) {
            'sin_stock'  => 'Este artículo no tiene stock disponible.',
            'excedido'   => "Stock insuficiente. Podés agregar hasta {$this->disponible} unidad(es) más (stock disponible: {$this->stockTotal}).",
            default      => '',
        };
    }
}
