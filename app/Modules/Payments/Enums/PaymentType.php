<?php

namespace App\Modules\Payments\Enums;

enum PaymentType: string
{
    case CustomerPayment = 'CUSTOMER_PAYMENT';
    case SupplierPayment = 'SUPPLIER_PAYMENT';
    case CustomerRefund = 'CUSTOMER_REFUND';
    case SupplierRefund = 'SUPPLIER_REFUND';

    public function label(): string
    {
        return match ($this) {
            self::CustomerPayment => __('enums.payment_type.customer_payment'),
            self::SupplierPayment => __('enums.payment_type.supplier_payment'),
            self::CustomerRefund => __('enums.payment_type.customer_refund'),
            self::SupplierRefund => __('enums.payment_type.supplier_refund'),
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
