<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::today('Asia/Tokyo');
        $yamada = User::query()
            ->where('email', 'yamada@example.com')
            ->firstOrFail();
        $suzuki = User::query()
            ->where('email', 'suzuki@example.com')
            ->firstOrFail();
        $books = Book::query()
            ->orderBy('id')
            ->limit(6)
            ->get();

        if ($books->count() < 6) {
            throw new RuntimeException('読書計画の作成には6冊以上の書籍が必要です。');
        }

        ReadingPlan::query()->create([
            'user_id' => $yamada->id,
            'book_id' => $books[0]->id,
            'target_date' => $today->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        ReadingPlan::query()->create([
            'user_id' => $yamada->id,
            'book_id' => $books[1]->id,
            'target_date' => $today,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        ReadingPlan::query()->create([
            'user_id' => $yamada->id,
            'book_id' => $books[2]->id,
            'target_date' => $today->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        ReadingPlan::query()->create([
            'user_id' => $yamada->id,
            'book_id' => $books[3]->id,
            'target_date' => $today->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        ReadingPlan::query()->create([
            'user_id' => $yamada->id,
            'book_id' => $books[4]->id,
            'target_date' => $today->subDays(10),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => $today->subDays(5),
        ]);

        ReadingPlan::query()->create([
            'user_id' => $suzuki->id,
            'book_id' => $books[5]->id,
            'target_date' => $today->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);
    }
}
