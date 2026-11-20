<?php

use App\Models\User;

it('revokes only the current token on logout', function () {
    $user = User::factory()->create();
    $currentToken = $user->createToken('current')->plainTextToken;
    $user->createToken('other');

    $this->withToken($currentToken)
        ->postJson(route('api.v1.logout'))
        ->assertNoContent();

    expect($user->tokens()->count())->toBe(1)
        ->and($user->tokens()->first()->name)->toBe('other');
});

it('requires authentication to log out', function () {
    $this->postJson(route('api.v1.logout'))->assertUnauthorized();
});
