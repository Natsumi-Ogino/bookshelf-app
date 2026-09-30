<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TokenAuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_issue_token_with_thirty_day_expiration(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');
        $user = User::factory()->create([
            'email' => 'token@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath(
                'expires_at',
                now()->addDays(30)->toIso8601String()
            );

        $this->assertSame([
            'plain_text_token',
            'token_type',
            'expires_at',
        ], array_keys($response->json()));

        $storedToken = $user->tokens()->sole();
        $plainTextToken = $response->json('plain_text_token');

        $this->assertSame('test-device', $storedToken->name);
        $this->assertSame(['*'], $storedToken->abilities);
        $this->assertTrue(
            $storedToken->expires_at->equalTo(now()->addDays(30))
        );
        $this->assertSame(
            hash('sha256', Str::after($plainTextToken, '|')),
            $storedToken->token
        );
        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_token_request_requires_approved_fields(): void
    {
        $this->postJson('/api/v1/tokens')
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'メールアドレスは必須です。'
            )
            ->assertJsonPath(
                'errors.password.0',
                'パスワードは必須です。'
            )
            ->assertJsonPath(
                'errors.device_name.0',
                '端末名は必須です。'
            );
    }

    public function test_invalid_credentials_return_approved_401_error(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'incorrect-password',
            'device_name' => 'test-device',
        ])
            ->assertUnauthorized()
            ->assertExactJson([
                'error' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_issuance_is_limited_by_email_and_ip(): void
    {
        $user = User::factory()->create([
            'email' => 'limited@example.com',
            'password' => Hash::make('password'),
        ]);
        $payload = [
            'email' => $user->email,
            'password' => 'incorrect-password',
            'device_name' => 'test-device',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/tokens', $payload)
                ->assertUnauthorized();
        }

        $this->postJson('/api/v1/tokens', $payload)
            ->assertTooManyRequests()
            ->assertExactJson([
                'error' => '試行回数が多すぎます。1分後に再度お試しください。',
            ]);
    }

    public function test_user_can_revoke_only_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken(
            'current-device',
            ['*'],
            now()->addDays(30)
        );
        $otherToken = $user->createToken(
            'other-device',
            ['*'],
            now()->addDays(30)
        );

        $this->withToken($currentToken->plainTextToken)
            ->deleteJson('/api/v1/tokens/current')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $currentToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->id,
        ]);
    }

    public function test_expired_token_cannot_access_write_api(): void
    {
        $user = User::factory()->create();
        $expiredToken = $user->createToken(
            'expired-device',
            ['*'],
            now()->subMinute()
        );

        $this->withToken($expiredToken->plainTextToken)
            ->postJson('/api/v1/books')
            ->assertUnauthorized()
            ->assertExactJson([
                'error' => '認証が必要です。',
            ]);
    }
}
