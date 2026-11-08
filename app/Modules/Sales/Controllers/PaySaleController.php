<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Actions\PaySale;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Requests\PaySaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PaySaleController extends Controller
{
    public function __invoke(
        PaySaleRequest $request,
        Sale $sale,
        PaySale $paySale,
    ): RedirectResponse {
        $this->authorize('pay', $sale);

        try {
            $paySale->execute(
                $sale,
                $request->validated()['payments'],
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
            ->with('success', 'Venta pagada e inventario actualizado.');
    }
}
