<?php

namespace App\Modules\Inventory\Enums;

enum MovementType: string
{
    case Purchase = 'PURCHASE';
    case Sale = 'SALE';
    case Return = 'RETURN';
    case Adjustment = 'ADJUSTMENT';
    case Transfer = 'TRANSFER';
    case Quarantine = 'QUARANTINE';
    case Damage = 'DAMAGE';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => __('enums.movement_type.purchase'),
            self::Sale => __('enums.movement_type.sale'),
            self::Return => __('enums.movement_type.return'),
            self::Adjustment => __('enums.movement_type.adjustment'),
            self::Transfer => __('enums.movement_type.transfer'),
            self::Quarantine => __('enums.movement_type.quarantine'),
            self::Damage => __('enums.movement_type.damage'),
        };
    }

    public function isManual(): bool
    {
        return in_array($this, [self::Return, self::Adjustment, self::Transfer, self::Quarantine, self::Damage], true);
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

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function manualOptions(): array
    {
        return array_values(array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            array_filter(self::cases(), fn (self $case): bool => $case->isManual()),
        ));
    }
}
