<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists users with pagination and without sensitive fields', function () {
    Sanctum::actingAs(User::factory()->create());
    User::factory()->count(19)->create();

    $this->getJson(route('api.v1.users.index'))
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 20)
        ->assertJsonStructure(['data' => [['id', 'name', 'email']], 'links', 'meta'])
        ->assertJsonMissingPath('data.0.password');
});

it('shows a single user', function () {
    Sanctum::actingAs(User::factory()->create());
    $other = User::factory()->create();

    $this->getJson(route('api.v1.users.show', $other))
        ->assertOk()
        ->assertJsonPath('data.id', $other->id)
        ->assertJsonPath('data.email', $other->email);
});

it('returns 404 for a missing user', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.users.show', 999999))->assertNotFound();
});

it('requires authentication to list or show users', function () {
    $user = User::factory()->create();

    $this->getJson(route('api.v1.users.index'))->assertUnauthorized();
    $this->getJson(route('api.v1.users.show', $user))->assertUnauthorized();
});
