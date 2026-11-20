<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('lets a user update their own account', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson(route('api.v1.users.update', $user), [
        'name' => 'Nombre Editado',
        'email' => 'editado@example.com',
    ])->assertOk()
        ->assertJsonPath('data.name', 'Nombre Editado')
        ->assertJsonPath('data.email', 'editado@example.com');

    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'editado@example.com']);
});

it('hashes the password when it is updated', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson(route('api.v1.users.update', $user), [
        'password' => 'brand-new-pass-1',
        'password_confirmation' => 'brand-new-pass-1',
    ])->assertOk();

    expect(Hash::check('brand-new-pass-1', $user->refresh()->password))->toBeTrue();
});

it('allows keeping the same email on update', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson(route('api.v1.users.update', $user), ['email' => $user->email])->assertOk();
});

it('forbids updating another user', function () {
    Sanctum::actingAs(User::factory()->create());
    $other = User::factory()->create(['name' => 'Original']);

    $this->putJson(route('api.v1.users.update', $other), ['name' => 'Hackeado'])->assertForbidden();

    expect($other->refresh()->name)->toBe('Original');
});

it('rejects invalid data when updating', function (array $payload, string $invalidField) {
    $user = User::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);
    Sanctum::actingAs($user);

    $this->putJson(route('api.v1.users.update', $user), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalidField);
})->with([
    'empty name' => [['name' => ''], 'name'],
    'duplicated email' => [['email' => 'taken@example.com'], 'email'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
]);

it('requires authentication to update a user', function () {
    $user = User::factory()->create();

    $this->putJson(route('api.v1.users.update', $user), ['name' => 'X'])->assertUnauthorized();
});
