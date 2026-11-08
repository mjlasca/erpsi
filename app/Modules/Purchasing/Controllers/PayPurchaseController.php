<?php

namespace App\Modules\Purchasing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchasing\Actions\PayPurchase;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Purchasing\Requests\PayPurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PayPurchaseController extends Controller
{
    public function __invoke(
        PayPurchaseRequest $request,
        Purchase $purchase,
        PayPurchase $payPurchase,
    ): RedirectResponse {
        $this->authorize('pay', $purchase);

        try {
            $payPurchase->execute(
                $purchase,
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
            ->route('purchasing.purchases.show', $purchase)
            ->with('success', 'Compra pagada e inventario actualizado.');
    }
}
