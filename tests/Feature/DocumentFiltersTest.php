<?php

use App\Models\User;
use App\Modules\Purchasing\Models\Purchase;
use App\Modules\Sales\Models\Sale;
use Inertia\Testing\AssertableInertia as Assert;

it('filters purchases by date range', function () {
    $user = User::factory()->administrator()->create();

    Purchase::factory()->create(['purchase_date' => '2026-01-05']);
    $matching = Purchase::factory()->create(['purchase_date' => '2026-01-15']);
    Purchase::factory()->create(['purchase_date' => '2026-01-25']);

    $this->actingAs($user)
        ->get(route('purchasing.purchases.index', [
            'from' => '2026-01-10',
            'to' => '2026-01-20',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('purchases.data', 1)
            ->where('purchases.data.0.id', $matching->id));
});

it('filters sales by date range', function () {
    $user = User::factory()->administrator()->create();

    Sale::factory()->create(['sale_date' => '2026-02-01']);
    $matching = Sale::factory()->create(['sale_date' => '2026-02-10']);
    Sale::factory()->create(['sale_date' => '2026-02-20']);

    $this->actingAs($user)
        ->get(route('sales.sales.index', [
            'from' => '2026-02-05',
            'to' => '2026-02-15',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sales.data', 1)
            ->where('sales.data.0.id', $matching->id));
});
