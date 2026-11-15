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
            self::Cash => __('enums.payment_method.cash'),
            self::Card => __('enums.payment_method.card'),
            self::BankTransfer => __('enums.payment_method.bank_transfer'),
            self::Check => __('enums.payment_method.check'),
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
