<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_required_users_with_hashed_passwords(): void
    {
        $this->seed(UserSeeder::class);

        $expectedUsers = [
            [
                'name' => '山田太郎',
                'email' => 'yamada@example.com',
            ],
            [
                'name' => '鈴木花子',
                'email' => 'suzuki@example.com',
            ],
            [
                'name' => '田中一郎',
                'email' => 'tanaka@example.com',
            ],
            [
                'name' => '佐藤美咲',
                'email' => 'sato@example.com',
            ],
            [
                'name' => '高橋健太',
                'email' => 'takahashi@example.com',
            ],
        ];

        $this->assertDatabaseCount('users', count($expectedUsers));

        foreach ($expectedUsers as $expectedUser) {
            $this->assertDatabaseHas('users', $expectedUser);

            $user = User::where('email', $expectedUser['email'])->firstOrFail();

            $this->assertNotSame('password', $user->password);
            $this->assertTrue(Hash::check('password', $user->password));
        }
    }

    public function test_it_does_not_duplicate_users_when_run_more_than_once(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 5);
    }
}
