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
            self::Quantity => __('enums.unit_type.quantity'),
            self::Area => __('enums.unit_type.area'),
            self::Length => __('enums.unit_type.length'),
            self::Weight => __('enums.unit_type.weight'),
            self::Volume => __('enums.unit_type.volume'),
            self::Other => __('enums.unit_type.other'),
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
