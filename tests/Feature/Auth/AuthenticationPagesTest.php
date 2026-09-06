<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class AuthenticationPagesTest extends TestCase
{
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertViewIs('auth.register');
    }
}
