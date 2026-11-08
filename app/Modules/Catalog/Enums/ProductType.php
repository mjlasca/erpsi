<?php

namespace App\Modules\Catalog\Enums;

enum ProductType: string
{
    case Goods = 'GOODS';
    case Service = 'SERVICE';
    case Bundle = 'BUNDLE';

    public function label(): string
    {
        return match ($this) {
            self::Goods => 'Producto',
            self::Service => 'Servicio',
            self::Bundle => 'Paquete',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
