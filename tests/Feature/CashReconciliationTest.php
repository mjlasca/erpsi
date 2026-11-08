<?php

use App\Models\User;
use App\Modules\Cash\Models\CashReconciliation;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentType;
use App\Modules\Payments\Models\Payment;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Sales\Models\Customer;
use Inertia\Testing\AssertableInertia as Assert;

it('summarizes cash and total flow for a day', function () {
    $user = User::factory()->administrator()->create();
    $customer = Customer::factory()->create();
    $supplier = Supplier::factory()->create();
    $date = now()->toDateString();

    Payment::factory()->create([
        'payment_type' => PaymentType::CustomerPayment,
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Cash,
        'payment_date' => $date,
        'amount' => '100.00',
    ]);

    Payment::factory()->create([
        'payment_type' => PaymentType::CustomerPayment,
        'customer_id' => $customer->id,
        'payment_method' => PaymentMethod::Card,
        'payment_date' => $date,
        'amount' => '50.00',
    ]);

    Payment::factory()->create([
        'payment_type' => PaymentType::SupplierPayment,
        'supplier_id' => $supplier->id,
        'customer_id' => null,
        'payment_method' => PaymentMethod::Cash,
        'payment_date' => $date,
        'amount' => '30.00',
    ]);

    $this->actingAs($user)
        ->get(route('cash.reconciliations.index', [
            'from' => $date,
            'to' => $date,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('summaries', 1)
            ->where('summaries.0.cash_in', '100.00')
            ->where('summaries.0.cash_out', '30.00')
            ->where('summaries.0.inflow', '150.00')
            ->where('summaries.0.outflow', '30.00')
            ->where('summaries.0.expected_cash', '70.00'));

    $this->actingAs($user)
        ->get(route('cash.reconciliations.show', $date))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.cash_in', '100.00')
            ->where('summary.cash_out', '30.00')
            ->where('summary.expected_cash', '70.00')
            ->has('payments', 3));

    $this->actingAs($user)
        ->put(route('cash.reconciliations.update', $date), [
            'opening_balance' => '20.00',
            'counted_cash' => '80.00',
            'notes' => 'Cierre de prueba',
        ])
        ->assertRedirect(route('cash.reconciliations.show', $date));

    $reconciliation = CashReconciliation::query()->whereDate('date', $date)->firstOrFail();

    expect($reconciliation->opening_balance)->toBe('20.00');
    expect($reconciliation->counted_cash)->toBe('80.00');

    $this->actingAs($user)
        ->get(route('cash.reconciliations.show', $date))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.expected_cash', '90.00')
            ->where('summary.counted_cash', '80.00')
            ->where('summary.difference', '-10.00'));
});

it('denies cash reconciliation access without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('cash.reconciliations.index'))
        ->assertForbidden();
});
