<?php

namespace App\Modules\Sales\Enums;

enum ReturnStatus: string
{
    case Draft = 'DRAFT';
    case Confirmed = 'CONFIRMED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('enums.return_status.draft'),
            self::Confirmed => __('enums.return_status.confirmed'),
            self::Cancelled => __('enums.return_status.cancelled'),
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
