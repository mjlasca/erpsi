<?php

namespace App\Modules\Catalog\Enums;

enum UnitType: string
{
    case Quantity = 'QUANTITY';
    case Area = 'AREA';
    case Length = 'LENGTH';
    case Weight = 'WEIGHT';
    case Volume = 'VOLUME';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Quantity => 'Cantidad',
            self::Area => 'Área',
            self::Length => 'Longitud',
            self::Weight => 'Peso',
            self::Volume => 'Volumen',
            self::Other => 'Otro',
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
