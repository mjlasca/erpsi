<?php

namespace App\Modules\Sales\Enums;

enum ReturnDisposition: string
{
    case Restock = 'RESTOCK';
    case Quarantine = 'QUARANTINE';
    case Discard = 'DISCARD';

    public function label(): string
    {
        return match ($this) {
            self::Restock => __('enums.return_disposition.restock'),
            self::Quarantine => __('enums.return_disposition.quarantine'),
            self::Discard => __('enums.return_disposition.discard'),
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
