<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

function validUserPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nuevo Usuario',
        'email' => 'nuevo@example.com',
        'password' => 'secret-pass-123',
        'password_confirmation' => 'secret-pass-123',
    ], $overrides);
}

it('creates a user and never exposes the password', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson(route('api.v1.users.store'), validUserPayload());

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Nuevo Usuario')
        ->assertJsonPath('data.email', 'nuevo@example.com')
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'created_at', 'updated_at']])
        ->assertJsonMissingPath('data.password');

    $created = User::where('email', 'nuevo@example.com')->firstOrFail();

    expect(Hash::check('secret-pass-123', $created->password))->toBeTrue()
        ->and($created->password)->not->toBe('secret-pass-123');
});

it('requires authentication to create a user', function () {
    $this->postJson(route('api.v1.users.store'), validUserPayload())->assertUnauthorized();

    $this->assertDatabaseMissing('users', ['email' => 'nuevo@example.com']);
});

it('rejects invalid data when creating a user', function (array $overrides, string $invalidField) {
    Sanctum::actingAs(User::factory()->create());
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson(route('api.v1.users.store'), validUserPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalidField);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'missing email' => [['email' => ''], 'email'],
    'malformed email' => [['email' => 'not-an-email'], 'email'],
    'duplicated email' => [['email' => 'taken@example.com'], 'email'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'unconfirmed password' => [['password_confirmation' => 'different'], 'password'],
]);
