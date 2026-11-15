<?php

namespace App\Modules\Purchasing\Enums;

enum PurchaseStatus: string
{
    case Draft = 'DRAFT';
    case Received = 'RECEIVED';
    case Paid = 'PAID';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('enums.purchase_status.draft'),
            self::Received => __('enums.purchase_status.received'),
            self::Paid => __('enums.purchase_status.paid'),
            self::Cancelled => __('enums.purchase_status.cancelled'),
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
