<?php

namespace App\Modules\Payments\Enums;

enum PaymentType: string
{
    case CustomerPayment = 'CUSTOMER_PAYMENT';
    case SupplierPayment = 'SUPPLIER_PAYMENT';

    public function label(): string
    {
        return match ($this) {
            self::CustomerPayment => 'Cobro a cliente',
            self::SupplierPayment => 'Pago a proveedor',
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
