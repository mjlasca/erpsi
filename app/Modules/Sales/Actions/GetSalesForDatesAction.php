<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

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

        return DB::transaction(function () use ($startDate, $endDate): Collection {
            $sales = Sale::query()
                ->whereBetween('created_at', [
                    $startDate,
                    $endDate,
                ])
                ->get();

            return $sales;
        });
    }
}
