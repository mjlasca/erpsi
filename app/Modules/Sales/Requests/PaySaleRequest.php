<?php

namespace App\Modules\Sales\Requests;

use App\Modules\Payments\Requests\PayDocumentRequest;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;

class PaySaleRequest extends PayDocumentRequest
{
    protected function document(): Sale|Purchase|null
    {
        $sale = $this->route('sale');

        return $sale instanceof Sale ? $sale : null;
    }
}
