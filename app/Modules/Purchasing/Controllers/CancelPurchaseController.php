<?php

namespace App\Modules\Purchasing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Actions\CancelPurchase;
use App\Modules\Purchasing\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CancelPurchaseController extends Controller
{
    public function __invoke(
        Request $request,
        Purchase $purchase,
        CancelPurchase $cancelPurchase,
    ): RedirectResponse {
        $this->authorize('cancel', $purchase);

        $cancelPurchase->execute($purchase, $request->user());

        return redirect()
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Compra cancelada correctamente.');
    }
}
