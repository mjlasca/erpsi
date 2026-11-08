<?php

namespace App\Modules\Inventory\Enums;

enum MovementType: string
{
    case Purchase = 'PURCHASE';
    case Sale = 'SALE';
    case Return = 'RETURN';
    case Adjustment = 'ADJUSTMENT';
    case Transfer = 'TRANSFER';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Compra',
            self::Sale => 'Venta',
            self::Return => 'Devolución',
            self::Adjustment => 'Ajuste',
            self::Transfer => 'Transferencia',
        };
    }

    public function isManual(): bool
    {
        return in_array($this, [self::Return, self::Adjustment, self::Transfer], true);
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
