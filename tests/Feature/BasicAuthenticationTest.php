<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BasicAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_information(): void
    {
        $response = $this
            ->withSession(['url.intended' => '/favorites'])
            ->post('/register', [
                'name' => '新規ユーザー',
                'email' => 'new-user@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success', '会員登録が完了しました。');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => '新規ユーザー',
            'email' => 'new-user@example.com',
        ]);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_cannot_register_when_password_confirmation_does_not_match(): void
    {
        $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'new-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'new-user@example.com',
        ]);
    }

    public function test_registered_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success', 'ログインしました。');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_returns_to_intended_page_after_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this
            ->withSession(['url.intended' => '/favorites'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response
            ->assertRedirect('/favorites')
            ->assertSessionHas('success', 'ログインしました。');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_log_in_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ]);

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/logout');

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success', 'ログアウトしました。');

        $this->assertGuest();
    }

    public function test_unused_authentication_features_are_disabled(): void
    {
        $this->assertFalse(Route::has('password.request'));
        $this->assertFalse(Route::has('password.email'));
        $this->assertFalse(Route::has('password.reset'));
        $this->assertFalse(Route::has('password.update'));
        $this->assertFalse(Route::has('user-profile-information.update'));
        $this->assertFalse(Route::has('user-password.update'));
        $this->assertFalse(Route::has('two-factor.login'));
        $this->assertFalse(Route::has('two-factor.login.store'));
        $this->assertFalse(Route::has('two-factor.enable'));
    }
}
