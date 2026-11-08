<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\ConfirmSale;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Requests\ConfirmSaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ConfirmSaleController extends Controller
{
    public function __invoke(
        ConfirmSaleRequest $request,
        Sale $sale,
        ConfirmSale $confirmSale,
    ): RedirectResponse {
        $this->authorize('confirm', $sale);

        try {
            $confirmSale->execute(
                $sale,
                $request->validated()['lot_allocations'] ?? [],
                $request->user(),
            );
        } catch (ValidationException $exception) {
            return back()->with(
                'error',
                $exception->validator->errors()->first(),
            );
        }

        return redirect()
            ->route('sales.sales.show', $sale)
            ->with('success', 'Venta confirmada e inventario actualizado.');
    }
}
