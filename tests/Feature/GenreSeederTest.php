<?php

namespace Tests\Feature;

use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_required_genres(): void
    {
        $this->seed(GenreSeeder::class);

        $expectedGenres = [
            '小説',
            'ビジネス',
            '技術書',
            '自己啓発',
            'エッセイ',
            '歴史',
            '科学',
            '芸術',
            '料理',
            '旅行',
        ];

        $this->assertDatabaseCount('genres', count($expectedGenres));

        foreach ($expectedGenres as $genre) {
            $this->assertDatabaseHas('genres', [
                'name' => $genre,
            ]);
        }

        $this->assertDatabaseMissing('genres', [
            'name' => '教育',
        ]);
    }

    public function test_it_does_not_duplicate_genres_when_run_multiple_times(): void
    {
        $this->seed(GenreSeeder::class);
        $this->seed(GenreSeeder::class);

        $this->assertDatabaseCount('genres', 10);
    }
}
