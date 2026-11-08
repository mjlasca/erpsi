<?php

namespace App\Modules\Payments\Enums;

enum PaymentMethod: string
{
    case Cash = 'CASH';
    case Card = 'CARD';
    case BankTransfer = 'BANK_TRANSFER';
    case Check = 'CHECK';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Card => 'Tarjeta',
            self::BankTransfer => 'Transferencia bancaria',
            self::Check => 'Cheque',
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
