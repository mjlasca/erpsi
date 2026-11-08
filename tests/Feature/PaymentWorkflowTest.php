<?php

use App\Modules\Payments\Actions\AllocatePayment;
use App\Modules\Payments\Actions\RecordPayment;
use App\Modules\Payments\Actions\RemovePaymentAllocation;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Sale;
use Illuminate\Validation\ValidationException;

it('records a customer payment without affecting inventory', function () {
    $sale = Sale::factory()->confirmed()->create([
        'total' => '100.00',
        'balance' => '100.00',
    ]);

    $payment = app(RecordPayment::class)->execute([
        'payment_type' => PaymentType::CustomerPayment->value,
        'customer_id' => $sale->customer_id,
        'payment_date' => now()->toDateString(),
        'amount' => '40.00',
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    expect($payment->amount)->toBe('40.00');
    expect($payment->payment_type)->toBe(PaymentType::CustomerPayment);
    expect($sale->refresh()->paid_amount)->toBe('0.00');
});

it('allocates a payment and updates the sale balance', function () {
    $sale = Sale::factory()->confirmed()->create([
        'total' => '100.00',
        'paid_amount' => '0.00',
        'balance' => '100.00',
    ]);

    $payment = app(RecordPayment::class)->execute([
        'payment_type' => PaymentType::CustomerPayment->value,
        'customer_id' => $sale->customer_id,
        'payment_date' => now()->toDateString(),
        'amount' => '100.00',
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    $allocation = app(AllocatePayment::class)->execute($payment, $sale, '40.00');

    expect($allocation->amount)->toBe('40.00');
    expect($sale->refresh()->paid_amount)->toBe('40.00');
    expect($sale->balance)->toBe('60.00');
    expect($sale->status)->toBe(SaleStatus::Partial);

    app(AllocatePayment::class)->execute($payment->refresh(), $sale->refresh(), '60.00');

    expect($sale->refresh()->paid_amount)->toBe('100.00');
    expect($sale->balance)->toBe('0.00');
    expect($sale->status)->toBe(SaleStatus::Paid);

    app(RemovePaymentAllocation::class)->execute($allocation);

    expect($sale->refresh()->paid_amount)->toBe('60.00');
    expect($sale->balance)->toBe('40.00');
    expect($sale->status)->toBe(SaleStatus::Partial);
});

it('rejects applying a payment to another customer sale', function () {
    $sale = Sale::factory()->confirmed()->create([
        'total' => '100.00',
        'paid_amount' => '0.00',
        'balance' => '100.00',
    ]);
    $otherCustomer = Customer::factory()->create();

    $payment = app(RecordPayment::class)->execute([
        'payment_type' => PaymentType::CustomerPayment->value,
        'customer_id' => $otherCustomer->id,
        'payment_date' => now()->toDateString(),
        'amount' => '100.00',
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    expect(fn () => app(AllocatePayment::class)->execute($payment, $sale, '10.00'))
        ->toThrow(ValidationException::class);
});

it('rejects allocating more than the payment or document balance', function () {
    $sale = Sale::factory()->confirmed()->create([
        'total' => '100.00',
        'paid_amount' => '0.00',
        'balance' => '100.00',
    ]);

    $payment = app(RecordPayment::class)->execute([
        'payment_type' => PaymentType::CustomerPayment->value,
        'customer_id' => $sale->customer_id,
        'payment_date' => now()->toDateString(),
        'amount' => '50.00',
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    expect(fn () => app(AllocatePayment::class)->execute($payment, $sale, '51.00'))
        ->toThrow(ValidationException::class);

    expect(fn () => app(AllocatePayment::class)->execute($payment, $sale, '100.00'))
        ->toThrow(ValidationException::class);
});
