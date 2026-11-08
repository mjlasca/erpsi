<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

it('returns validation messages in Spanish', function () {
    $this->post(route('login.store'), [])
        ->assertSessionHasErrors(['email' => 'El campo correo es obligatorio.']);
});

it('renders the login page', function () {
    $this->get(route('login.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login/Index'));
});

it('authenticates an active user', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'secret-password',
    ]);

    $this->post(route('login.store'), [
        'email' => 'admin@example.com',
        'password' => 'secret-password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'admin@example.com']);

    $this->from(route('login.index'))
        ->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login.index'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects inactive users', function () {
    User::factory()->inactive()->create([
        'email' => 'inactive@example.com',
        'password' => 'secret-password',
    ]);

    $this->post(route('login.store'), [
        'email' => 'inactive@example.com',
        'password' => 'secret-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login.index'));

    $this->assertGuest();
});

it('rate limits repeated failed login attempts', function () {
    User::factory()->create(['email' => 'rate-limited@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'email' => 'rate-limited@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), [
        'email' => 'rate-limited@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    expect(RateLimiter::tooManyAttempts('rate-limited@example.com|127.0.0.1', 5))
        ->toBeTrue();
});

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login.index'));
});

it('logs out users deactivated while authenticated', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login.index'));

    $this->assertGuest();
});
