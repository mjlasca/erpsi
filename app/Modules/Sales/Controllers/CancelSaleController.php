<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\CancelSale;
use App\Modules\Sales\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CancelSaleController extends Controller
{
    public function __invoke(
        Request $request,
        Sale $sale,
        CancelSale $cancelSale,
    ): RedirectResponse {
        $this->authorize('cancel', $sale);

        $cancelSale->execute($sale, $request->user());

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Venta cancelada correctamente.');
    }
}
