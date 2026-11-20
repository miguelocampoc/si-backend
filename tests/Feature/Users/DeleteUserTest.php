<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lets a user delete their own account', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->deleteJson(route('api.v1.users.destroy', $user))->assertNoContent();

    $this->assertModelMissing($user);
});

it('lets an authenticated user delete another user', function () {
    Sanctum::actingAs(User::factory()->create());
    $other = User::factory()->create();

    $this->deleteJson(route('api.v1.users.destroy', $other))->assertNoContent();

    $this->assertModelMissing($other);
});

it('requires authentication to delete a user', function () {
    $user = User::factory()->create();

    $this->deleteJson(route('api.v1.users.destroy', $user))->assertUnauthorized();

    $this->assertModelExists($user);
});
