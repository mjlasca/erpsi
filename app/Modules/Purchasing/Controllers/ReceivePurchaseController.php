<?php

namespace App\Modules\Purchasing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Actions\ReceivePurchase;
use App\Modules\Purchasing\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReceivePurchaseController extends Controller
{
    public function __invoke(
        Request $request,
        Purchase $purchase,
        ReceivePurchase $receivePurchase,
    ): RedirectResponse {
        $this->authorize('receive', $purchase);

        $receivePurchase->execute($purchase, $request->user());

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Compra recibida e inventario actualizado.');
    }
}
