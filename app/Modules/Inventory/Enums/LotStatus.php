<?php

namespace App\Modules\Inventory\Enums;

enum LotStatus: string
{
    case Available = 'AVAILABLE';
    case Reserved = 'RESERVED';
    case Partial = 'PARTIAL';
    case Sold = 'SOLD';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Reserved => 'Reservado',
            self::Partial => 'Parcial',
            self::Sold => 'Vendido',
            self::Cancelled => 'Anulado',
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
