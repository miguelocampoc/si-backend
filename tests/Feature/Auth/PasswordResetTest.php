<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('sends a reset link to an existing user', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson(route('api.v1.password.forgot'), ['email' => $user->email])
        ->assertOk()
        ->assertJsonStructure(['message']);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal whether an email is registered', function () {
    Notification::fake();

    $this->postJson(route('api.v1.password.forgot'), ['email' => 'ghost@example.com'])
        ->assertOk();

    Notification::assertNothingSent();
});

it('validates the forgot password payload', function () {
    $this->postJson(route('api.v1.password.forgot'), ['email' => 'invalid'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('completes the full recovery flow and revokes existing tokens', function () {
    Notification::fake();
    $user = User::factory()->create();
    $user->createToken('stale');

    $this->postJson(route('api.v1.password.forgot'), ['email' => $user->email])->assertOk();

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->postJson(route('api.v1.password.reset'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertOk();

    expect(Hash::check('new-password-123', $user->refresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);

    $this->postJson(route('api.v1.login'), [
        'email' => $user->email,
        'password' => 'new-password-123',
    ])->assertOk();
});

it('rejects an invalid reset token', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.v1.password.reset'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('validates the reset password payload', function (array $overrides, string $invalidField) {
    $payload = array_merge([
        'token' => 'any-token',
        'email' => 'ana@example.com',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ], $overrides);

    $this->postJson(route('api.v1.password.reset'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalidField);
})->with([
    'missing token' => [['token' => ''], 'token'],
    'password too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'password not confirmed' => [['password_confirmation' => 'different'], 'password'],
]);
