<?php

namespace App\Modules\Sales\Enums;

enum SaleStatus: string
{
    case Draft = 'DRAFT';
    case Confirmed = 'CONFIRMED';
    case Partial = 'PARTIAL';
    case Paid = 'PAID';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('enums.sale_status.draft'),
            self::Confirmed => __('enums.sale_status.confirmed'),
            self::Partial => __('enums.sale_status.partial'),
            self::Paid => __('enums.sale_status.paid'),
            self::Cancelled => __('enums.sale_status.cancelled'),
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
