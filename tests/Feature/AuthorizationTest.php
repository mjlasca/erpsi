<?php

use App\Models\User;
use App\Modules\Sales\Models\Sale;

it('allows users with the required permission to access catalog pages', function () {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user)
        ->get(route('catalog.products.index'))
        ->assertOk();
});

it('denies users without the required permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('catalog.products.index'))
        ->assertForbidden();
});

it('denies access to custom document actions without permission', function () {
    $user = User::factory()->create();
    $sale = Sale::factory()->create();

    $this->actingAs($user)
        ->post(route('sales.sales.confirm', $sale))
        ->assertForbidden();
});
