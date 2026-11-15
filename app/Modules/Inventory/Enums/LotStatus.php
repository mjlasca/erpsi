<?php

namespace App\Modules\Inventory\Enums;

enum LotStatus: string
{
    case Available = 'AVAILABLE';
    case Reserved = 'RESERVED';
    case Partial = 'PARTIAL';
    case Sold = 'SOLD';
    case Cancelled = 'CANCELLED';
    case Quarantine = 'QUARANTINE';

    public function label(): string
    {
        return match ($this) {
            self::Available => __('enums.lot_status.available'),
            self::Reserved => __('enums.lot_status.reserved'),
            self::Partial => __('enums.lot_status.partial'),
            self::Sold => __('enums.lot_status.sold'),
            self::Cancelled => __('enums.lot_status.cancelled'),
            self::Quarantine => __('enums.lot_status.quarantine'),
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
