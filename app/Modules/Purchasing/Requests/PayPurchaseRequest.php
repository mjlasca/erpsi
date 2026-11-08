<?php

namespace App\Modules\Purchasing\Requests;

use App\Modules\Payments\Requests\PayDocumentRequest;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;

class PayPurchaseRequest extends PayDocumentRequest
{
    protected function document(): Sale|Purchase|null
    {
        $purchase = $this->route('purchase');

        return $purchase instanceof Purchase ? $purchase : null;
    }
}
