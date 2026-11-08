<?php

namespace App\Modules\Payments\Requests;

use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use App\Support\Decimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class PayDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    abstract protected function document(): Sale|Purchase|null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
            'payments.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $document = $this->document();

                if ($document === null) {
                    return;
                }

                $amounts = collect((array) $this->input('payments', []))
                    ->pluck('amount')
                    ->filter(fn ($amount): bool => is_numeric($amount))
                    ->map(fn ($amount): string => (string) $amount);

                $total = Decimal::sum($amounts, 2);
                $expected = Decimal::round2((string) $document->total);

                if (Decimal::compare($total, $expected, 2) !== 0) {
                    $validator->errors()->add(
                        'payments',
                        'El total de los pagos debe coincidir con el total del documento.',
                    );
                }
            },
        ];
    }
}
