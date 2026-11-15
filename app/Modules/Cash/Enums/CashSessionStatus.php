<?php

namespace App\Modules\Cash\Enums;

enum CashSessionStatus: string
{
    case Open = 'OPEN';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('enums.cash_session.open'),
            self::Closed => __('enums.cash_session.closed'),
        };
    }
}
