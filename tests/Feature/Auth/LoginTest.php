<?php

use App\Models\User;

it('logs in with valid credentials and returns a bearer token', function () {
    $user = User::factory()->create(['email' => 'ana@example.com']);

    $response = $this->postJson(route('api.v1.login'), [
        'email' => 'ana@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', 'ana@example.com')
        ->assertJsonMissingPath('data.user.password');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    expect($user->tokens()->count())->toBe(1);
});

it('issued token authenticates protected requests', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $token = $this->postJson(route('api.v1.login'), [
        'email' => 'ana@example.com',
        'password' => 'password',
    ])->json('data.token');

    $this->withToken($token)
        ->getJson(route('api.v1.users.index'))
        ->assertOk();
});

it('rejects invalid credentials', function (string $email, string $password) {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.login'), [
        'email' => $email,
        'password' => $password,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(User::first()->tokens()->count())->toBe(0);
})->with([
    'wrong password' => ['ana@example.com', 'incorrect'],
    'unknown email' => ['nobody@example.com', 'password'],
]);

it('validates the login payload', function (array $payload, string $invalidField) {
    $this->postJson(route('api.v1.login'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalidField);
})->with([
    'missing email' => [['password' => 'password'], 'email'],
    'malformed email' => [['email' => 'not-an-email', 'password' => 'password'], 'email'],
    'missing password' => [['email' => 'ana@example.com'], 'password'],
]);
