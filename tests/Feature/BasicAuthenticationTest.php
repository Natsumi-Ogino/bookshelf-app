<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_information(): void
    {
        $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'new-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => '新規ユーザー',
            'email' => 'new-user@example.com',
        ]);
    }

    public function test_registered_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout');

        $this->assertGuest();
    }
}
