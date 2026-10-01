<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\GenreSeeder;
use Database\Seeders\ReadingPlanSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_six_approved_dynamic_reading_plans(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 00:30:00', 'Asia/Tokyo'));

        $this->seed([
            UserSeeder::class,
            GenreSeeder::class,
            BookSeeder::class,
            ReadingPlanSeeder::class,
        ]);

        $yamada = User::query()
            ->where('email', 'yamada@example.com')
            ->firstOrFail();
        $suzuki = User::query()
            ->where('email', 'suzuki@example.com')
            ->firstOrFail();

        $this->assertDatabaseCount('reading_plans', 6);
        $this->assertSame(5, $yamada->readingPlans()->count());
        $this->assertSame(1, $suzuki->readingPlans()->count());

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $yamada->id,
            'target_date' => '2026-10-04',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $yamada->id,
            'target_date' => '2026-10-01',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $yamada->id,
            'target_date' => '2026-09-28',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $yamada->id,
            'target_date' => '2026-10-08',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $yamada->id,
            'target_date' => '2026-09-21',
            'status' => ReadingPlanStatus::Completed->value,
            'completed_at' => '2026-09-26 00:00:00',
        ]);
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $suzuki->id,
            'target_date' => '2026-10-06',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }
}
