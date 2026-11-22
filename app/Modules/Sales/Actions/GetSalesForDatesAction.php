<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\CarbonImmutable;

class GetSalesForDatesAction
{
    public function __construct() {}

    /**
     * @param string $startDate
     * @param string $endDate
     */
    public function execute(
        string $startDate,
        string $endDate
    ): Collection {

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();

        return Sale::query()
            ->whereBetween('sale_date', [$start, $end])
            ->get();
    }
}
